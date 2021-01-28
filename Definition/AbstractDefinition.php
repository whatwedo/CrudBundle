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
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Controller\CrudController;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Extension\BreadcrumbsExtension;
use whatwedo\CrudBundle\Extension\ExtensionInterface;
use whatwedo\CrudBundle\Manager\BlockManager;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\View\DefinitionViewInterface;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\Table;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

abstract class AbstractDefinition implements DefinitionInterface
{
    /**
     * listen on changes from this element (and get / set)
     */
    const AJAX_LISTEN = 1;

    /**
     * just get and set values
     */
    const AJAX = 2;

    /**
     * @var \Doctrine\Persistence\ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var DefinitionViewInterface
     */
    protected $definitionView;

    /**
     * @var DefinitionBuilder
     */
    protected $builder;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var Breadcrumbs
     */
    protected $breadcrumbs;

    /**
     * @var ExtensionInterface[]
     */
    protected $extensions;

    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    /**
     * @var BlockManager
     */
    protected $blockManager;

    /**
     * @var array
     */
    protected $templates;

    /**
     * @var DefinitionBuilder|null
     */
    protected $definitionBuilderLabelCache = null;

    protected string $templateDirectory = '';

    protected string $layoutFile = '';

    public function getTitle($entity = null, $route = null): string
    {
        switch ($route) {
            case RouteEnum::INDEX:
                return static::getEntityTitle();
            case RouteEnum::SHOW:
                return $entity;
            case RouteEnum::DELETE:
                return $entity . ' löschen';
            case RouteEnum::EDIT:
                return $entity;
            case RouteEnum::CREATE:
                return 'Hinzufügen';
        }

        return $entity;
    }

    public static function getCapabilities(): array
    {
        return [
            RouteEnum::INDEX,
            RouteEnum::SHOW,
            RouteEnum::DELETE,
            RouteEnum::EDIT,
            RouteEnum::CREATE,
        ];
    }

    public static function hasCapability($string): bool
    {
        return in_array($string, static::getCapabilities());
    }

    public static function getController(): string
    {
        return CrudController::class;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getRepository()->createQueryBuilder($this->getQueryAlias());
    }

    public function configureTable(Table $table): void
    {
    }

    public static function getAlias(): string
    {
        return str_replace(
            ['\\', '_definition', '_bundle'],
            ['_', '', ''],
            strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', static::class))
        );
    }

    /**
     * returns the query alias to be used
     *
     * @return string alias
     */
    public static function getQueryAlias(): string
    {
        return static::getAlias();
    }

    /**
     * set the doctrine registry
     *
     * @param Registry $registry
     * @required
     */
    public function setDoctrine(\Doctrine\Persistence\ManagerRegistry $registry): void
    {
        $this->doctrine = $registry;
    }

    public function getBlockManager(): BlockManager
    {
        return $this->blockManager;
    }

    /**
     * @required
     */
    public function setBlockManager(BlockManager $blockManager): void
    {
        $this->blockManager = $blockManager;
    }

    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    /**
     * @return Breadcrumbs
     */
    public function getBreadcrumbs()
    {
        return $this->getExtension(BreadcrumbsExtension::class)->getBreadcrumbs();
    }

    /**
     * @required
     */
    public function setRequestStack(RequestStack $requestStack): self
    {
        $this->requestStack = $requestStack;

        return $this;
    }

    public function getDefinitionManager(): DefinitionManager
    {
        return $this->definitionManager;
    }

    /**
     * @required
     */
    public function setDefinitionManager(DefinitionManager $definitionManager): self
    {
        $this->definitionManager = $definitionManager;
        return $this;
    }

    public function getTemplateDirectory(): string
    {
        return $this->templateDirectory;
    }

    public function setTemplateDirectory(string $templateDirectory): DefinitionInterface
    {
        $this->templateDirectory = $templateDirectory;

        return $this;
    }

    /**
     * @return string
     */
    public function getLayoutFile(): string
    {
        return $this->layoutFile;
    }

    /**
     * @param string $layoutFile
     */
    public function setLayoutFile(string $layoutFile): void
    {
        $this->layoutFile = $layoutFile;
    }

    /**
     * @required
     */
    public function setDefinitionView(DefinitionViewInterface $definitionView)
    {
        $this->definitionView = $definitionView;
    }

    /**
     * @param null $data
     */
    public function createView($data = null): DefinitionViewInterface
    {
        $this->builder = new DefinitionBuilder($this->blockManager, $this->definitionManager, $this->templates, $this);

        $this->configureView($this->builder, $data);

        $this->definitionView->setDefinition($this);
        $this->definitionView->setData($data);
        $this->definitionView->setBlocks($this->builder->getBlocks());
        $this->definitionView->setTemplates($this->builder->getTemplates());
        $this->definitionView->setTemplateParameters($this->builder->getTemplateParameters());
        return $this->definitionView;
    }

