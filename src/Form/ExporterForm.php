<?php
namespace Exports\Form;

use Exports\Exporter\Manager;
use Laminas\Form\Form;

class ExporterForm extends Form
{
    protected $exporterManager;

    public function setExporterManager(Manager $exporterManager)
    {
        $this->exporterManager = $exporterManager;
    }

    public function getExporters()
    {
        $exporters = [];
        foreach ($this->exporterManager->getRegisteredNames() as $exporterName) {
            $exporter = $this->exporterManager->get($exporterName);
            $exporters[$exporterName] = $exporter;
        }
        return $exporters;
    }

    public function init()
    {
    }
}
