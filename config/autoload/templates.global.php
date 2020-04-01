<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories'  => [
            App\Twig\FileExtension::class  => App\Twig\FileExtensionFactory::class,
            App\Twig\ValueExtension::class => Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ],
    ],
    'twig' => [
        'extensions' => [
            App\Twig\FileExtension::class,
            App\Twig\ValueExtension::class,
        ],
    ],
];