    /**
     * @param Table|DoctrineTable $table
     */
    public function overrideTableConfiguration(Table $table): void
    {
        if ($table->hasExtension(FilterExtension::class)) {
            $table->getFilterExtension()
                ->addFiltersAutomatically(
                    $table,
                    [$this, 'getLabelFor']
                );
        }
    }

    /**
     * @param DoctrineTable $table
     * @param               $property
     */
    public function getLabelFor($table, $property): string
    {
        if ($table instanceof DoctrineTable) {
            foreach ($table->getColumns() as $column) {
                if ($column->getAcronym() == $property) {
                    $label = $column->getLabel();
                    if ($label) {
                        return $label;
                    }
                    break;
                }
            }
        }

        if (is_null($this->definitionBuilderLabelCache)) {
            $this->definitionBuilderLabelCache = new DefinitionBuilder($this->blockManager, $this->definitionManager, $this->templates, $this);
            $this->configureView($this->definitionBuilderLabelCache, null);
        }

        foreach ($this->definitionBuilderLabelCache->getBlocks() as $block) {
            foreach ($block->getContents() as $content) {
                if ($content->getAcronym() == $property) {
                    $label = $content->getOption('label');
                    if ($label) {
                        return $label;
                    }
                    break;
                }
            }
        }

        return ucfirst($property);
    }

    public function getDeleteRedirect(RouterInterface $router, $entity = null): Response
    {
        return new RedirectResponse($router->generate(static::getRouteName(RouteEnum::INDEX)));
    }

    public function getCreateRedirect(RouterInterface $router, $entity = null): Response
    {
        return new RedirectResponse($router->generate(static::getRouteName(RouteEnum::SHOW), [
            'id' => $entity->getId(),
        ]));
    }

    public function getEditRedirect(RouterInterface $router, $entity = null): Response
    {
        return  new RedirectResponse($router->generate(static::getRouteName(RouteEnum::SHOW), [
            'id' => $entity->getId(),
        ]));
    }

    /**
     * @see RouteEnum
     */
    public static function getRouteName(string $capability): string
    {
        if (!RouteEnum::has($capability)) {
            throw new \InvalidArgumentException('Invalid capability specified. Only RouteEnum values are supported.');
        }

        return sprintf('%s_%s', static::getRoutePrefix(), $capability);
    }

    public static function getChildRouteAddition(): string
    {
        return static::getQueryAlias();
    }

    public function getExportAttributes(): array
    {
        return [];
    }

    public function getExportCallbacks(): array
    {
        return [];
    }

    public function getExportHeaders(): array
    {
        return [];
    }

    public function getExportOptions(): array
    {
        return [
            'csv' => [
                'delimiter'     => ';',
                'enclosure'     => '"',
                'escapeChar'    => '\\',
                'keySeparator'  => '.',
            ],
        ];
    }

    public function addAjaxOnChangeListener(): array
    {
        return [];
    }

    /**
     * @param array $data
     */
    public function ajaxOnDataChanged($data): ? \stdClass
    {
        return null;
    }

    /**
     * build breadcrumbs according to route
     *
     * @param object|null $entity
     * @param string|null $route
     */
    public function buildBreadcrumbs($entity = null, $route = null): void
    {
        if (!$this->hasExtension(BreadcrumbsExtension::class)) {
            return;
        }

        if (static::hasCapability(RouteEnum::INDEX)) {
            $this->getBreadcrumbs()->addRouteItem(static::getEntityTitle(), static::getRouteName(RouteEnum::INDEX), $this->getIndexBreadcrumbParameters([], $entity));
        } else {
            $this->getBreadcrumbs()->addItem(static::getEntityTitle());
        }
    }

    /**
     * overwrite breadcrumbs for Index page
     * @param array $parameters
     * @param null  $entity
     */
    public function getIndexBreadcrumbParameters($parameters = [], $entity = null): array
    {
        return $parameters;
    }

    public function getExtension($extension): ExtensionInterface
    {
        if (!$this->hasExtension($extension)) {
            throw new \InvalidArgumentException(sprintf(
                'Extension %s is not enabled. Please configure it first.',
                $extension
            ));
        }

        return $this->extensions[$extension];
    }

    public function hasExtension($extension): bool
    {
        return isset($this->extensions[$extension]);
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->extensions[get_class($extension)] = $extension;
    }

    public function guessType($class, $property)
    {
        return $this->definitionView->guessType($class, $property);
    }

    public static function supports($entity): bool
    {
        return is_a(ClassUtils::getClass($entity), static::getEntity(), true);
    }

    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository($this->getEntity());
    }

    protected function getDoctrine()
    {
        return $this->doctrine;
    }

    protected static function getRoutePrefix(): string
    {
        return static::getAlias();
    }
}
