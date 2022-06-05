<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\DataCollector;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudDataCollector extends AbstractDataCollector
{
    public function __construct(
        private DefinitionManager $definitionManager
    ) {
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $definition = null;
        $route = null;
        $page = null;

        try {
            if ($request->attributes->has('_route')) {
                $route = $request->attributes->get('_route');
                $definitionInstance = $this->definitionManager->getDefinitionByRoute($route);
                $definition = $definitionInstance::class;
            }
        } catch (\Exception $ex) {
        }

        if ($definition) {
            if ($route) {
                $page = $this->getPageName($definitionInstance, $route);
            }

            if ($definition) {
                $this->data = [
                    'definition' => $definition,
                    'layout' => $definitionInstance->getLayout(),
                    'page' => $page,
                ];
            }
        }
    }

    public static function getTemplate(): ?string
    {
        return '@whatwedoCrud/data_collector/template.html.twig';
    }

    public function getLayout()
    {
        return $this->data['layout'] ?? '';
    }

    public function getPage()
    {
        return $this->data['page'] ?? '';
    }

    public function getDefinition()
    {
        return $this->data['definition'] ?? '';
    }

    /**
     * @param mixed $route
     */
    protected function getPageName(\whatwedo\CrudBundle\Definition\DefinitionInterface $definitionInstance, string $route): string
    {
        $pageValue = str_replace($definitionInstance::getRoutePathPrefix() . '_', '', $route);

        try {
            $page = \whatwedo\CrudBundle\Enum\Page::tryFrom($pageValue);
            $page = serialize($page);
            $pageItem = explode('"', $page);
            if (count($pageItem) === 3) {
                $page = $pageItem[1];

                return $page;
            }
        } catch (\Throwable $exception) {
        }

        return $pageValue;
    }
}
