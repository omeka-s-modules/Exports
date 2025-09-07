<?php
namespace Exports\Service\Exporter;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Exports\Exporter\ResourcesJsonLd;

class ResourcesJsonLdFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourcesJsonLd(
            $services->get('Omeka\ApiManager'),
            $services->get('EventManager')
        );
    }
}
