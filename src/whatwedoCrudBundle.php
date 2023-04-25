<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use whatwedo\CrudBundle\DependencyInjection\Compiler\DefinitionPass;
use whatwedo\CrudBundle\DependencyInjection\Compiler\RemoveUnwantedAutoWiredServicesPass;

class whatwedoCrudBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DefinitionPass());
        $container->addCompilerPass(new RemoveUnwantedAutoWiredServicesPass());
    }
}
