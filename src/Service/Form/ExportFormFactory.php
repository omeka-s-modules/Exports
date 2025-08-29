<?php
namespace Exports\Service\Form;

use Exports\Form\ExportForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ExportFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ExportForm(null, $options ?? []);
        $form->setExportTypeManager($services->get('Exports\ExportTypeManager'));
        return $form;
    }
}
