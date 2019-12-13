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

namespace whatwedo\CrudBundle\Definition;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Extension\ExtensionInterface;
use whatwedo\CrudBundle\View\DefinitionViewInterface;
use whatwedo\TableBundle\Table\Table;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
interface DefinitionInterface
{
    public static function supports($entity): bool;

    public static function getEntityTitle():string;

    public static function getAlias():string;

    /**
     * @param string $route
     * @see RouteEnum
     * @return string
     */
    public static function getRouteName(string $capability):string;

    /**
     * @param null|object $entity
     * @param null|object $route
     * @return string
     */
    public function getTitle($entity = null, $route = null):string;

    /**
     * returns capabilities of this definition
     *
     * Available Options:
     * - list
     * - show
     * - create
     * - edit
     * - delete
     * - batch
     *
     * @return string[] capabilities
     */
    public static function getCapabilities():array;

    /**
     * returns FQDN of the controller
     *
     * @return string
     */
    public static function getController():string;

    /**
     * returns the fqdn of the entity
     *
     * @return string fqdn of the entity
     */
    public static function getEntity():string;

    /**
     * returns the query alias to be used
     *
     * @return string alias
     */
    public static function getQueryAlias():string;

    /**
     * returns a query builder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder():QueryBuilder;

    /**
     * table configuration
     *
     * @param Table $table
     * @return
     */
    public function configureTable(Table $table):void;

    /**
     * check if this definition has specific capability
     *
     * @param $string
     * @return bool
     */
    public static function hasCapability($string):bool;

    /**
     * get template directory of this definition
     *
     * @return string
     */
    public function getTemplateDirectory():string;

    /**
     * returns a view
     *
     * @param $data
     * @return DefinitionViewInterface
     */
    public function createView($data = null):DefinitionViewInterface;

    /**
     * builds the interface
     *
     * @param DefinitionBuilder $builder
     * @param $data
     */
    public function configureView(DefinitionBuilder $builder, $data);

    /**
     * @param RouterInterface $router
     * @param $entity
     * @return Response
     */
    public function getDeleteRedirect(RouterInterface $router, $entity = null): RedirectResponse;

    /**
     * @return array
     */
    public function getExportAttributes():array;

    /**
     * @return array
     */
    public function getExportCallbacks():array;

    /**
     * @return array
     */
    public function getExportHeaders():array;

    /**
     * @return array
     */
    public function getExportOptions():array;

    /**
     * @param Table $table
     * @return
     */
    public function overrideTableConfiguration(Table $table):void;

    /**
     * @return array
     */
    public function addAjaxOnChangeListener():array;

    /**
     * @param $data
     * @return \stdClass
     */
    public function ajaxOnDataChanged($data): ? \stdClass;

    /**
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension):void;

    /**
     * @param string $extension FQDN of extension
     */
    public function hasExtension($extension):bool;

    /**
     * @param string $extension FQDN of extension
     */
    public function getExtension($extension): ExtensionInterface;

    /**
     * @param string $class
     * @param string $property
     * @return null|\Symfony\Component\Form\Guess\Guess|\Symfony\Component\Form\Guess\TypeGuess
     */
    public function guessType($class, $property);

}
