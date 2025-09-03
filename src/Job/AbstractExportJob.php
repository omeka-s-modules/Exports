<?php
namespace Exports\Job;

use Exports\Api\Representation\ExportRepresentation;
use Exports\Exporter\ExporterInterface;
use Exports\Module;
use Omeka\Job\AbstractJob;
use Omeka\Job\Exception;

abstract class AbstractExportJob extends AbstractJob
{
    /**
     * @var string
     */
    protected $exportsDirectoryPath;

    /**
     * @var string
     */
    protected $exportDirectoryPath;

    /**
     * @var ExportRepresentation
     */
    protected $export;

    /**
     * @var ExporterInterface
     */
    protected $exporter;

    /**
     * Get a named service. Proxy to $this->getServiceLocator().
     */
    public function get(string $serviceName)
    {
        return $this->getServiceLocator()->get($serviceName);
    }

    /**
     * Get the directory path where the exports are created.
     */
    public function getExportsDirectoryPath(): string
    {
        if (null === $this->exportsDirectoryPath) {
            $exportsDirectoryPath = $this->get('Omeka\Settings')->get('exports_directory_path');
            if (!Module::exportsDirectoryPathIsValid($exportsDirectoryPath)) {
                throw new Exception\RuntimeException('Invalid directory path');
            }
            $this->exportsDirectoryPath = $exportsDirectoryPath;
        }
        return $this->exportsDirectoryPath;
    }

    /**
     * Get the directory path of the export.
     */
    public function getExportDirectoryPath(): string
    {
        if (null === $this->exportDirectoryPath) {
            $this->exportDirectoryPath = sprintf(
                '%s/%s',
                $this->getExportsDirectoryPath(),
                $this->getExportName()
            );
        }
        return $this->exportDirectoryPath;
    }

    /**
     * Get the export representation.
     */
    public function getExport(): ExportRepresentation
    {
        if (null === $this->export) {
            $exportId = $this->getArg('export_id');
            if (!is_numeric($exportId)) {
                throw new Exception\RuntimeException('Missing export_id');
            }
            $this->export = $this->get('Omeka\ApiManager')
                ->read('exports_exports', $exportId)
                ->getContent();
        }
        return $this->export;
    }

    /**
     * Get the exporter object.
     */
    public function getExporter(): ExporterInterface
    {
        if (null === $this->exporter) {
            $export = $this->getExport();
            $this->exporter = $this->get('Exports\ExporterManager')->get($export->exporterName());
        }
        return $this->exporter;
    }

    /**
     * Get the export name.
     */
    public function getExportName(): string
    {
        return $this->getArg('export_name') ?? $this->getExport()->name();
    }

    /**
     * Execute a command.
     */
    public function execute(string $command): void
    {
        $output = $this->get('Omeka\Cli')->execute($command);
        if (false === $output) {
            // Stop the job. Note that the Cli service already logged an error.
            throw new Exception\RuntimeException;
        }
    }

    /**
     * Make a directory in the exports directory.
     */
    public function makeDirectory(string $directoryPath): void
    {
        mkdir(sprintf('%s/%s', $this->getExportDirectoryPath(), $directoryPath), 0755, true);
    }

    /**
     * Make a file in the export directory.
     */
    public function makeFile(string $filePath, string $content = ''): void
    {
        file_put_contents(
            sprintf('%s/%s', $this->getExportDirectoryPath(), $filePath),
            $content
        );
    }

    /**
     * Create the export ZIP file.
     */
    public function createExportZip(): void
    {
        $command = sprintf(
            '%s %s && %s --recurse-paths ../%s .',
            $this->get('Omeka\Cli')->getCommandPath('cd'),
            sprintf('%s/%s', $this->getExportsDirectoryPath(), $this->getExportName()),
            $this->get('Omeka\Cli')->getCommandPath('zip'),
            sprintf('%s.zip', $this->getExportName()),
            $this->getExportName()
        );
        $this->execute($command);
    }

    /**
     * Delete the export directory from the server.
     */
    public function deleteExportDirectory(): void
    {
        $path = $this->getExportDirectoryPath();
        if (is_dir($path) && is_writable($path)) {
            $command = sprintf(
                '%s -r %s',
                $this->get('Omeka\Cli')->getCommandPath('rm'),
                escapeshellarg($path)
            );
            $this->execute($command);
        }
    }

    /**
     * Delete the export ZIP file from the server.
     */
    public function deleteExportZip(): void
    {
        $path = sprintf('%s.zip', $this->getExportDirectoryPath());
        if (is_file($path) && is_writable($path)) {
            $command = sprintf(
                '%s -r %s',
                $this->get('Omeka\Cli')->getCommandPath('rm'),
                escapeshellarg($path)
            );
            $this->execute($command);
        }
    }

    /**
     * Detach all new entities to avoid memory allocation issues during batch
     * processes.
     */
    public function detachAllNewEntities(): void
    {
        $this->get('Omeka\ApiAdapterManager')
            ->get('items')
            ->detachAllNewEntities($this->originalIdentityMap);
    }
}
