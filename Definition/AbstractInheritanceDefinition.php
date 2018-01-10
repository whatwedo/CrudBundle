<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
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

namespace whatwedo\CrudBundle\Definition;

use whatwedo\CrudBundle\Controller\InheritanceCrudController;
use whatwedo\TableBundle\Table\Table;

/**
 * Class AbstractInheritanceDefinition
 * @package whatwedo\CrudBundle\Definition
 */
abstract class AbstractInheritanceDefinition extends AbstractDefinition
{

    /*
     Needed functions for Abstract Definition
     */

    /**
     * @return string
     */
    public function getEntity()
    {
        $request = $this->getRequestStack()->getCurrentRequest();
        $queryMapping = $request->attributes->get('class');
        if ($queryMapping === $this->getAllQuery()) {
            return $this->getAbstractEntity();
        } else {
            return array_search($queryMapping, $this->getEntityClassesQueryMapping());
        }
    }

    /**
     * @return string
     */
    public function getEntityTitle()
    {
        $entity = $this->getEntity();
        $titleMapping = $this->getEntityClassesTitleMapping();
        if (array_key_exists($entity, $titleMapping)) {
            return $titleMapping[$entity];
        } else {
            return $this->getAllEntityTitle();
        }
    }

    /**
     * @return string
     */
    public function getQueryAlias()
    {
        $entity = $this->getEntity();
        $queryMapping = $this->getEntityClassesQueryAliasMapping();
        if (array_key_exists($entity, $queryMapping)) {
            return $queryMapping[$entity];
        } else {
            return $this->getAllQueryAlias();
        }
    }

    /**
     * @return string
     */
    public static function getController()
    {
        return InheritanceCrudController::class;
    }

    public function overrideTableConfiguration(Table $table)
    {
    }

    /*
     Helper functions for Inheritance Definition
     */

    /**
     * @return array|string[]
     */
    public abstract function getEntityClasses();

    /**
     * @return string
     */
    public abstract function getAbstractEntity();

    /**
     * @return string
     */
    public function getAllEntityTitle()
    {
        $reflectionClass = new \ReflectionClass($this->getAbstractEntity());
        return $reflectionClass->getShortName();
    }

    /**
     * @return string
     * value used in query Builder
     */
    public function getAllQueryAlias()
    {
        $reflectionClass = new \ReflectionClass($this->getAbstractEntity());
        return strtolower(str_replace(' ', '_', trim(preg_replace('/[^a-z0-9]+/i', ' ', $reflectionClass->getShortName()))));
    }

    /**
     * @return string
     * value used in url
     */
    public function getAllQuery()
    {
        return 'all';
    }

    /**
     * @return array|string[]
     * value used in query Builder
     */
    public function getEntityClassesQueryAliasMapping()
    {
        return $this->getEntityClassesQueryMapping();
    }

    /**
     * @return array|string[]
     * value used in url
     */
    public function getEntityClassesQueryMapping()
    {
        $mapping = [];
        foreach ($this->getEntityClasses() as $entityClass) {
            $reflectionClass = new \ReflectionClass($entityClass);
            $name = strtolower(str_replace(' ', '_', trim(preg_replace('/[^a-z0-9]+/i', ' ', $reflectionClass->getShortName()))));
            $mapping[$entityClass] = $name;
        }
        return $mapping;
    }

    /**
     * @return array|string
     * value used in site title
     */
    public function getEntityClassesTitleMapping()
    {
        $mapping = [];
        foreach ($this->getEntityClasses() as $entityClass) {
            $reflectionClass = new \ReflectionClass($entityClass);
            $mapping[$entityClass] = $reflectionClass->getShortName();
        }
        return $mapping;
    }


}
