<?php
namespace Exports\Exporter;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\Form\Fieldset;

interface ExporterInterface
{
    /**
     * Get the exporter label.
     */
    public function getLabel(): string;

    /**
     * Get the exporter description.
     */
    public function getDescription(): ?string;

    /**
     * Add form elements needed to configure the export.
     */
    public function addElements(Fieldset $fieldset): void;

    /**
     * Do the export, placing export assets in the export directory.
     */
    public function export(ExportRepresentation $export, ExportJob $job): void;
}
