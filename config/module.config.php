<?php
namespace Exports;

use Laminas\Router\Http;

return [
    'exports_module' => [
        'export_types' => [
            'factories' => [
                'resources_csv' => Service\ExportType\ResourcesCsvFactory::class,
            ],
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            sprintf('%s/../view', __DIR__),
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => sprintf('%s/../language', __DIR__),
                'pattern' => '%s.mo',
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Exports\ExportTypeManager' => Service\ExportType\ExportTypeManagerFactory::class,
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'exports_exports' => Api\Adapter\ExportAdapter::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'Exports\Controller\Admin\Index' => Service\Controller\Admin\IndexControllerFactory::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            'Exports\Form\ExportTypeForm' => Service\Form\ExportTypeFormFactory::class,
            'Exports\Form\ExportForm' => Service\Form\ExportFormFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Exports', // @translate
                'route' => 'admin/exports',
                'action' => 'browse',
                'useRouteMatch' => true,
                'resource' => 'Exports\Controller\Admin\Index',
                'privilege' => 'index',
                'pages' => [
                    [
                        'route' => 'admin/exports',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'exports' => [
                        'type' => Http\Segment::class,
                        'options' => [
                            'route' => '/exports[/:action[/:id]]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Exports\Controller\Admin',
                                'controller' => 'index',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
