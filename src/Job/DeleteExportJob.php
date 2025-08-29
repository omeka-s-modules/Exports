<?php
namespace Exports\Job;

class DeleteExportJob extends AbstractExportJob
{
    /**
     * Delete the export server artifacts.
     */
    public function perform(): void
    {
        $services = $this->getServiceLocator();
        $fileStore = $services->get('Omeka\File\Store');

        // Delete leftover server artifacts.
        $this->deleteExportDirectory();
        $this->deleteExportZip();

        // Delete the export ZIP file from Omeka storage.
        $fileStore->delete(
            sprintf('exports/%s.zip', $this->getExportName())
        );
    }
}
