<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use whatwedo\CrudBundle\DependencyInjection\Compiler\DefinitionPass;

class whatwedoCrudBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DefinitionPass());
    }
}
