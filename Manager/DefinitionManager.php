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
    protected $definitions = [];

    /**
     * @param $alias
     */
    public function addDefinition(DefinitionInterface $definition, $alias = null)
    {
        if ($alias == null) {
            $alias = $definition::getAlias();
        }

        $this->definitions[$alias] = $definition;
    }

    /**
     * returns a definition based on the alias
     *
     * @param string $alias alias of the definition
     * @return DefinitionInterface
     * @throws ElementNotFoundException
     */
    public function getDefinition($alias)
    {
        if (!isset($this->definitions[$alias])) {
            throw new ElementNotFoundException(sprintf('Definition with the alias "%s" not found.', $alias));
        }

        return $this->definitions[$alias];
    }

    public function getDefinitionFromEntityClass($entityclass, $allowInheritance = false)
    {
        foreach ($this->definitions as $definition) {
            if ($definition::getEntity() == $entityclass || ($allowInheritance && is_a($entityclass, $definition::getEntity(), true))) {
                return $definition;
            }
        }
        if (!$allowInheritance) {
            return $this->getDefinitionFromEntityClass($entityclass, true);
        }
        return null;
    }

    /**
     * @param $entity
     * @return DefinitionInterface|null
     */
    public function getDefinitionFor($entity)
    {
        if (!is_object($entity)) {
            return null;
        }
        foreach ($this->definitions as $definition) {
            if ($definition::supports($entity)) {
                return $definition;
            }
        }
        return null;
    }

    public function getDefinitionFromClass(string $class): ? DefinitionInterface
    {
        foreach ($this->definitions as $definition) {
            if (get_class($definition) === $class) {
                return $definition;
            }
        }
        return null;
    }

    /**
     * @return DefinitionInterface[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }
}
