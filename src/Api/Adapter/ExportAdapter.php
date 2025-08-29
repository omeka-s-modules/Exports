<?php
namespace Exports\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Stdlib\ErrorStore;
use Omeka\Entity\EntityInterface;
use Exports\Api\Representation\ExportRepresentation;
use Exports\Entity\ExportsExport;

class ExportAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'label' => 'label',
        'type' => 'type',
    ];

    public function getResourceName()
    {
        return 'exports_exports';
    }

    public function getRepresentationClass()
    {
        return ExportRepresentation::class;
    }

    public function getEntityClass()
    {
        return ExportsExport::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()) {
            // This value is unalterable after creation.
            if (!isset($data['o-module-exports:export_type']) || !is_string($data['o-module-exports:export_type'])) {
                $errorStore->addError('o-module-exports:export_type', 'export_type must be a string'); // @translate
            }
        }
        if (isset($data['o-module-exports:export_data']) && !is_array($data['o-module-exports:export_data'])) {
            $errorStore->addError('o-module-exports:export_data', 'export_data must be an array'); // @translate
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $this->hydrateOwner($request, $entity);
        if (Request::CREATE === $request->getOperation()) {
            $entity->setType($request->getValue('o-module-exports:export_type'));
        }
        if (Request::UPDATE === $request->getOperation()) {
            $entity->setModified(new DateTime('now'));
        }
        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }
        if ($this->shouldHydrate($request, 'o-module-exports:export_data')) {
            $entity->setData($request->getValue('o-module-exports:export_data'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!is_string($entity->getType())) {
            $errorStore->addError('o-module-exports:export_type', 'An export must have a type'); // @translate
        }
        if (!is_string($entity->getLabel())) {
            $errorStore->addError('o:label', 'An export must have a label'); // @translate
        }
    }
}
