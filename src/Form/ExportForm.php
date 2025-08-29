<?php
namespace Exports\Form;

use Exports\ExportType\Manager;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;

class ExportForm extends Form
{
    protected $exportTypeManager;

    public function setExportTypeManager(Manager $exportTypeManager)
    {
        $this->exportTypeManager = $exportTypeManager;
    }

    public function init()
    {
        $exportTypeName = $this->getOption('export_type_name');
        $exportType = $this->exportTypeManager->get($exportTypeName);

        $this->add([
            'type' => Element\Text::class,
            'name' => 'export_type',
            'options' => [
                'label' => 'Export type', // @translate
            ],
            'attributes' => [
                'id' => 'export_type',
                'disabled' => true,
                'value' => $exportType->getLabel(),
            ],
        ]);
        $this->add([
            'type' => Element\Text::class,
            'name' => 'o:label',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'id' => 'o:label',
                'required' => true,
            ],
        ]);
        $this->add([
            'type' => Element\Hidden::class,
            'name' => 'o-module-exports:export_type',
            'options' => [
                'label' => 'Export type', // @translate
            ],
            'attributes' => [
                'id' => 'o-module-exports:export_type',
                'value' => $exportTypeName,
            ],
        ]);
        $this->add([
            'type' => Fieldset::class,
            'name' => 'o-module-exports:export_data',
            'attributes' => [
                'id' => 'o-module-exports:export_data',
            ],
        ]);
        $exportType->addElements(
            $this->get('o-module-exports:export_data')
        );

        $inputFilter = $this->getInputFilter();
    }
}
