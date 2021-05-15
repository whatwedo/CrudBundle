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

namespace whatwedo\CrudBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudLoader extends Loader
{
    private bool $isLoaded = false;
    protected DefinitionManager $definitionManager;

    public function __construct(DefinitionManager $definitionManager)
    {
        $this->definitionManager = $definitionManager;

        parent::__construct();
    }

    public function load($resource, $type = null): RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "whatwedo_crud" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->definitionManager->getDefinitions() as $definition) {
            foreach ($definition::getCapabilities() as $capability) {
                $route = new Route(
                    '/' . $definition::getRouteNamePrefix() . '/',
                    [
                        '_resource' => $resource,
                        '_controller' => $definition::getController() . '::' . $capability,
                    ]
                );

                switch ($capability) {
                    case RouteEnum::INDEX:
                        break;
                    case RouteEnum::SHOW:
                        $route->setPath($route->getPath().'{id}');
                        $route->setRequirement('id', '\d+');
                        break;
                    case RouteEnum::CREATE:
                        $route->setPath($route->getPath().'create');
                        $route->setMethods(['GET', 'POST']);
                        break;
                    case RouteEnum::EDIT:
                        $route->setPath($route->getPath().'{id}/edit');
                        $route->setMethods(['GET', 'POST', 'PUT', 'PATCH']);
                        $route->setRequirement('id', '\d+');
                        break;
                    case RouteEnum::DELETE:
                        $route->setPath($route->getPath().'{id}/delete');
                        $route->setMethods(['POST']);
                        $route->setRequirement('id', '\d+');
                        break;
                    case RouteEnum::BATCH:
                        $route->setPath($route->getPath().'batch');
                        $route->setMethods(['POST']);
                        break;
                    case RouteEnum::EXPORT:
                        $route->setPath($route->getPath().'export');
                        $route->setMethods(['GET']);
                        break;
                    case RouteEnum::AJAX:
                        $route->setPath($route->getPath().'ajax');
                        $route->setMethods(['POST']);
                        break;
                }

                $routes->add($definition::getRouteNamePrefix().'_'.$capability, $route);
            }
        }

        $this->isLoaded = true;

        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'whatwedo_crud' === $type;
    }
}
