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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Exception\InvalidDataException;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Event\CriteriaLoadEvent;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Model\SimpleTableData;
use whatwedo\TableBundle\Table\ActionColumn;

/**
 * Class RelationContent
 * @package whatwedo\CrudBundle\Content
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


    /**
     * RelationContent constructor.
     * @param ContainerInterface $container
     */

    protected $accessorPathDefinitionCacheMap = [];

    /**
     * RelationContent constructor.
     * @param ContainerInterface $container
     */
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
     * @param $identifier
     * @param $row
     *
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \whatwedo\TableBundle\Exception\DataLoaderNotAvailableException
     */
    public function renderTable($identifier, $row)
    {
        $data = $this->getContents($row);
        if (!$data instanceof Collection) {
            throw new InvalidDataException('data for RelationContent should be an instance of ' . Collection::class);
        }

        /** @var TableFactory $tableFactory */
        $tableFactory = $this->container->get('whatwedo_table.factory.table');
        $options = $this->options['table_options'];
        $options['data_loader'] = function($page, $limit) use ($data) {
            $dataLoader = new SimpleTableData();
            $dataLoader->setTotalResults(count($data));

            if ($this->options['criteria'] instanceof Criteria) {
                $criteria = $this->options['criteria'];
            } else {
                $criteria = Criteria::create();
            }

            $criteria
                ->setMaxResults($limit)
                ->setFirstResult(($page - 1) * $limit)
            ;

            $dataLoader->setResults($data->matching($criteria)->toArray());

            return $dataLoader;
        };

        $table = $tableFactory->createTable($identifier, $options);
        $targetDefinition = $this->getDefinitionForAccessorPath($this->getOption('accessor_path'));
        $targetDefinition->configureTable($table);

        if (is_callable($this->options['table_configuration'])) {
            $this->options['table_configuration']($table);
        }

        $this->container->get('event_dispatcher')->dispatch(CriteriaLoadEvent::PRE_LOAD, new CriteriaLoadEvent($this, $table));

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
                'route_parameters' => [],
                'voter_attribute' => RouteEnum::SHOW,
            ];
        }

        if (call_user_func([$this->getOption('definition'), 'hasCapability'], RouteEnum::EDIT)) {
            $actionColumnItems[] = [
                'label' => 'Bearbeiten',
                'icon' => 'pencil',
                'button' => 'warning',
                'route' => sprintf(
                    '%s_%s',
                    call_user_func([$this->getOption('definition'), 'getRoutePrefix']),
                    RouteEnum::EDIT
                ),
                'route_parameters' => [],
                'voter_attribute' => RouteEnum::EDIT,
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
        $capibilities = call_user_func([$this->options['definition'], 'getCapabilities']);

        if (in_array(RouteEnum::CREATE, $capibilities)) {
            return call_user_func([$this->options['definition'], 'getRoutePrefix']) . '_create';
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
        $definition = $this->getDefinitionManager()->getDefinitionFromClass($this->options['definition']);
        $entityName = $definition::getEntity();
        $entityReflector = new ReflectionClass($entityName);
        if ($entityReflector->isAbstract()) {
            return false;
        }
        return $this->container->get('security.authorization_checker')->isGranted(RouteEnum::CREATE, $entityReflector->newInstanceWithoutConstructor());
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
        /** @var ClassMetadata $metadata */
        $metadata = $this->container->get('doctrine')
            ->getManager()
            ->getMetadataFactory()
            ->getMetadataFor($this->definition::getEntity());
        $propertyClass = $metadata->associationMappings[$accessorPath]['targetEntity'];
        $targetDefinition = $this->getDefinitionManager()->getDefinitionFromEntityClass($propertyClass);
        $this->accessorPathDefinitionCacheMap[$accessorPath] = $targetDefinition;
        return $this->getDefinitionForAccessorPath($accessorPath);
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
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
