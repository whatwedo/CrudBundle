<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use whatwedo\CrudBundle\Enums\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(
        protected DefinitionManager $definitionManager
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, mixed $type = null): RouteCollection
    {
        if ($type === 'whatwedo_crud') {
            if ($this->isLoaded) {
                throw new \RuntimeException('Do not add the "whatwedo_crud" loader twice');
            }
            $this->isLoaded = true;
            return $this->loadAllDefinitions($resource);
        }

        // type === 'crud': load a single definition by class name
        if (is_string($resource) && class_exists($resource)) {
            return $this->loadDefinition($resource, $resource);
        }

        return new RouteCollection();
    }

    private function loadAllDefinitions(mixed $resource): RouteCollection
    {
        $routes = new RouteCollection();
        foreach ($this->definitionManager->getDefinitions() as $definition) {
            $this->addDefinitionRoutes($routes, $definition, $resource, true);
        }
        return $routes;
    }

    private function loadDefinition(string $definitionClass, mixed $resource): RouteCollection
    {
        $routes = new RouteCollection();
        $this->addDefinitionRoutes($routes, $definitionClass, $resource, false);
        return $routes;
    }

    private function addDefinitionRoutes(RouteCollection $routes, string $definition, mixed $resource, bool $usePathPrefix = true): void
    {
        foreach ($definition::getCapabilities() as $capability) {
            if ($capability instanceof Page) {
                $basePath = $usePathPrefix ? '/' . $definition::getRoutePathPrefix() . '/' : '/';
                $route = new Route(
                    $basePath,
                    [
                        '_resource' => $resource,
                        '_controller' => $definition::getController() . '::' . $capability->toRoute() . 'Action',
                    ]
                );

                switch ($capability) {
                    case Page::INDEX:
                        break;
                    case Page::SHOW:
                        $route->setPath($route->getPath() . '{id}');
                        $route->setRequirement('id', '\d+');
                        break;
                    case Page::RELOAD:
                        $route->setPath($route->getPath() . '{id}/reload/{block}/{field?}');
                        $route->setRequirement('id', '\d+');
                        $route->setRequirement('block', '\w+');
                        $route->setRequirement('field', '(\w|\.)+');
                        break;
                    case Page::CREATE:
                        $route->setPath($route->getPath() . 'create');
                        $route->setMethods(['GET', 'POST']);
                        break;
                    case Page::CREATEMODAL:
                        $route->setPath($route->getPath() . 'createmodal');
                        $route->setMethods(['GET', 'POST']);
                        break;
                    case Page::EDIT:
                        $route->setPath($route->getPath() . '{id}/edit');
                        $route->setMethods(['GET', 'POST', 'PUT', 'PATCH']);
                        $route->setRequirement('id', '\d+');
                        break;
                    case Page::DELETE:
                        $route->setPath($route->getPath() . '{id}/delete');
                        $route->setMethods(['POST']);
                        $route->setRequirement('id', '\d+');
                        break;
                    case Page::BATCH:
                        $route->setPath($route->getPath() . 'batch');
                        $route->setMethods(['POST']);
                        break;
                    case Page::EXPORT:
                        $route->setPath($route->getPath() . 'export');
                        $route->setMethods(['GET']);
                        break;
                    case Page::AJAXFORM:
                        $route->setPath($route->getPath() . 'ajax-form');
                        $route->setMethods(['POST']);
                        break;
                    case Page::JSONSEARCH:
                        $route->setPath($route->getPath() . 'json-search');
                        $route->setMethods(['GET']);
                        break;
                }

                $routes->add($definition::getRoutePrefix() . '_' . $capability->toRoute(), $route);
            }
        }
    }

    /**
     * @return bool
     */
    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'whatwedo_crud' || $type === 'crud';
    }
}
