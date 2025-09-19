<?php
namespace Exports\Exporter;

use Laminas\EventManager\EventManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\ResourceInterface;

abstract class AbstractResourcesExporter implements ExporterInterface
{
    protected $apiManager;

    protected $eventManager;

    public function __construct(ApiManager $apiManager, EventManager $eventManager)
    {
        $this->apiManager = $apiManager;
        $this->eventManager = $eventManager;
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $view->headScript()->appendFile($view->assetUrl('js/resources-exporter-form.js', 'Exports'));
    }

    /**
     * Get value options for the resource select element.
     */
    public function getResourceValueOptions(): array
    {
        $apiResources = $this->apiManager->search('api_resources')->getContent();
        $resourceValueOptions = [];
        foreach ($apiResources as $apiResource) {
            // The value_annotations resource does not implement the search or
            // read API operations. Remove it as an export option. Annotations
            // are available when exporting items, media, and item_sets.
            if ('value_annotations' === $apiResource->id()) {
                continue;
            }
            $resourceValueOptions[$apiResource->id()] = $apiResource->id();
        }
        asort($resourceValueOptions);
        return $resourceValueOptions;
    }

    /**
     * Get all resource IDs given a type and query.
     */
    public function getResourceIds(string $resourceType, array $resourceQuery): array
    {
        // Get the resource IDs.
        $resourceIds = $this->apiManager->search(
            $resourceType,
            $resourceQuery,
            ['returnScalar' => 'id']
        )->getContent();
        // Some API adapters don't implement the returnScalar request option and
        // return Omeka\Api\ResourceInterface objects instead of scalar IDs. In
        // that case, convert the objects to their corresponding scalar IDs.
        $resourceIds = array_map(
            fn ($resourceId) => ($resourceId instanceof ResourceInterface) ? $resourceId->getId() : $resourceId,
            $resourceIds
        );
        return $resourceIds;
    }
}
