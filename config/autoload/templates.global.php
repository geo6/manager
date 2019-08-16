<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories'  => [
            App\Twig\ValueExtension::class => Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ],
    ],
    'twig' => [
        'extensions' => [
            App\Twig\ValueExtension::class,
        ],
    ],
];
