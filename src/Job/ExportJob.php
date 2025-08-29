<?php
namespace Exports\Job;

class ExportJob extends AbstractExportJob
{
    /**
     * @var array
     */
    protected $originalIdentityMap;

    /**
     * Create the export.
     */
    public function perform(): void
    {
        // Set the original entity map.
        $this->originalIdentityMap = $this->get('Omeka\EntityManager')
            ->getUnitOfWork()
            ->getIdentityMap();

        $export = $this->getExport();

        // First make the export directory.
        $this->makeDirectory('');

        // Delegate the export to the export type, which is responsibe for
        // building the export assets within the export directory.
        $this->getExportType()->export($export, $this);

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
