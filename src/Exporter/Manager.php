<?php
namespace Exports\Exporter;

use Omeka\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = ExporterInterface::class;

    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        try {
            $exporter = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $exporter = new Unknown($name);
        }
        return $exporter;
    }
}
