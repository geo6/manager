<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories'  => [
            App\Twig\FileExtension::class => Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
            App\Twig\ValueExtension::class => Zend\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ],
    ],
    'twig' => [
        'extensions' => [
            App\Twig\FileExtension::class,
            App\Twig\ValueExtension::class,
        ],
    ],
];
