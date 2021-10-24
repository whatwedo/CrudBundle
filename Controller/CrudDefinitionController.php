<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Controller;

use whatwedo\CrudBundle\Definition\DefinitionInterface;

interface CrudDefinitionController
{
    public function setDefinition(DefinitionInterface $definition);
}
