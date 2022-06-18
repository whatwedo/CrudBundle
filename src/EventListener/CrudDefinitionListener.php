<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use whatwedo\CrudBundle\Controller\CrudDefinitionControllerInterface;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudDefinitionListener
{
    protected $definitionManager;

    public function __construct(DefinitionManager $definitionManager)
    {
        $this->definitionManager = $definitionManager;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (! is_array($controller)) {
            return;
        }

        if (! $controller[0] instanceof CrudDefinitionControllerInterface) {
            return;
        }

        try {
            $controller[0]->setDefinition(
                $this->definitionManager->getDefinitionByRoute($event->getRequest()->attributes->get('_route'))
            );
        } catch (\InvalidArgumentException $e) {
            $resource = $event->getRequest()->attributes->get('_resource', '');
            $resourceImplementsDefinitionInterface = class_exists($resource)
                && in_array(DefinitionInterface::class, (new \ReflectionClass($resource))->getInterfaceNames(), true);
            if ($resourceImplementsDefinitionInterface) {
                $controller[0]->setDefinition(
                    $this->definitionManager->getDefinitionByClassName($resource)
                );

                return;
            }
            foreach ($this->definitionManager->getDefinitions() as $definition) {
                if ($definition::getController() === $controller[0]::class) {
                    $controller[0]->setDefinition($definition);

                    return;
                }
            }
        }
    }
}
