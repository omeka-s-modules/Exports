<?php
namespace Exports\Form;

use Exports\ExportType\Manager;
use Laminas\Form\Form;

class ExportTypeForm extends Form
{
    protected $exportTypeManager;

    public function setExportTypeManager(Manager $exportTypeManager)
    {
        $this->exportTypeManager = $exportTypeManager;
    }

    public function getExportTypes()
    {
        $exportTypes = [];
        foreach ($this->exportTypeManager->getRegisteredNames() as $exportTypeName) {
            $exportType = $this->exportTypeManager->get($exportTypeName);
            $exportTypes[$exportTypeName] = $exportType;
        }
        return $exportTypes;
    }

    public function init()
    {
    }
}
