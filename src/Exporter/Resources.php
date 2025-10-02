<?php
namespace Exports\Exporter;

use Exports\Job\ExportJob;
use Laminas\EventManager\EventManager;
use Laminas\Form\Element as LaminasElement;
use Laminas\Form\Fieldset;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Form\Element as OmekaElement;
use Omeka\Api\Adapter\Manager as ApiAdapterManager;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\ResourceInterface;
use Omeka\Permissions\Acl;

class Resources implements ExporterInterface
{
    protected $apiManager;

    protected $apiAdapterManager;

    protected $acl;

    protected $eventManager;

    public function __construct(
        ApiManager $apiManager,
        ApiAdapterManager $apiAdapterManager,
        Acl $acl,
        EventManager $eventManager
    ) {
        $this->apiManager = $apiManager;
        $this->apiAdapterManager = $apiAdapterManager;
        $this->acl = $acl;
        $this->eventManager = $eventManager;
    }

    public function getLabel(): string
    {
        return 'Resources'; // @translate
    }

    public function getDescription(): ?string
    {
        return 'Export a file containing data about selected resources (CSV or JSON-LD).'; // @translate
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $view->headScript()->appendFile($view->assetUrl('js/resources-exporter-form.js', 'Exports'));
    }

    public function addElements(Fieldset $fieldset): void
    {
        // Get value options for the resource select element.
        $resourceValueOptions = [
            'primary_types' => [
                'label' => 'Primary types', // @translate
                'options' => [
                    'items' => 'Items', // @translate
                    'item_sets' => 'Item sets', // @translate
                    'media' => 'Media', // @translate
                ],
            ],
            'other_types' => [
                'label' => 'Other types', // @translate
                'options' => [],
            ],
        ];
        $apiResources = $this->apiManager->search('api_resources')->getContent();
        foreach ($apiResources as $apiResource) {
            // Remove items, item_sets, and media as export options because they
            // are prepended above.
            if (in_array($apiResource->id(), ['items', 'item_sets', 'media'])) {
                continue;
            }
            // The value_annotations resource does not implement the search or
            // read API operations. Remove it as an export option. Annotations
            // are available when exporting items, media, and item_sets.
            if ('value_annotations' === $apiResource->id()) {
                continue;
            }
            // Remove resources as export options that the current user does not
            // have permission to search.
            $resourceAdapter = $this->apiAdapterManager->get($apiResource->id());
            $userIsAllowed = $this->acl->userIsAllowed(get_class($resourceAdapter), 'search');
            if (!$userIsAllowed) {
                continue;
            }
            $resourceValueOptions['other_types']['options'][$apiResource->id()] = $apiResource->id();
        }
        asort($resourceValueOptions['other_types']['options']);

        $fieldset->add([
            'type' => LaminasElement\Select::class,
            'name' => 'resource',
            'options' => [
                'label' => 'Resource type', // @translate
                'info' => 'Select the type of resource to export.', // @translate
                'empty_option' => 'Select a resource type', // @translate
                'value_options' => $resourceValueOptions,
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
                'info' => 'Configure the query used to filter the items to be exported. If no query is entered, all available items will be exported.', // @translate
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
                'info' => 'Configure the query used to filter the item sets to be exported. If no query is entered, all available item sets will be exported.', // @translate
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
                'info' => 'Configure the query used to filter the media to be exported. If no query is entered, all available media will be exported.', // @translate
                'query_resource_type' => 'media',
            ],
            'attributes' => [
                'id' => 'query_media',
                'required' => false,
            ],
        ]);
        $fieldset->add([
            'type' => LaminasElement\Select::class,
            'name' => 'format',
            'options' => [
                'label' => 'Format', // @translate
                'info' => 'Select the format of the export file.', // @translate
                'empty_option' => 'Select a format', // @translate
                'value_options' => [
                    'csv' => 'CSV',
                    'jsonld' => 'JSON-LD',
                ],
            ],
            'attributes' => [
                'id' => 'format',
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => LaminasElement\Select::class,
            'name' => 'reference_by',
            'options' => [
                'label' => 'Reference by', // @translate
                'info' => 'Select whether to reference internal resources by URL or by ID.', // @translate
                'value_options' => [
                    'url' => 'URL',
                    'id' => 'ID',
                ],
            ],
            'attributes' => [
                'id' => 'reference_by',
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => LaminasElement\Text::class,
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

    public function export(ExportJob $job): void
    {
        $export = $job->getExport();
        $job->setOriginalIdentityMap();

        // Get the resource query.
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
        parse_str($query, $resourceQuery);

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

        // Do the export according to format.
        switch ($export->dataValue('format')) {
            case 'csv':
                $resourcesCsv = new ResourcesCsv($this->apiManager, $this->eventManager, $job, $resourceIds);
                $resourcesCsv->export();
                break;
            case 'jsonld':
                $resourcesJsonLd = new ResourcesJsonLd($this->apiManager, $this->eventManager, $job, $resourceIds);
                $resourcesJsonLd->export();
                break;
            default:
                throw new Exception\RuntimeException('Invalid export format.');
        }
    }
}
