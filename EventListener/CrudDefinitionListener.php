<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use whatwedo\CrudBundle\Controller\CrudDefinitionController;
use whatwedo\CrudBundle\Exception\ElementNotFoundException;
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

        if ($controller[0] instanceof CrudDefinitionController) {
            try {
                $controller[0]->setDefinition(
                    $this->definitionManager->getDefinitionByRoute($event->getRequest()->attributes->get('_route'))
                );
            } catch (ElementNotFoundException $e) {
            }
        }
    }
}
