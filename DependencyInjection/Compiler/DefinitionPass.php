<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\CrudBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use whatwedo\CrudBundle\Extension\ExtensionInterface;
use whatwedo\CrudBundle\Resource\DefinitionResource;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class DefinitionPass implements CompilerPassInterface
{
    /**
     * this will initialize all Definitions
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('whatwedo\CrudBundle\Manager\DefinitionManager')) {
            return;
        }

        /*
         * Load Definition Extensions
         */
        foreach ($container->findTaggedServiceIds('crud.extension') as $id => $tags) {
            $crudExtension = $container->getDefinition($id);

            // all extensions must implement ExtensionInExtensionInterfaceterface
            if (!is_subclass_of($crudExtension->getClass(), ExtensionInterface::class)) {
                throw new \UnexpectedValueException(sprintf(
                    'Extensions tagged with crud.extension must implement %s - %s given.',
                    ExtensionInterface::class,
                    $crudExtension->getClass()
                ));
                continue;
            }

            // remove extensions from container if their requirements (other bundles) are not fulfilled
            if (!call_user_func([$crudExtension->getClass(), 'isEnabled'], [$container->getParameter('kernel.bundles')])) {
                $container->removeDefinition($id);
            }
        }

        /*
         * Load definitions
         */
        $crudManager = $container->findDefinition('whatwedo\CrudBundle\Manager\DefinitionManager');

        foreach ($container->findTaggedServiceIds('crud.definition') as $id => $tags) {
            $crudDefinition = $container->getDefinition($id);

            if($crudDefinition->isAbstract()) {
                continue;
            }

            $crudDefinition->addMethodCall('setTemplates', [$container->getParameter('whatwedo_crud.config.templates')]);

            // add available extensions to all Definitions
            foreach ($container->findTaggedServiceIds('crud.extension') as $idExtension => $tagsExtension) {
                $crudDefinition->addMethodCall('addExtension', [new Reference($idExtension)]);
            }

            $crudManager->addMethodCall('addDefinition', [new Reference($id)]);

            // add all Defintions to our DefinitionManager
            foreach ($tags as $attributes) {
                if(array_key_exists('alias', $attributes)) {
                    $crudManager->addMethodCall('addDefinition', [$attributes['alias'], new Reference($id)]);
                }
            }

            // create a resource so that the cache is loaded new as soon as a definition is updated
            $container->addResource(new DefinitionResource($id));
        }
    }
}
