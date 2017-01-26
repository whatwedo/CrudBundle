<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
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

namespace whatwedo\CrudBundle\Tests\Definition;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Router;
use whatwedo\CrudBundle\Enum\RouteEnum;

/**
 * Class DefinitionTest
 * @package whatwedo\CrudBundle\Tests\Definition
 */
class DefinitionTest extends WebTestCase
{

    /**
     *
     */
    public function testIndex()
    {
        $failures = [];
        $client = static::createClient();
        $definitionManager = $client->getContainer()->get('whatwedo_crud.manager.definition');
        $router = $client->getContainer()->get('router');
        foreach ($definitionManager->getDefinitions() as $definition)
        {
            $routename = $definition::getRoutePrefix() . '_' . RouteEnum::INDEX;
            if (!$definition::hasCapability(RouteEnum::INDEX)) {
                continue;
            }
            if ($this->routeHasParameter($router, $routename)) {
                continue;
            }
            $index = $router->generate($routename);
            $client->request('GET', $index);
            $statusCode = $client->getResponse()->getStatusCode();
            try {
                $this->assertEquals(200, $statusCode, $index . ' returns not a 200 response');
            } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
                $failures[] = $e->getMessage();
            }
        }
        $this->printFailures($failures);
    }

    /**
     *
     */
    public function testOrdering()
    {
        $failures = [];
        $client = static::createClient();
        $definitionManager = $client->getContainer()->get('whatwedo_crud.manager.definition');
        $router = $client->getContainer()->get('router');
        foreach ($definitionManager->getDefinitions() as $definition)
        {
            $routename = $definition::getRoutePrefix() . '_' . RouteEnum::INDEX;
            if (!$definition::hasCapability(RouteEnum::INDEX)) {
                continue;
            }
            if ($this->routeHasParameter($router, $routename)) {
                continue;
            }
            $index = $router->generate($routename);
            $crawler = $client->request('GET', $index);
            $anchors = $crawler->filter('a.table-sort-asc');
            foreach ($anchors as $anchor)
            {
                $suffix = $anchor->getAttribute('href');
                $innerClient = static::createClient();
                $innerClient->request('GET', $index . $suffix);
                $statusCode = $innerClient->getResponse()->getStatusCode();
                try {
                    $this->assertEquals(200, $statusCode, 'sort_expression false in ' . $index . $suffix);
                } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
                    $failures[] = $e->getMessage();
                }
            }
        }
        $this->printFailures($failures);
    }

    /**
     *
     */
    public function testCreate()
    {
        $failures = [];
        $client = static::createClient();
        $definitionManager = $client->getContainer()->get('whatwedo_crud.manager.definition');
        $router = $client->getContainer()->get('router');
        foreach ($definitionManager->getDefinitions() as $definition)
        {
            $routename = $definition::getRoutePrefix() . '_' . RouteEnum::CREATE;
            if (!$definition::hasCapability(RouteEnum::CREATE)) {
                continue;
            }
            if ($this->routeHasParameter($router, $routename)) {
                continue;
            }
            $create = $router->generate($routename);

            $client->request('GET', $create);
            $statusCode = $client->getResponse()->getStatusCode();
            try {
                $this->assertEquals(200, $statusCode, $create . ' returns not a 200 response');
            } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
                $failures[] = $e->getMessage();
            }
        }
        $this->printFailures($failures);
    }

    /**
     * @param $failures
     */
    protected function printFailures($failures)
    {
        if(!empty($failures))
        {
            throw new \PHPUnit_Framework_ExpectationFailedException (
                count($failures)." assertions failed:\n\t".implode("\n\t", $failures)
            );
        }
    }

    /**
     * @param Router $router
     * @param $routename
     * @return bool
     */
    protected function routeHasParameter(Router $router, $routename)
    {
        $routes = $router->getRouteCollection();
        $route = $routes->get($routename);
        $path = $route->getPath();
        preg_match('#{([^}]+)}#', $path, $matches);
        return !empty($matches);
    }
}
