<?php
namespace Exports\Service\Exporter;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Exports\Exporter\ResourcesCsv;

class ResourcesCsvFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourcesCsv(
            $services->get('Omeka\ApiManager'),
            $services->get('EventManager')
        );
    }
}
