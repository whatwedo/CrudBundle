<?php

namespace whatwedo\CrudBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use whatwedo\CrudBundle\DependencyInjection\Compiler\BlockPass;
use whatwedo\CrudBundle\DependencyInjection\Compiler\ContentPass;
use whatwedo\CrudBundle\DependencyInjection\Compiler\DefaultVoterPass;
use whatwedo\CrudBundle\DependencyInjection\Compiler\DefinitionPass;
use whatwedo\CrudBundle\DependencyInjection\Compiler\TwigExtensionPass;

class whatwedoCrudBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DefinitionPass());
        $container->addCompilerPass(new ContentPass());
        $container->addCompilerPass(new BlockPass());
        $container->addCompilerPass(new DefaultVoterPass());
    }
}
