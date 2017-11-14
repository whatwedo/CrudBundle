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
use Doctrine\Common\Util\ClassUtils;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Exception\ElementNotFoundException;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class DefinitionManager
{
    /**
     * @var array|DefinitionInterface[]
     */
    protected $definitions = [];

    /**
     * @param $alias
     * @param DefinitionInterface $definition
     */
    public function addDefinition($alias, DefinitionInterface $definition)
    {
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

    public function getDefinitionFromEntityClass($entityclass)
    {
        foreach ($this->definitions as $definition) {
            if ($definition::getEntity() == $entityclass) {
                return $definition;
            }
        }
        return null;
    }

    /**
     * @param $entity
     * @return mixed|null|DefinitionInterface
     */
    public function getDefinitionFor($entity)
    {
        if (!is_object($entity)) {
            return null;
        }
        foreach ($this->definitions as $definition)
        {
            if ($definition::getEntity() == ClassUtils::getRealClass(get_class($entity))) {
                return $definition;
            }
        }
        return null;
    }

    /**
     * @param string $class
     * @return mixed|null|DefinitionInterface
     */
    public function getDefinitionFromClass($class)
    {
        foreach ($this->definitions as $definition) {
            if (get_class($definition) == $class) {
                return $definition;
            }
        }
        return null;
    }

    /**
     * @return array|DefinitionInterface[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }
}
