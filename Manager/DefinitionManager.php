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

namespace whatwedo\CrudBundle\Manager;

use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Exception\ElementNotFoundException;

class DefinitionManager
{
    /**
     * @var DefinitionInterface[]
     */
    protected array $definitions = [];

    public function addDefinition(DefinitionInterface $definition): void
    {
        $this->definitions[$definition::getAlias()] = $definition;
    }

    public function getDefinitionByAlias(string $alias): DefinitionInterface
    {
        return $this->definitions[$alias]
            ?? throw new ElementNotFoundException(sprintf('definition with the alias "%s" not found.', $alias));
    }

    public function getDefinitionByEntity($entity): DefinitionInterface
    {
        foreach ($this->definitions as $definition) {
            if ($definition::supports($entity)) {
                return $definition;
            }
        }

        throw new ElementNotFoundException(
            sprintf('definition for entity "%s" not found.', is_string($entity) ? $entity : get_class($entity))
        );
    }

    public function getDefinitionByClassName(string $class): ?DefinitionInterface
    {
        foreach ($this->definitions as $definition) {
            if (get_class($definition) === $class) {
                return $definition;
            }
        }

        throw new ElementNotFoundException(
            sprintf('definition "%s" not found.', $class)
        );
    }

    public function getDefinitionByRoute($route): ?DefinitionInterface
    {
        if (preg_match('/([\w\_\-]+)\_(\w+)/', $route, $routeMatches)) {
            foreach ($this->definitions as $definition) {
                if ($routeMatches[1] === $definition::getRouteNamePrefix()) {
                    return $definition;
                }
            }
        }

        throw new ElementNotFoundException(
            sprintf('definition for route "%s" not found.', $route)
        );
    }

    /**
     * @return DefinitionInterface[]
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }
}
