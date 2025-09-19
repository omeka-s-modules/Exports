<?php
namespace Exports\Exporter;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\EventManager\EventManager;
use Omeka\Api\Manager as ApiManager;

class ResourcesJsonLd
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
