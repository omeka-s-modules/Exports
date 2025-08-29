<?php
namespace Exports\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ExportRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-exports:Export';
    }

    public function getJsonLd()
    {
        $owner = $this->owner();
        $job = $this->job();
        return [
            'o:owner' => $owner ? $owner->getReference() : null,
            'o:job' => $job ? $job->getReference() : null,
            'o:created' => $this->getDateTime($this->created()),
            'o-module-exports:export_type' => $this->type(),
            'o:name' => $this->name(),
            'o:label' => $this->label(),
            'o:data' => $this->data(),
        ];
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/exports',
            [
                'id' => $this->id(),
                'action' => $action,
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function owner()
    {
        return $this->getAdapter('users')->getRepresentation($this->resource->getOwner());
    }

    public function job()
    {
        return $this->getAdapter('jobs')->getRepresentation($this->resource->getJob());
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function type()
    {
        return $this->resource->getType();
    }

    public function typeLabel()
    {
        $exportTypeManager = $this->getServiceLocator()->get('Exports\ExportTypeManager');
        return $exportTypeManager->get($this->resource->getType())->getLabel();
    }

    public function name()
    {
        return $this->resource->getName();
    }

    public function label()
    {
        return $this->resource->getLabel();
    }

    public function data()
    {
        return $this->resource->getData();
    }

    public function dataValue(string $key, $default = null)
    {
        $data = $this->data();
        return $data[$key] ?? $default;
    }

    public function downloadAvailable(): bool
    {
        $job = $this->job();
        return ($job && 'completed' === $job->status());
    }

    public function downloadUrl(): string
    {
        return $this->getFileUrl('exports', $this->name(), 'zip');
    }
}
