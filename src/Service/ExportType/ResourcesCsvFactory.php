<?php
namespace Exports\Service\ExportType;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Exports\ExportType\ResourcesCsv;

class ResourcesCsvFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourcesCsv(
            $services->get('Omeka\ApiManager')
        );
    }
}
