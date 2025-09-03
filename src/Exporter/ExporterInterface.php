<?php
namespace Exports\Exporter;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\Form\Fieldset;

interface ExporterInterface
{
    /**
     * Get the label of this exporter.
     */
    public function getLabel(): string;

    /**
     * Get the description of this exporter.
     */
    public function getDescription(): ?string;

    /**
     * Add the form elements used for the export data.
     */
    public function addElements(Fieldset $fieldset): void;

    /**
     * Export the data.
     */
    public function export(ExportRepresentation $export, ExportJob $job): void;
}
