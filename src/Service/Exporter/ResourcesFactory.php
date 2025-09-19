<?php
namespace Exports\Service\Exporter;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Exports\Exporter\Resources;

class ResourcesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Resources(
            $services->get('Omeka\ApiManager'),
            $services->get('EventManager')
        );
    }
}
