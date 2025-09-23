<?php
namespace Exports\Exporter;

use Exports\Job\ExportJob;
use Laminas\EventManager\EventManager;
use Omeka\Api\Manager as ApiManager;

class ResourcesJsonLd
{
    protected $apiManager;
    protected $eventManager;
    protected $job;
    protected $resourceIds;

    public function __construct(
        ApiManager $apiManager,
        EventManager $eventManager,
        ExportJob $job,
        array $resourceIds
    ) {
        $this->apiManager = $apiManager;
        $this->eventManager = $eventManager;
        $this->job = $job;
        $this->resourceIds = $resourceIds;
    }

    public function export(): void
    {
        $job = $this->job;
        $resourceIds = $this->resourceIds;
        $export = $job->getExport();
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
