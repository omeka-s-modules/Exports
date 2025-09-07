<?php
namespace Exports\Exporter;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class ResourcesJsonLd extends AbstractResourcesExporter
{
    public function getLabel(): string
    {
        return 'Resources JSON-LD'; // @translate
    }

    public function getDescription(): ?string
    {
        return 'Export a JSON-LD file containing data about resources.'; // @translate
    }

    public function addElements(Fieldset $fieldset): void
    {
        $fieldset->add([
            'type' => Element\Select::class,
            'name' => 'resource',
            'options' => [
                'label' => 'Resource type', // @translate
                'info' => 'Enter the type of resource to export.', // @translate
                'empty_option' => 'Select a resource type', // @translate
                'value_options' => $this->getResourceValueOptions(),
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
                'label' => 'Resource query', // @translate
                'info' => 'Enter the query used to filter the resources to be exported. If no query is entered, all available resources will be exported.', // @translate
            ],
            'attributes' => [
                'id' => 'query',
                'required' => false,
            ],
        ]);
    }

    public function export(ExportRepresentation $export, ExportJob $job): void
    {
        $job->setOriginalIdentityMap();

        $resourceType = $export->dataValue('resource');
        parse_str($export->dataValue('query'), $resourceQuery);

        // Get the resource IDs.
        $resourceIds = $this->getResourceIds($resourceType, $resourceQuery);
        $lastResourceId = end($resourceIds);

        $fp = fopen(sprintf('%s/%s.json', $job->getExportDirectoryPath(), $export->name()), 'w');
        fwrite($fp, '[');

        foreach (array_chunk($resourceIds, 100) as $resourceIdsChunk) {
            if ($job->shouldStop()) {
                return; // Stop the job if requested.
            }
            foreach ($resourceIdsChunk as $resourceId) {
                $resource = $this->apiManager->read($resourceType, $resourceId)->getContent();
                $resourceJson = json_encode($resource);
                if ($lastResourceId === $resourceId) {
                    // The JSON specification does not allow trailing commas
                    // within arrays. Don't include it on the last resource.
                    fwrite($fp, $resourceJson);
                } else {
                    fwrite($fp, sprintf('%s,', $resourceJson));
                }
            }
            // Clear memory after every chunk.
            $job->detachAllNewEntities();
        }

        fwrite($fp, ']');
        fclose($fp);
    }
}
