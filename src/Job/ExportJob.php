<?php
namespace Exports\Job;

class ExportJob extends AbstractExportJob
{
    /**
     * Create the export.
     */
    public function perform(): void
    {
        $export = $this->getExport();

        // First make the export directory.
        $this->makeDirectory('');

        // Delegate the export to the exporter, which is responsible for building
        // the export assets within the export directory.
        $this->getExporter()->export($export, $this);

        // Create the export ZIP file.
        $this->createExportZip();

        // Copy the export ZIP file to Omeka storage.
        $this->get('Omeka\File\Store')->put(
            sprintf('%s.zip', $this->getExportDirectoryPath()),
            sprintf('exports/%s.zip', $export->name())
        );

        // Delete leftover server artifacts.
        $this->deleteExportDirectory();
        $this->deleteExportZip();

        $this->get('Omeka\Logger')->notice(sprintf('Memory peak usage: %s', memory_get_peak_usage()));
    }
}
