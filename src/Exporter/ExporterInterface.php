<?php
namespace Exports\Exporter;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Job\ExportJob;
use Laminas\Form\Fieldset;
use Laminas\View\Renderer\PhpRenderer;

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
     * Prepare the form view.
     *
     * Typically used to append JavaScript and CSS to the head.
     */
    public function prepareForm(PhpRenderer $view): void;

    /**
     * Add form elements needed to configure the export.
     */
    public function addElements(Fieldset $fieldset): void;

    /**
     * Do the export, placing export assets in the export directory.
     */
    public function export(ExportRepresentation $export, ExportJob $job): void;
}
