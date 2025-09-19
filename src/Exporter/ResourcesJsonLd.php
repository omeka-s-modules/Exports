<?php
namespace Exports\Exporter;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\Form\Element as LaminasElement;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class ResourcesJsonLd extends AbstractResourcesExporter
{
    public function getLabel(): string
    {
        return 'Resources JSON-LD'; // @translate
    }

    public function getDescription(): ?string
    {
        return 'Export a JSON-LD file containing data about selected resources.'; // @translate
    }

    public function addElements(Fieldset $fieldset): void
    {
        $fieldset->add([
            'type' => LaminasElement\Select::class,
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
            'type' => LaminasElement\Text::class,
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
        $fieldset->add([
            'type' => OmekaElement\Query::class,
            'name' => 'query_items',
            'options' => [
                'label' => 'Item query', // @translate
                'info' => 'Enter the query used to filter the items to be exported. If no query is entered, all available items will be exported.', // @translate
                'query_resource_type' => 'items',
            ],
            'attributes' => [
                'id' => 'query_items',
                'required' => false,
            ],
        ]);
        $fieldset->add([
            'type' => OmekaElement\Query::class,
            'name' => 'query_item_sets',
            'options' => [
                'label' => 'Item set query', // @translate
                'info' => 'Enter the query used to filter the item sets to be exported. If no query is entered, all available item sets will be exported.', // @translate
                'query_resource_type' => 'item_sets',
            ],
            'attributes' => [
                'id' => 'query_item_sets',
                'required' => false,
            ],
        ]);
        $fieldset->add([
            'type' => OmekaElement\Query::class,
            'name' => 'query_media',
            'options' => [
                'label' => 'Media query', // @translate
                'info' => 'Enter the query used to filter the media to be exported. If no query is entered, all available media will be exported.', // @translate
                'query_resource_type' => 'media',
            ],
            'attributes' => [
                'id' => 'query_media',
                'required' => false,
            ],
        ]);
    }

    public function export(ExportRepresentation $export, ExportJob $job): void
    {
        $job->setOriginalIdentityMap();

        $resourceType = $export->dataValue('resource');
        switch ($resourceType) {
            case 'items':
                $query = $export->dataValue('query_items');
                break;
            case 'item_sets':
                $query = $export->dataValue('query_item_sets');
                break;
            case 'media':
                $query = $export->dataValue('query_media');
                break;
            default:
                $query = $export->dataValue('query');
        }
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
