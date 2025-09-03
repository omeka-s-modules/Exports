<?php
namespace Exports\Exporter;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\Form\Fieldset;

class Unknown implements ExporterInterface
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getLabel(): string
    {
        return sprintf('Unknown [%s]', $this->name); // @translate
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function addElements(Fieldset $fieldset): void
    {
    }

    public function export(ExportRepresentation $export, ExportJob $job): void
    {
    }
}
