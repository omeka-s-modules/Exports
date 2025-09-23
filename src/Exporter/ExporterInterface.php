<?php
namespace Exports\Exporter;

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
     *
     * The export job will make an export directory and invoke this method. This
     * method should do the export and place all assets into that directory. The
     * export job will then ZIP up the export directory, copy the file to Omeka
     * file storage, and delete any leftover server artifacts.
     */
    public function export(ExportJob $job): void;
}
