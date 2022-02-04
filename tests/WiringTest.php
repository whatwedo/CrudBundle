<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class WiringTest extends KernelTestCase
{
    public function testServiceWiring()
    {
        foreach ([
            DefinitionManager::class,
        ] as $serviceClass) {
            $this->assertInstanceOf(
                $serviceClass,
                self::getContainer()->get($serviceClass)
            );
        }
    }
}
