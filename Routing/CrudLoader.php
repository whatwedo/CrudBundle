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
use whatwedo\CoreBundle\Formatter\CollectionFormatter;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Manager\DefinitionManager;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class CrudLoader extends Loader
{
    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    public function __construct(DefinitionManager $definitionManager)
    {
        $this->definitionManager = $definitionManager;
    }

    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();
        $definition = $this->definitionManager->getDefinition($resource);

        foreach ($definition->getCapabilities() as $capability) {
            $route = new Route(
                '/' . $capability,
                [
                    '_resource' => $resource,
                    '_controller' => $definition->getController() . '::' . $capability . 'Action',
                ]
            );
            $routeName = $definition->getRoutePrefix() . '_' . $capability;

            switch ($capability) {
                case RouteEnum::INDEX:
                    $route->setPath('/');
                    break;
                case RouteEnum::SHOW:
                    $route->setPath('/{id}');
                    $route->setRequirement('id', '\d+');
                    break;
                case RouteEnum::CREATE:
                    $route->setPath('/create');
                    $route->setMethods(['GET', 'POST']);
                    break;
                case RouteEnum::EDIT:
                    $route->setPath('/{id}/edit');
                    $route->setMethods(['GET', 'POST', 'PUT', 'PATCH']);
                    $route->setRequirement('id', '\d+');
                    break;
                case RouteEnum::DELETE:
                    $route->setPath('/{id}/delete');
                    $route->setMethods(['POST']);
                    $route->setRequirement('id', '\d+');
                    break;
                case RouteEnum::BATCH:
                    $route->setPath('/batch');
                    $route->setMethods(['POST']);
                    break;
                case RouteEnum::EXPORT:
                    $route->setPath('/export');
                    $route->setMethods(['GET']);
                    break;
                case RouteEnum::AJAX:
                    $route->setPath('/ajax');
                    $route->setMethods(['POST']);
                    break;
            }

            $routes->add($routeName, $route);
        }

        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'crud' === $type;
    }
}
