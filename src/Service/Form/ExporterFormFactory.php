<?php
namespace Exports\Service\Form;

use Exports\Form\ExporterForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ExporterFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ExporterForm(null, $options ?? []);
        $form->setExporterManager($services->get('Exports\ExporterManager'));
        return $form;
    }
}
