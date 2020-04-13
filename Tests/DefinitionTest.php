<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\CrudBundle\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Table\SortableColumnInterface;

/**
 * Class DefinitionTest
 */
class DefinitionTest extends WebTestCase
{
    /** @var string[][] */
    const EXCLUDE_DEFINITIONS = [];

    //todo: find another way to do this

    /** @var string[] */
    const SKIP_ORDERING_TESTS = [];

    /**
     * @var KernelBrowser|null
     */
    private $client;

    /**
     * @return DefinitionInterface[]
     */
    public function getDefinitions(): array
    {
        static::bootKernel();

        return array_map(function (DefinitionInterface $definition) {
            return [$definition];
        }, static::$container->get(DefinitionManager::class)->getDefinitions());
    }

    /**
     * @dataProvider getDefinitions
     */
    public function testDefinition(DefinitionInterface $definition): void
    {
        $client = $this->getWebClient();

        $router = self::$container->get('router');
        $failures = [];
        $isTestable = false;

        foreach ($definition::getCapabilities() as $capability) {
            if ($this->isExcluded($definition, $capability)) {
                continue;
            }
            $isTestable = true;

            $route = $router->generate($definition::getRouteName($capability));
            $client->request(Request::METHOD_GET, $route);

            try {
                $this->assertTrue($client->getResponse()->isSuccessful());
            } catch (ExpectationFailedException $e) {
                $failures[] = new AssertionFailedError(sprintf(
                    '"%s" unsuccessful (%s): %s',
                    strtoupper($capability),
                    $client->getResponse()->getStatusCode(),
                    $route
                ));
            }
        }

        if (!$isTestable) {
            $this->markTestSkipped('Definition has no capabilities to test');
        }

        $this->printFailures($failures);
    }

    /**
     * @dataProvider getDefinitions
     */
    public function testOrdering(DefinitionInterface $definition): void
    {
        $client = $client = $this->getWebClient();

        $failures = [];

        $router = self::$container->get('router');

        if (in_array($definition::getAlias(), self::SKIP_ORDERING_TESTS)) {
            $this->markTestSkipped('Definition has no ORDERING');
        }

        if (!$definition::hasCapability(RouteEnum::INDEX)) {
            $this->markTestSkipped('Definition has no INDEX');
        }

        if ($this->isExcluded($definition, RouteEnum::INDEX)) {
            $this->markTestSkipped('INDEX excluded from test');
        }

        $index = $router->generate($definition::getRouteName(RouteEnum::INDEX));
        $crawler = $client->request(Request::METHOD_GET, $index);

        // TODO: use Definition data to figure out sortable columns instead?
        $anchors = $crawler->filter('table[id^="whatwedo_table_"] thead a');

        /** @var \DOMElement $anchor */
        foreach ($anchors as $anchor) {
            $suffix = $anchor->getAttribute('href');
            $client->request('GET', $index . $suffix);

            try {
                $this->assertTrue($client->getResponse()->isSuccessful());
            } catch (ExpectationFailedException $e) {
                preg_match(sprintf('/%s[^_]*_([^=]*)/', SortableColumnInterface::ORDER_ENABLED), $suffix, $sortOptions);

                $failures[] = new AssertionFailedError(sprintf(
                    'Sorting column "%s" unsuccessful: %s',
                    $sortOptions[1],
                    $index
                ));
            }
        }

        $this->printFailures($failures);
    }

    protected function isExcluded(DefinitionInterface $definition, string $capability): bool
    {
        // TODO: why skip? takes to long? set low "page size"?
        if ($capability === RouteEnum::EXPORT) {
            return true;
        }

        /** @var string $definitionClass */
        $definitionClass = get_class($definition);
        $excludeCapabilities = [];

        if (isset(self::EXCLUDE_DEFINITIONS[$definitionClass])) {
            $excludeCapabilities  = self::EXCLUDE_DEFINITIONS[$definitionClass];
        }
        if (in_array($capability, $excludeCapabilities)) {
            return true;
        }

        // TODO: get reference from fixtures for show/edit/delete?
        return $this->routeHasParameter($definition::getRouteName($capability));
    }

    protected function routeHasParameter(string $routeName): bool
    {
        $router = self::$container->get('router');
        return !empty($router->getRouteCollection()->get($routeName)->compile()->getPathVariables());
    }

    /**
     * @param string[] $failures
     */
    protected function printFailures(array $failures): void
    {
        if (!empty($failures)) {
            throw new ExpectationFailedException(implode("\n", $failures));
        }
    }

    protected function getWebClient(?string $userId = null): KernelBrowser
    {
        if (!$this->client) {
            $this->client = self::$container->get('test.client');
            $this->client->setServerParameters([]);

            if ($userId) {
                $this->login($userId);
            }
        }
        return $this->client;
    }

    protected function login(string $user)
    {
    }

    protected function setUp(): void
    {
        static::bootKernel();
    }
}
