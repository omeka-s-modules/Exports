<?php
namespace Exports\ExportType;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\Form\Fieldset;

interface ExportTypeInterface
{
    /**
     * Get the label of this export type.
     */
    public function getLabel(): string;

    /**
     * Get the description of this export type.
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
