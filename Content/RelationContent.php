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
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Exception\InvalidDataException;
use whatwedo\CrudBundle\Form\Type\EntityAjaxType;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\ActionColumn;
use function array_keys;
use function array_reduce;
use function array_reverse;
use function implode;

/**
 * Class RelationContent
 * @package whatwedo\CrudBundle\Content
 */
class RelationContent extends TableContent implements EditableContentInterface
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

        /*
         * $row = Lesson
         */
        $reverseMapping = $this->getReverseMapping($row);
        $targetDefinition = $this->definitionManager->getDefinitionFromClass($this->getOption('definition'));

        $queryBuilder = $targetDefinition->getQueryBuilder();

        $rootAlias = $targetDefinition::getQueryAlias();
        foreach ($reverseMapping as $field => $value) {
            /*
             * person.studentModuleOccasions => person_studentModuleOccasions
             * person_studentModuleOccasions.occasion => person_studentModuleOccasions_occasion
             * person_studentModuleOccasions_occasion.lessons => person_studentModuleOccasions_occasion_lessons
             */
            $newAlias = $rootAlias . '_' . $field;

            $queryBuilder->leftJoin($rootAlias . '.' . $field, $newAlias);

            if($value instanceof Collection) {
                $queryBuilder->andWhere($newAlias . ' IN (:' . $newAlias.')');
            }
            else {
                $queryBuilder->andWhere($newAlias . ' = :' . $newAlias);
            }

            $queryBuilder->setParameter($newAlias, $value);

            $queryBuilder->addSelect($newAlias);

            $rootAlias = $newAlias;
        }

        $options['query_builder'] = $queryBuilder;

        if (is_callable($this->options['query_builder_configuration'])) {
            $this->options['query_builder_configuration']($queryBuilder, $targetDefinition);
        }

        $table = $this->tableFactory->createDoctrineTable($identifier, $options);
        $targetDefinition->configureTable($table);
        $targetDefinition->overrideTableConfiguration($table);

        if (is_callable($this->options['table_configuration'])) {
            $this->options['table_configuration']($table);
        }

        $actionColumnItems = [];

        if ($this->hasCapability(RouteEnum::SHOW)) {
            $showRoute = $this->getRoute(RouteEnum::SHOW);

            $table->setShowRoute($showRoute);
            $actionColumnItems[] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => $showRoute,
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

        if ($this->hasCapability(RouteEnum::EXPORT)) {
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
        $definition = $this->definitionManager->getDefinitionFromClass($this->getOption('definition'));
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
            'form_type' => EntityAjaxType::class,
            'form_options' => [],
            'query_builder_configuration' => null,
            'table_configuration' => null,
            'route_addition_key' => $this->definition::getChildRouteAddition(),
            'show_index_button' => false,
            'add_voter_attribute' => RouteEnum::EDIT
        ]);

        $resolver->setDefault('definition', function (Options $options) {
            return get_class($this->getTargetDefinition($options['accessor_path']));
        });

        $resolver->setDefault('class', function (Options $options) {
            return $this->getTargetDefinition($options['accessor_path'])::getEntity();
        });

        $resolver->setAllowedTypes('table_options', ['array']);
        $resolver->setAllowedTypes('form_options', ['array']);
        $resolver->setAllowedTypes('table_configuration', ['callable', 'null']);
        $resolver->setAllowedTypes('query_builder_configuration', ['callable', 'null']);
    }

    private function getReverseMapping($row)
    {
        /*
         * $accessorPath: 'occasion.students.person'
         *
         * [
         *      'occasion' => [
         *          'field' => 'lessons',
         *          'path' => ''
         *      ],
         *      'students' => [
         *          'field' => 'occasion',
         *          'path' => 'occasion'
         *      ],
         *      'person' => [
         *          'field' => 'studentModuleOccasions',
         *          'path' => 'occasion.students'
         *      ]
         * ]
         */
        $stack = [];

        foreach (explode('.', $this->getOption('accessor_path')) as $part) {
            $targetEntity = empty($stack) ? $this->definition::getEntity() : end($stack)['_mapping']['targetEntity'];

            $mapping = $this->getMetadataFactory()->getMetadataFor($targetEntity)->getAssociationMapping($part);

            $stack[$part] = [
                '_mapping' => $mapping,
                'field' => $mapping['mappedBy'] ?: $mapping['inversedBy'],
                'path' => implode('.', array_keys($stack))
            ];
        }

        /*
         * [
         *      'studentModuleOccasions' => ModuleOccasionStudent[],
         *      'occasion' => ModuleOccasion,
         *      'lessons' => Lesson
         * ]
         */
        $reverse = [];
        foreach (array_reverse($stack) as $entry) {
            $reverse[$entry['field']] = $entry['path'] ? PropertyAccess::createPropertyAccessor()->getValue($row, $entry['path']) : $row;
        }

        return $reverse;
    }

    private function getTargetDefinition($accessorPath = null)
    {
        $metadataFactory = $this->getMetadataFactory();

        $associations = explode('.', $accessorPath ?: $this->getOption('accessor_path'));

        /*
         * 1:
         * $className = 'Entity\Lesson'
         * $association = 'occasion'
         *
         * 2:
         * $className = 'Entity\ModuleOccasion'
         * $association = 'students'
         *
         * 3:
         * $className = 'Entity\ModuleOccasionStudent'
         * $association = 'person'
         *
         * $target = 'Entity\Person'
         *
         * => PersonDefinition
         */
        $target = array_reduce($associations, function (string $className, string $association) use ($metadataFactory) {
            return $metadataFactory->getMetadataFor($className)->getAssociationTargetClass($association);
        }, $this->definition::getEntity());

        return $this->definitionManager->getDefinitionFromEntityClass($target);
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory|ClassMetadataFactory
     */
    private function getMetadataFactory()
    {
        return $this->doctrine
            ->getManager()
            ->getMetadataFactory();
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->getOption('form_type');
    }

    /**
     * @param array $options
     * @return array
     */
    public function getFormOptions($options = [])
    {
        if (in_array($this->getFormType(), [EntityHiddenType::class, HiddenType::class])) {
            $this->options['label'] = false;
        }
        return array_merge($options, ['label' => $this->getLabel(), 'multiple' => true, 'class' => $this->getOption('class')], $this->options['form_options']);
    }

    /**
     * Definiton der Vorselektion
     * @return string
     */
    public function getPreselectDefinition()
    {
        return $this->getOption('definition');
    }
}
