<?php
namespace Exports\Exporter;

use ArrayObject;
use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Omeka\Api\Manager as ApiManager;

class ResourcesCsv
{
    protected $apiManager;

    protected $eventManager;

    public function __construct(ApiManager $apiManager, EventManager $eventManager)
    {
        $this->apiManager = $apiManager;
        $this->eventManager = $eventManager;
    }

    public function export(ExportRepresentation $export, ExportJob $job, array $resourceIds): void
    {
        $resourceType = $export->dataValue('resource');

        // To avoid having to hold every CSV row in memory before writing to the
        // file, we're defining the header row first and then adding the
        // resource rows using the header row as a template. This requires two
        // passes of the resources.

        // Iterate every resource, building the CSV header row.
        $headerRow = [];
        foreach (array_chunk($resourceIds, 100) as $resourceIdsChunk) {
            if ($job->shouldStop()) {
                return; // Stop the job if requested.
            }
            foreach ($resourceIdsChunk as $resourceId) {
                $resource = $this->apiManager->read($resourceType, $resourceId)->getContent();
                $resourceJson = json_decode(json_encode($resource), true);
                foreach ($resourceJson as $k => $v) {
                    $fieldData = $this->getFieldData($k, $v, $export);
                    if (is_array($fieldData)) {
                        foreach ($fieldData as $data) {
                            $headerRow[$data[0]] = $data[0];
                        }
                    }
                }
            }
            // Clear memory after every chunk.
            $job->detachAllNewEntities();
        }

        // Write the header row to the CSV file.
        ksort($headerRow);
        $fp = fopen(sprintf('%s/%s.csv', $job->getExportDirectoryPath(), $export->name()), 'w');
        fputcsv($fp, $headerRow, ',', '"', '');

        // Iterate every resource, building one CSV resource row at a time.
        $rowTemplate = array_fill_keys($headerRow, null);
        foreach (array_chunk($resourceIds, 100) as $resourceIdsChunk) {
            if ($job->shouldStop()) {
                return; // Stop the job if requested.
            }
            foreach ($resourceIdsChunk as $resourceId) {
                $resource = $this->apiManager->read($resourceType, $resourceId)->getContent();
                $resourceJson = json_decode(json_encode($resource), true);
                $resourceRow = $rowTemplate;
                foreach ($resourceJson as $k => $v) {
                    $fieldData = $this->getFieldData($k, $v, $export);
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
            // Clear memory after every chunk.
            $job->detachAllNewEntities();
        }

        fclose($fp);
    }

    /**
     * Get CSV field data from a JSON-LD key-value pair.
     *
     * Determines whether to process the key-value pair and returns an array of
     * corresponding CSV header-field pairs.
     */
    public function getFieldData(string $k, $v, ExportRepresentation $export): ?array
    {
        $multivalueSeparator = $export->dataValue('multivalue_separator');

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
        if (is_array($v) && 0 < count($v) && $this->isInternalLink(reset($v))) {
            return [[$k, implode($multivalueSeparator, array_map(fn ($link) => $link['o:id'], $v))]];
        }
        if ($this->isDate($v)) {
            return [[$k, $v['@value']]];
        }
        if ($this->isPropertyValues($v)) {
            $fieldData = [];
            foreach ($v as $index => $value) {
                $valueData = $this->getValueData($index, $value, $export);
                if (is_array($valueData)) {
                    foreach ($valueData as $value) {
                        $fieldData[sprintf('%s:%s', $k, $value[0])][] = $value[1];
                    }
                }
            }
            return array_map(
                fn ($k, $v) => [$k, implode($multivalueSeparator, $v)],
                array_keys($fieldData),
                array_values($fieldData)
            );
        }

        // Next, let modules return CSV field data.
        $fieldData = new ArrayObject;
        $event = new Event('exports.resources.csv.get_field_data', $this, [
            'k' => $k, // The JSON-LD key
            'v' => $v,  // The JSON-LD value
            'export' => $export, // The export respresentation
            'field_data' => $fieldData, // Modules set CSV header-field pairs to this array object
        ]);
        $this->eventManager->triggerEvent($event);
        if ($fieldData->count()) {
            return $fieldData->getArrayCopy();
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
            && is_array(reset($v))
            && isset(reset($v)['property_id'])
        );
    }

    /**
     * Get property value data from a JSON-LD property value.
     *
     * @param ?int $index The index of the property value in the JSON-LD property values array
     * @param array $v An individual JSON-LD property value
     * @param ExportRepresentation $export The export representation
     * @return ?array An array of CSV header_suffix-value pairs
     */
    public function getValueData(?int $index, array $v, ExportRepresentation $export): ?array
    {
        $valueData = [];
        if (isset($v['@value'])) {
            $headerSuffix = 'literal';
            $valueData[] = [$headerSuffix, $v['@value']];
        } elseif (isset($v['value_resource_id'])) {
            $headerSuffix = 'resource';
            $valueData[] = [$headerSuffix, $v['value_resource_id']];
        } elseif (isset($v['@id'])) {
            $headerSuffix = 'uri';
            $valueData[] = [$headerSuffix, $v['@id']];
        } else {
            // Invalid or unrecognized JSON-LD value.
            return null;
        }

        // Add annotation data, if any, to the value data.
        if (isset($v['@annotation']) && is_array($v['@annotation'])) {
            $allAnnotationValueData = [];
            foreach ($v['@annotation'] as $term => $annotationValues) {
                if ($this->isPropertyValues($annotationValues)) {
                    foreach ($annotationValues as $annotationValue) {
                        $annotationValueData = $this->getValueData(null, $annotationValue, $export);
                        if (is_array($annotationValueData)) {
                            // The annotation's header suffix is a union of the
                            // value's header suffix, the index of JSON-LD value,
                            // the annotation's property term, and the annotation's
                            // data type. This prevents ambiguity in the relationship
                            // between the annotation and its associated value.
                            $headerAnnotationSuffix = sprintf('%s %s %s:%s', $headerSuffix, $index, $term, $annotationValueData[0][0]);
                            $allAnnotationValueData[$headerAnnotationSuffix][] = $annotationValueData[0][1];
                        }
                    }
                }
            }
            $allAnnotationValueData = array_map(
                fn ($k, $v) => [$k, implode($export->dataValue('multivalue_separator'), $v)],
                array_keys($allAnnotationValueData),
                array_values($allAnnotationValueData)
            );
            $valueData = array_merge($valueData, $allAnnotationValueData);
        }

        return $valueData;
    }
}
