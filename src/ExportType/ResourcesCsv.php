<?php
namespace Exports\ExportType;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Api\Manager as ApiManager;

class ResourcesCsv implements ExportTypeInterface
{
    protected $apiManager;

    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function getLabel(): string
    {
        return 'Resources CSV'; // @translate
    }

    public function getDescription(): ?string
    {
        return 'Export a CSV file containing data about the selected resources.'; // @translate
    }

    public function addElements(Fieldset $fieldset): void
    {
        $apiResources = $this->apiManager->search('api_resources')->getContent();
        $resourceValueOptions = [];
        foreach ($apiResources as $apiResource) {
            $resourceValueOptions[$apiResource->id()] = $apiResource->id();
        }
        asort($resourceValueOptions);

        $fieldset->add([
            'type' => Element\Select::class,
            'name' => 'resource',
            'options' => [
                'label' => 'Resource', // @translate
                'info' => 'Enter the resource to export.', // @translate
                'empty_option' => 'Select a resource', // @translate
                'value_options' => $resourceValueOptions,
            ],
            'attributes' => [
                'id' => 'resource',
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Text::class,
            'name' => 'query',
            'options' => [
                'label' => 'Query', // @translate
                'info' => 'Enter the query string used to filter the resources.', // @translate
            ],
            'attributes' => [
                'id' => 'query',
                'required' => false,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Text::class,
            'name' => 'multivalue_separator',
            'options' => [
                'label' => 'Multivalue separator', // @translate
                'info' => 'Enter the character to separate multiple values in a cell.', // @translate
            ],
            'attributes' => [
                'id' => 'multivalue_separator',
                'required' => true,
                'value' => '|',
            ],
        ]);
    }

    public function export(ExportRepresentation $export, ExportJob $job): void
    {
        $resourceName = $export->dataValue('resource');
        parse_str($export->dataValue('query'), $resourceQuery);

        $resourceIds = $this->apiManager->search(
            $resourceName,
            $resourceQuery,
            ['returnScalar' => 'id']
        )->getContent();

        // Iterate every resource, building the CSV header row.
        $headerRow = [];
        foreach (array_chunk($resourceIds, 100) as $resourceIdsChunk) {
            foreach ($resourceIdsChunk as $resourceId) {
                $resource = $this->apiManager->read($resourceName, $resourceId)->getContent();
                $resourceJson = json_decode(json_encode($resource), true);
                foreach ($resourceJson as $k => $v) {
                    $fieldData = $this->getFieldData($k, $v, $export->dataValue('multivalue_separator'));
                    if (is_array($fieldData)) {
                        foreach ($fieldData as $data) {
                            $headerRow[$data[0]] = $data[0];
                        }
                    }
                }
            }
            $job->detachAllNewEntities();
        }

        // Write the header row to the CSV file.
        ksort($headerRow);
        $fp = fopen(sprintf('%s/%s.csv', $job->getExportDirectoryPath(), $export->name()), 'w');
        fputcsv($fp, $headerRow, ',', '"', '');

        // Iterate every resource, building one CSV resource row at a time.
        $rowTemplate = array_fill_keys($headerRow, null);
        foreach (array_chunk($resourceIds, 100) as $resourceIdsChunk) {
            foreach ($resourceIdsChunk as $resourceId) {
                $resource = $this->apiManager->read($resourceName, $resourceId)->getContent();
                $resourceJson = json_decode(json_encode($resource), true);
                $resourceRow = $rowTemplate;
                foreach ($resourceJson as $k => $v) {
                    $fieldData = $this->getFieldData($k, $v, $export->dataValue('multivalue_separator'));
                    if (is_array($fieldData)) {
                        foreach ($fieldData as $data) {
                            if (array_key_exists($data[0], $resourceRow)) {
                                $resourceRow[$data[0]] = $data[1];
                            }
                        }
                    }
                }
                // Write the resource row to the CSV file.
                fputcsv($fp, $resourceRow, ',', '"', '');
            }
            $job->detachAllNewEntities();
        }

        fclose($fp);
    }

    /**
     * Get CSV field data from a JSON-LD key-value pair.
     *
     * Determines whether to keep the key-value pair and returns an array of
     * corresponding CSV header-field pairs.
     */
    public function getFieldData(string $k, $v, string $multivalueSeparator): ?array
    {
        // First, skip unneeded and empty fields.
        if (in_array($k, ['@context', '@id'])) {
            return null;
        }
        if (is_null($v)) {
            return null;
        }
        if (is_array($v) && empty($v)) {
            return null;
        }
        if (is_string($v) && '' === trim($v)) {
            return null;
        }

        // Next, handle specific fields by key.
        if ('@type' === $k) {
            return [[$k, is_array($v) ? implode($multivalueSeparator, $v) : $v]];
        }
        if ('thumbnail_display_urls' === $k) {
            return [
                ['thumbnail_large', $v['large']],
                ['thumbnail_medium', $v['medium']],
                ['thumbnail_square', $v['square']],
            ];
        }

        // Next, handle fields by heuristics.
        if ($this->isInternalLink($v)) {
            return [[$k, $v['o:id']]];
        }
        if (is_array($v) && 0 < count($v) && $this->isInternalLink($v[0])) {
            return [[$k, implode($multivalueSeparator, array_map(fn($link) => $link['o:id'], $v))]];
        }
        if ($this->isDate($v)) {
            return [[$k, $v['@value']]];
        }
        if ($this->isPropertyValues($v)) {
            $fieldData = [];
            foreach ($v as $value) {
                $valueData = $this->getValueData($value, $multivalueSeparator);
                if (is_array($valueData)) {
                    foreach ($valueData as $value) {
                        $fieldData[sprintf('%s:%s', $k, $value[0])][] = $value[1];
                    }
                }
            }
            return array_map(
                fn($k, $v) => [$k, implode($multivalueSeparator, $v)],
                array_keys($fieldData),
                array_values($fieldData)
            );
        }

        // Next, handle the remaining scalar fields.
        if (is_int($v) || is_float($v) || is_bool($v) || is_string($v)) {
            return [[$k, (string) $v]];
        }

        // There's nothing to get.
        return null;
    }

    /**
     * Is this JSON-LD value an internal link?
     */
    public function isInternalLink($v): bool
    {
        return (
            is_array($v)
            && 2 === count($v)
            && isset($v['@id'])
            && isset($v['o:id'])
        );
    }

    /**
     * Is this JSON-LD value a date?
     */
    public function isDate($v): bool
    {
        return (
            is_array($v)
            && 2 === count($v)
            && isset($v['@value'])
            && isset($v['@type'])
            && 'http://www.w3.org/2001/XMLSchema#dateTime' === $v['@type']
        );
    }

    /**
     * Is this JSON-LD value a property value?
     */
    public function isPropertyValues($v): bool
    {
        return (
            is_array($v)
            && 0 < count($v)
            && is_array($v[0])
            && isset($v[0]['property_id'])
        );
    }

    /**
     * Get property value data from a JSON-LD property value.
     */
    public function getValueData(array $v, string $multivalueSeparator): ?array
    {
        $valueData = [];
        if (isset($v['@value'])) {
            $valueData[] = ['literal', $v['@value']];
        } elseif (isset($v['value_resource_id'])) {
            $valueData[] = ['resource', $v['value_resource_id']];
        } elseif (isset($v['@id'])) {
            $valueData[] = ['uri', $v['@id']];
        }
        if (isset($v['@annotation']) && is_array($v['@annotation'])) {
            $allAnnotationValueData = [];
            foreach ($v['@annotation'] as $term => $annotationValues) {
                if ($this->isPropertyValues($annotationValues)) {
                    foreach ($annotationValues as $annotationValue) {
                        $annotationValueData = $this->getValueData($annotationValue, $multivalueSeparator);
                        if (is_array($annotationValueData)) {
                            $allAnnotationValueData[sprintf('annotation %s:%s', $term, $annotationValueData[0][0])][] = $annotationValueData[0][1];
                        }
                    }
                }
            }
            $allAnnotationValueData = array_map(
                fn($k, $v) => [$k, implode($multivalueSeparator, $v)],
                array_keys($allAnnotationValueData),
                array_values($allAnnotationValueData)
            );
            $valueData = array_merge($valueData, $allAnnotationValueData);
        }
        return $valueData ?: null;
    }
}
