<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\Tests\App\Manager\UnwantedManager;

class WiringTest extends KernelTestCase
{
    public function testServiceWiring(): void
    {
        $serviceClass = DefinitionManager::class;
        $this->assertInstanceOf(
            $serviceClass,
            self::getContainer()->get($serviceClass)
        );
    }

    public function testUnwantedAreRemoved(): void
    {
        $serviceNotFoundException = null;
        try {
            self::getContainer()->get(UnwantedManager::class);
            self::assertFalse(true, 'UnwantedManager should not be wired');
        } catch (ServiceNotFoundException $serviceNotFoundException) {
        }
        self::assertNotNull($serviceNotFoundException);
    }
}
