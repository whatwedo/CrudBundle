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

namespace whatwedo\CrudBundle\Content;
use Doctrine\Common\Collections\Collection;
use Psr\Container\ContainerInterface;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Enum\VisibilityEnum;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Table\ActionColumn;
use whatwedo\TableBundle\Table\Table;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class RelationContent extends AbstractContent
{
    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return bool
     */
    public function isTable()
    {
        return true;
    }

    /**
     * @param Table $table
     * @param $row
     * @return string
     * @throws \Exception
     */
    public function renderTable(Table $table, $row)
    {
        if (is_callable($this->options['table_configuration'])) {
            $this->options['table_configuration']($table);
        }

        $actionColumnItems = [];

        if (call_user_func([$this->getOption('definition'), 'hasCapability'], RouteEnum::SHOW)) {
            $table->setShowRoute(sprintf(
                '%s_%s',
                call_user_func([$this->getOption('definition'), 'getRoutePrefix']),
                RouteEnum::SHOW
            ));
            $actionColumnItems[] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => sprintf(
                    '%s_%s',
                    call_user_func([$this->getOption('definition'), 'getRoutePrefix']),
                    RouteEnum::SHOW
                ),
            ];
        }

        $definition = $this->getDefinitionManager()->getDefinitionFromClass($this->getOption('definition'));
        $allowEdit = call_user_func([$this->getOption('definition'), 'hasCapability'], RouteEnum::EDIT);
        $showActionColumn = [];
        if ($allowEdit) {
            $reflection = new \ReflectionClass(get_class($definition));
            $allowEdit = $reflection->getMethod('allowEdit')->getClosure($definition);
            $showActionColumn[sprintf('%s_%s', call_user_func([$this->getOption('definition'), 'getRoutePrefix']), RouteEnum::EDIT)] = $allowEdit;
            $actionColumnItems[] = [
                'label' => 'Bearbeiten',
                'icon' => 'pencil',
                'button' => 'warning',
                'route' => sprintf(
                    '%s_%s',
                    call_user_func([$this->getOption('definition'), 'getRoutePrefix']),
                    RouteEnum::EDIT
                ),
            ];
        }

        if (call_user_func([$this->getOption('definition'), 'hasCapability'], RouteEnum::EXPORT)) {
            $table->setExportRoute(sprintf(
                '%s_%s',
                call_user_func([$this->getOption('definition'), 'getRoutePrefix']),
                RouteEnum::EXPORT
            ));
        }

        $table->addColumn('actions', ActionColumn::class, [
            'items' => $actionColumnItems,
            'showActionColumn' => $showActionColumn
        ]);

        $data = $this->getContents($row);

        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        if (is_string($data)){
            throw new \Exception($data);
        }

        $table->setResults(array_values($data));

        return $table->renderTable();
    }

    /**
     * @param $row
     * @return string
     */
    public function render($row)
    {
        return 'call RelationContent::renderTable()';
    }

    /**
     * @return null|string
     */
    public function getIndexRoute()
    {
        if (!$this->options['show_index_button']) {
            return null;
        }

        $capibilities = call_user_func([$this->options['definition'], 'getCapabilities']);

        if (in_array(RouteEnum::INDEX, $capibilities)) {
            return call_user_func([$this->options['definition'], 'getRoutePrefix']) . '_index';
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getCreateRoute()
    {
        if (!$this->options['show_create_button']) {
            return null;
        }

        $capibilities = call_user_func([$this->options['definition'], 'getCapabilities']);

        if (in_array(RouteEnum::CREATE, $capibilities)) {
            return call_user_func([$this->options['definition'], 'getRoutePrefix']) . '_create';
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function isShowInEdit()
    {
        return $this->options['show_in_edit'];
    }

    /**
     * @param $data
     * @return array
     */
    public function getCreateRouteParameters($data)
    {
        $parameters = [];

        if ($this->options['route_addition_key'] !== null
            && $data) {
            $parameters[$this->options['route_addition_key']] = $data->getId();
        }
        return $parameters;
    }

    public function allowCreate($data = null)
    {
        $definition = $this->getDefinitionManager()->getDefinitionFromClass($this->options['definition']);
        return $definition->allowCreate($data);
    }

    /**
     * @param $key
     * @param $value
     */
    public function setOption($key, $value)
    {
        if (isset($this->options[$key])) {
            $this->options[$key] = $value;
        }
    }

    /**
     * @return mixed|DefinitionManager
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getDefinitionManager()
    {
        if (!$this->definitionManager instanceof DefinitionManager) {
            return $this->definitionManager = $this->container->get('whatwedo_crud.manager.definition');
        }

        return $this->definitionManager;
    }

    /**
     * @param DefinitionManager $definitionManager
     */
    public function setDefinitionManager($definitionManager)
    {
        $this->definitionManager = $definitionManager;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym,
            'table_configuration' => null,
            'definition' => null,
            'route_addition_key' => null,
            'show_in_edit' => true,
            'show_index_button' => false,
            'show_create_button' => true,
        ]);
    }
}
