<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => [
        'all' => true,
    ],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => [
        'all' => true,
    ],
    Symfony\Bundle\TwigBundle\TwigBundle::class => [
        'all' => true,
    ],
    Zenstruck\Foundry\ZenstruckFoundryBundle::class => [
        'all' => true,
    ],
    whatwedo\CoreBundle\whatwedoCoreBundle::class => [
        'all' => true,
    ],
    whatwedo\CrudBundle\whatwedoCrudBundle::class => [
        'all' => true,
    ],
    whatwedo\TableBundle\whatwedoTableBundle::class => [
        'all' => true,
    ],
];
