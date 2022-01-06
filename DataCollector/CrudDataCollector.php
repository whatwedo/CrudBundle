<?php

namespace whatwedo\CrudBundle\DataCollector;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudDataCollector extends AbstractDataCollector
{

    public function __construct(
        private DefinitionManager $definitionManager
    )
    {
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $definition = $this->definitionManager->getDefinitionByRoute($request->attributes->get('_route'));


        $this->data = [
            'definition' => $definition::class,
        ];
    }

    public static function getTemplate(): ?string
    {
        return '@whatwedoCrud/data_collector/template.html.twig';
    }

    public function getDefinition()
    {
        return $this->data['definition'];
    }
}
