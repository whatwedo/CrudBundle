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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Exception\InvalidDataException;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Event\CriteriaLoadEvent;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Model\SimpleTableData;
use whatwedo\TableBundle\Table\ActionColumn;
use whatwedo\CrudBundle\Enum\VisibilityEnum;

/**
 * Class RelationContent
 * @package whatwedo\CrudBundle\Content
 */
class RelationContent extends TableContent
{
    protected $tableFactory;
    protected $eventDispatcher;
    protected $authorizationChecker;
    protected $definitionManager;
    protected $requestStack;
    protected $doctrine;

    protected $accessorPathDefinitionCacheMap = [];

    /**
     * RelationContent constructor.
     * @param ContainerInterface $container
     */
    public function __construct(TableFactory $tableFactory, EventDispatcherInterface $eventDispatcher, AuthorizationCheckerInterface $authorizationChecker, DefinitionManager $definitionManager, RequestStack $requestStack, RegistryInterface $doctrine)
    {
        $this->tableFactory = $tableFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->authorizationChecker = $authorizationChecker;
        $this->definitionManager = $definitionManager;
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
    }

    public function renderTable($identifier, $row)
    {
        $data = $this->getContents($row);
        if (!$data instanceof Collection) {
            throw new InvalidDataException('data for RelationContent should be an instance of ' . Collection::class);
        }

        $options = $this->options['table_options'];
    /**
     * @param $identifier
     * @param $row
     *
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \whatwedo\TableBundle\Exception\DataLoaderNotAvailableException
     */
        $options['data_loader'] = function($page, $limit) use ($data) {
            $dataLoader = new SimpleTableData();
            $dataLoader->setTotalResults(count($data));

            $criteria = $this->getCriteria()
                ->setMaxResults($limit)
                ->setFirstResult(($page - 1) * $limit)
            ;

            $dataLoader->setResults($data->matching($criteria)->toArray());

            return $dataLoader;
        };

        $table = $this->tableFactory->createTable($identifier, $options);
        $targetDefinition = $this->getDefinitionForAccessorPath($this->getOption('accessor_path'));
        $targetDefinition->configureTable($table);

        if (is_callable($this->options['table_configuration'])) {
            $this->options['table_configuration']($table);
        }

        $this->eventDispatcher->dispatch(CriteriaLoadEvent::PRE_LOAD, new CriteriaLoadEvent($this, $table));

        $actionColumnItems = [];

        if ($this->hasCapability(RouteEnum::SHOW)) {
            $table->setShowRoute($this->getRoute(RouteEnum::SHOW));
            $actionColumnItems[] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => $this->getRoute(RouteEnum::SHOW),
                'route_parameters' => [],
                'voter_attribute' => RouteEnum::SHOW,
            ];
        }

        if ($this->hasCapability(RouteEnum::EDIT)) {
            $actionColumnItems[] = [
                'label' => 'Bearbeiten',
                'icon' => 'pencil',
                'button' => 'warning',
                'route' => $this->getRoute(RouteEnum::EDIT),
                'route_parameters' => [],
                'voter_attribute' => RouteEnum::EDIT,
            ];
        }

        if ($this->hasCapability( RouteEnum::EXPORT)) {
            $table->setExportRoute($this->getRoute(RouteEnum::EXPORT));
        }

        $table->addColumn('actions', ActionColumn::class, [
            'items' => $actionColumnItems,
        ]);

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

        if ($this->hasCapability(RouteEnum::INDEX)) {
            return $this->getRoute(RouteEnum::INDEX);
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getCreateRoute()
    {
        if ($this->hasCapability(RouteEnum::CREATE)) {
            return $this->getRoute(RouteEnum::CREATE);
        }

        return null;
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

    /**
     * @return boolean
     */
    public function isAddAllowed()
    {
        $definition = $this->definitionManager->getDefinitionFromClass($this->options['definition']);
        $entityName = $definition::getEntity();
        $entityReflector = new ReflectionClass($entityName);
        if ($entityReflector->isAbstract()) {
            return false;
        }
        return $this->authorizationChecker->isGranted(RouteEnum::CREATE, $entityReflector->newInstanceWithoutConstructor());
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
     * @return string
     */
    public function getAddVoterAttribute()
    {
        return $this->options['add_voter_attribute'];
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym,
            'table_options' => [],
            'criteria' => [],
            'table_configuration' => null,
            'route_addition_key' => $this->definition::getChildRouteAddition(),
            'show_index_button' => false,
            'add_voter_attribute' => RouteEnum::EDIT,
            'visibility' => VisibilityEnum::SHOW
        ]);

        $resolver->setDefault('definition', function (Options $options) {
            return get_class($this->getDefinitionForAccessorPath($options['accessor_path']));
        });

        $resolver->setAllowedTypes('table_options', ['array']);
        $resolver->setAllowedTypes('table_configuration', ['callable', 'null']);
    }

    /**
     * @param string $accessorPath
     * @return DefinitionInterface
     */
    private function getDefinitionForAccessorPath($accessorPath)
    {
        if (array_key_exists($accessorPath, $this->accessorPathDefinitionCacheMap)) {
            return $this->accessorPathDefinitionCacheMap[$accessorPath];
        }

        $metadataFactory = $metadata = $this->doctrine
            ->getManager()
            ->getMetadataFactory();

        $currentEntity = $this->definition::getEntity();

        // Allow nested relations using dot notation
        foreach (explode('.', $accessorPath) as $pathPart)
        {
            /** @var ClassMetadata $metadata */
            $metadata = $metadataFactory->getMetadataFor($currentEntity);

            $propertyClass = $metadata->associationMappings[$pathPart]['targetEntity'];

            $currentEntity = $propertyClass;

            $targetDefinition = $this->definitionManager->getDefinitionFromEntityClass($propertyClass);
            $this->accessorPathDefinitionCacheMap[$accessorPath] = $targetDefinition;
        }

        return $this->getDefinitionForAccessorPath($accessorPath);
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @return Criteria
     */
    public function getCriteria()
    {
        if (!$this->options['criteria'] instanceof Criteria) {
            $this->options['criteria'] = Criteria::create();
        }
        return $this->options['criteria'];
    }
}
