<?php
return [
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => OMEKA_PATH . '/modules/PersistentIdentifiers/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'listeners' => [
        'PersistentIdentifiers\PIDListener',
    ],
    'service_manager' => [
        'invokables' => [
            'PersistentIdentifiers\PIDListener' => PersistentIdentifiers\Mvc\PIDListener::class,
        ],
        'factories' => [
            'PersistentIdentifiers\PIDSelectorManager' => PersistentIdentifiers\Service\PIDSelector\ManagerFactory::class,
        ],
    ],
    'pid_services' => [
        'factories' => [
            'ezid' => PersistentIdentifiers\Service\PIDSelector\EZIDFactory::class,
            'datacite' => PersistentIdentifiers\Service\PIDSelector\DataCiteFactory::class,
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'pid_items' => 'PersistentIdentifiers\Api\Adapter\PIDItemAdapter',
        ],
    ],
    'controllers' => [
        'factories' => [
            'PersistentIdentifiers\Controller\Index' => 'PersistentIdentifiers\Service\Controller\IndexControllerFactory',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/PersistentIdentifiers/view',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH . '/modules/PersistentIdentifiers/src/Entity',
        ],
        'proxy_paths' => [
            OMEKA_PATH . '/modules/PersistentIdentifiers/data/doctrine-proxies',
        ],
    ],
    'form_elements' => [
        'factories' => [
            'PersistentIdentifiers\Form\ConfigForm' => 'PersistentIdentifiers\Service\Form\ConfigFormFactory',
            'PersistentIdentifiers\Form\EZIDForm' => 'PersistentIdentifiers\Service\Form\EZIDFormFactory',
            'PersistentIdentifiers\Form\DataCiteForm' => 'PersistentIdentifiers\Service\Form\DataCiteFormFactory',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'PIDEditor' => 'PersistentIdentifiers\View\Helper\FormPIDEditor',
        ],
        'delegators' => [
            'Laminas\Form\View\Helper\FormElement' => [
                PersistentIdentifiers\Service\Delegator\FormElementDelegatorFactory::class,
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'persistent-identifiers' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/persistent-identifiers',
                            'defaults' => [
                                '__NAMESPACE__' => 'PersistentIdentifiers\Controller',
                                'controller' => 'Index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'pid-edit' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/pid-edit',
                                    'defaults' => [
                                        'action' => 'pid-edit',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'PIDitem' => [
                'type' => \Laminas\Router\Http\Segment::class,
                'options' => [
                    'route' => '/item/:id',
                    'defaults' => [
                        '__NAMESPACE__' => 'PersistentIdentifiers\Controller',
                        'controller' => 'Index',
                        'action' => 'item-landing-page',
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Persistent Identifiers', // @translate
                'route' => 'admin/persistent-identifiers',
                'resource' => 'PersistentIdentifiers\Controller\Index',
                'pages' => [
                    [
                        'label' => 'Settings', // @translate
                        'route' => 'admin/persistent-identifiers',
                        'resource' => 'PersistentIdentifiers\Controller\Index',
                    ],
                ],
            ],
        ],
    ],
];
