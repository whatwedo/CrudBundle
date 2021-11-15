<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(
        protected DefinitionManager $definitionManager
    ) {
        parent::__construct();
    }

    public function load($resource, $type = null): RouteCollection
    {
        if ($this->isLoaded) {
            throw new \RuntimeException('Do not add the "whatwedo_crud" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->definitionManager->getDefinitions() as $definition) {
            foreach ($definition::getCapabilities() as $capability) {
                $route = new Route(
                    '/'.$definition::getRoutePathPrefix().'/',
                    [
                        '_resource' => $resource,
                        '_controller' => $definition::getController().'::'.$capability->toRoute(),
                    ]
                );

                switch ($capability) {
                    case Page::INDEX:
                        break;
                    case Page::SHOW:
                        $route->setPath($route->getPath().'{id}');
                        $route->setRequirement('id', '\d+');
                        break;
                    case Page::CREATE:
                        $route->setPath($route->getPath().'create');
                        $route->setMethods(['GET', 'POST']);
                        break;
                    case Page::EDIT:
                        $route->setPath($route->getPath().'{id}/edit');
                        $route->setMethods(['GET', 'POST', 'PUT', 'PATCH']);
                        $route->setRequirement('id', '\d+');
                        break;
                    case Page::DELETE:
                        $route->setPath($route->getPath().'{id}/delete');
                        $route->setMethods(['POST']);
                        $route->setRequirement('id', '\d+');
                        break;
                    case Page::BATCH:
                        $route->setPath($route->getPath().'batch');
                        $route->setMethods(['POST']);
                        break;
                    case Page::EXPORT:
                        $route->setPath($route->getPath().'export');
                        $route->setMethods(['GET']);
                        break;
                    case Page::AJAX:
                        $route->setPath($route->getPath().'ajax');
                        $route->setMethods(['POST']);
                        break;
                }

                $routes->add($definition::getRoutePrefix().'_'.$capability->toRoute(), $route);
            }
        }

        $this->isLoaded = true;

        return $routes;
    }

    /**
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return 'whatwedo_crud' === $type;
    }
}
