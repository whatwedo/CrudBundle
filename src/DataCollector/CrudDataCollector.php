<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\DataCollector;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use whatwedo\CoreBundle\Action\Action;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudDataCollector extends AbstractDataCollector
{
    public function __construct(
        private DefinitionManager $definitionManager
    ) {
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $definitionClass = null;
        $definition = null;
        $route = null;
        $page = null;

        try {
            if ($request->attributes->has('_route')) {
                $route = $request->attributes->get('_route');
                $definition = $this->definitionManager->getDefinitionByRoute($route);
                $definitionClass = $definition::class;
            }
        } catch (\Exception $ex) {
        }

        if ($definitionClass) {
            if ($route) {
                $page = $this->getPageName($definition, $route);
            }

            if ($definitionClass) {
                $this->data = [
                    'definitionClass' => $definitionClass,
                    'layout' => $definition->getLayout(),
                    'templateDir' => $definition->getTemplateDirectory(),
                    'page' => $page,
                ];

                /**
                 * @var Action $action
                 */
                foreach ($definition->getActions() as $key => $action) {
                    $this->data['actions'][$key] = [
                        'class' => $action::class,
                        'label' => $action->getOption('label'),
                        'attr' => $action->getOption('attr'),
                        'icon' => $action->getOption('icon'),
                        'voter_attribute' => $action->getOption('voter_attribute'),
                        'priority' => $action->getOption('priority'),
                    ];

                    if ($action->hasOption('confirmation')) {
                        $this->data['actions'][$key]['confirmation'] = $action->getOption('confirmation');
                    }
                }
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

    public function getDefinitionClass()
    {
        return $this->data['definitionClass'] ?? '';
    }

    public function getTemplateDir()
    {
        return $this->data['templateDir'] ?? '';
    }

    public function getActions()
    {
        return $this->data['actions'] ?? '';
    }

    /**
     * @param mixed $route
     */
    protected function getPageName(DefinitionInterface $definitionInstance, string $route): string
    {
        $pageValue = str_replace($definitionInstance::getRoutePathPrefix() . '_', '', $route);

        try {
            $page = Page::tryFrom($pageValue);
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
