<?php
namespace Exports\Job;

use Omeka\Entity\Job;
use Omeka\Job\Exception;

class ExportJob extends AbstractExportJob
{
    /**
     * Create the export.
     */
    public function perform(): void
    {
        $export = $this->getExport();

        // Make the export directory.
        $this->makeDirectory('');

        // Delegate the export to the exporter.
        $this->getExporter()->export($export, $this);

        // Cancel the export if the job was stopped.
        if (Job::STATUS_STOPPING === $this->job->getStatus()) {
            $this->deleteExportDirectory();
            return;
        }

        // Cancel the export if the export directory is empty.
        if (2 === count(scandir($this->getExportDirectoryPath()))) {
            $this->deleteExportDirectory();
            throw new Exception\RuntimeException('Nothing found in export directory.');
        }

        // Create the export ZIP file.
        $command = sprintf(
            '%s %s && %s --recurse-paths ../%s .',
            $this->get('Omeka\Cli')->getCommandPath('cd'),
            sprintf('%s/%s', $this->getExportsDirectoryPath(), $this->getExportName()),
            $this->get('Omeka\Cli')->getCommandPath('zip'),
            sprintf('%s.zip', $this->getExportName()),
            $this->getExportName()
        );
        $this->execute($command);

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
