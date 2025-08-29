<?php
namespace Exports\Form;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\Form;
use Laminas\Validator\Callback;
use Exports\Module;

class ModuleConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'exports_directory_path',
            'options' => [
                'label' => 'Exports directory path', // @translate
                'info' => 'Enter the path to the directory where your exports will be saved. The path must exist and be writable by the web server.', // @translate
            ],
            'attributes' => [
                'id' => 'exports_directory_path',
                'required' => true,
            ],
        ]);
        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'exports_directory_path',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => 'Callback',
                    'options' => [
                        'messages' => [
                            Callback::INVALID_VALUE => 'Invalid exports directory path. The path must exist and be writable by the web server.', // @translate
                        ],
                        'callback' => [Module::class, 'exportsDirectoryPathIsValid'],
                    ],
                ],
            ],
        ]);
    }
}
