<?php
namespace Exports\Service\Form;

use Exports\Form\ExportTypeForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ExportTypeFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ExportTypeForm(null, $options ?? []);
        $form->setExportTypeManager($services->get('Exports\ExportTypeManager'));
        return $form;
    }
}
