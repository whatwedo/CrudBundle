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

namespace whatwedo\CrudBundle\View;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Content\Content;
use whatwedo\CrudBundle\Content\EditableContentInterface;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Manager\DefinitionManager;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class DefinitionView implements DefinitionViewInterface
{
    /**
     * @var object
     */
    protected $data;

    /**
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @var BlockCollection
     */
    protected $blocks;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var FormInterface|null
     */
    protected $form;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    public function __construct(EngineInterface $templating, FormFactoryInterface $formFactory, Router $router)
    {
        $this->templating = $templating;
        $this->formFactory = $formFactory;
        $this->router = $router;
    }

    public function setDefinitionManager(DefinitionManager $definitionManager)
    {
        $this->definitionManager = $definitionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition(DefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return object
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function setBlocks(BlockCollection $blocks)
    {
        $this->blocks = $blocks;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * {@inheritdoc}
     */
    public function renderShow()
    {
        return $this->templating->render('whatwedoCrudBundle:Crud/_boxes:read.html.twig', [
            'data' => $this->data,
            'helper' => $this,
        ]);
    }

    /**
     * @param string $value text to be rendered
     * @param Content $content
     * @return string html
     */
    public function linkIt($value, Content $content)
    {
        $entity = $content->getContents($this->data);
        $def = $this->definitionManager->getDefinitionFor($entity);
        if (!is_null($def)) {
            $path = $this->router->generate($def::getRoutePrefix() . '_' . RouteEnum::SHOW, ['id' => $entity->getId()]);
            return sprintf('<a href="%s">%s</a>', $path, $value);
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function renderEdit()
    {
        return $this->templating->render('whatwedoCrudBundle:Crud/_boxes:edit.html.twig', [
            'form' => $this->getForm()->createView(),
            'helper' => $this,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function renderCreate()
    {
        return $this->templating->render('whatwedoCrudBundle:Crud/_boxes:create.html.twig', [
            'form' => $this->getForm()->createView(),
            'helper' => $this,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function allowDelete($data = null)
    {
        return $this->definition->allowDelete($data);
    }

    /**
     * @param null $data
     * @return bool
     */
    public function allowCreate($data = null)
    {
        return $this->definition->allowCreate($data);
    }

    /**
     * @param null $data
     * @return bool
     */
    public function allowEdit($data = null)
    {
        return $this->definition->allowEdit($data);
    }

    /**
     * @param $route
     * @param array $params
     * @return string
     */
    public function getPath($route, $params = [])
    {
        if ($this->definition->hasCapability($route)) {
            switch($route) {
                case RouteEnum::SHOW:
                case RouteEnum::EDIT:
                case RouteEnum::DELETE:
                    if (!$this->data) {
                        return 'javascript:alert(\'can\\\'t generate route "' . $route . '" without data\')';
                    }

                    return $this->router->generate(sprintf('%s_%s', $this->definition->getRoutePrefix(), $route),
                        array_merge([
                            'id' => $this->data->getId(),
                            ], $params)
                    );
                case RouteEnum::AJAX:
                    if (!$this->data) {
                        return $this->router->generate(sprintf('%s_%s', $this->definition->getRoutePrefix(), $route),
                            $params
                        );
                    }
                    return $this->router->generate(sprintf('%s_%s', $this->definition->getRoutePrefix(), $route),
                        array_merge([
                            'id' => $this->data->getId(),
                        ], $params)
                    );
                case RouteEnum::INDEX:
                case RouteEnum::BATCH:
                case RouteEnum::CREATE:
                    return $this->router->generate(sprintf('%s_%s', $this->definition->getRoutePrefix(), $route),
                        $params
                    );

                default:
                    return 'javascript:alert(\'can\\\'t generate route "' . $route . '".\')';
            }
        }

        return 'javascript:alert(\'Definition does not have the capability "' . $route . '".\')';
    }

    /**
     * @return null|FormInterface
     */
    public function getForm()
    {
        if ($this->form instanceof FormInterface) {
            return $this->form;
        }

        $builder = $this->formFactory->createBuilder(FormType::class, $this->data, []);

        foreach ($this->getBlocks() as $block) {
            foreach ($block->getContents() as $content) {
                if ($content instanceof EditableContentInterface
                    && !$content->isReadOnly()) {
                    $builder->add(
                        $content->getAcronym(),
                        $content->getFormType(),
                        $content->getFormOptions([ 'required' => false ])
                    );
                }
            }
        }

        $this->form = $builder->getForm();

        return $this->form;
    }

    /**
     * @param bool $onlylisten
     * @return string
     */
    public function getAjaxListen($onlylisten = false)
    {
        $data = $this->definition->addAjaxOnChangeListener();
        if ($onlylisten)
        {
            $data = array_filter($data, function($item) {
                return $item == AbstractDefinition::AJAX_LISTEN;
            });
        }
        $ret = '[';
        $i = 0;
        foreach ($data as $key => $item)
        {
            $ret .= '\'' . $key . '\'';
            if ($i != count($data) - 1){
                $ret .= ',';
            }
            $i++;
        }
        $ret .= ']';
        return $ret;
    }

    /**
     * @param $route
     * @return bool
     */
    public function hasCapability($route)
    {
        return $this->definition->hasCapability($route);
    }

    /**
     * @return DefinitionInterface
     */
    public function getDefinition()
    {
        return $this->definition;
    }
}
