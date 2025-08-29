<?php
namespace Exports\ExportType;

use Omeka\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = ExportTypeInterface::class;

    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        try {
            $exportType = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $exportType = new Unknown($name);
        }
        return $exportType;
    }
}
