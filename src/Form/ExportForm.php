<?php
namespace Exports\Form;

use Exports\Exporter\Manager;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;

class ExportForm extends Form
{
    protected $exporterManager;

    public function setExporterManager(Manager $exporterManager)
    {
        $this->exporterManager = $exporterManager;
    }

    public function init()
    {
        $exporterName = $this->getOption('exporter_name');
        $exporter = $this->exporterManager->get($exporterName);

        $this->add([
            'type' => Element\Text::class,
            'name' => 'exporter',
            'options' => [
                'label' => 'Exporter', // @translate
            ],
            'attributes' => [
                'id' => 'exporter',
                'disabled' => true,
                'value' => $exporter->getLabel(),
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
            'name' => 'o-module-exports:exporter_name',
            'options' => [
                'label' => 'Exporter', // @translate
            ],
            'attributes' => [
                'id' => 'o-module-exports:exporter_name',
                'value' => $exporterName,
            ],
        ]);
        $this->add([
            'type' => Fieldset::class,
            'name' => 'o-module-exports:export_data',
            'attributes' => [
                'id' => 'o-module-exports:export_data',
            ],
        ]);
        $exporter->addElements(
            $this->get('o-module-exports:export_data')
        );

        $inputFilter = $this->getInputFilter();
    }
}
