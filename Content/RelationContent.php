<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Content;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Extension\SearchExtension;
use function array_keys;
use function array_reduce;
use function array_reverse;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use function implode;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use whatwedo\CrudBundle\Action\Action;
use whatwedo\CrudBundle\Action\IdentityAction;
use whatwedo\CrudBundle\Action\PostAction;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Form\Type\EntityAjaxType;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\ActionColumn;

class RelationContent extends TableContent
{
    protected array $accessorPathDefinitionCacheMap = [];

    public function __construct(
        protected TableFactory $tableFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected DefinitionManager $definitionManager,
        protected RequestStack $requestStack,
        protected ManagerRegistry $doctrine,
        protected UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @param $row
     *
     * @return string
     */
    public function render($row)
    {
        return $this->getTable($row)->render();
    }

    public function getIndexRoute(): ?string
    {
        if (! $this->options['show_index_button']) {
            return null;
        }

        if ($this->hasCapability(Page::INDEX)) {
            return $this->getRoute(Page::INDEX);
        }

        return null;
    }

    public function getCreateRoute(): ?string
    {
        if ($this->hasCapability(Page::CREATE)) {
            return $this->getRoute(Page::CREATE);
        }

        return null;
    }

    /**
     * @param $data
     *
     * @return array<int|string, mixed>
     */
    public function getCreateRouteParameters($data): array
    {
        $parameters = [];

        if (null !== $this->options['route_addition_key']
            && $data) {
            $parameters[$this->options['route_addition_key']] = $data->getId();
        }

        return $parameters;
    }

    public function isAddAllowed(): bool
    {
        $definition = $this->definitionManager->getDefinitionByClassName($this->getOption('definition'));

        return $this->authorizationChecker->isGranted(Page::CREATE, $definition);
    }

    public function getAddVoterAttribute(): string
    {
        return $this->options['add_voter_attribute'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym,
            'table_options' => [],
            'form_type' => EntityAjaxType::class,
            'form_options' => [
                'definition' => $this->definition,
            ],
            'query_builder_configuration' => null,
            'table_configuration' => null,
            'action_configuration' => null,
            'route_addition_key' => $this->definition::getAlias(),
            'show_index_button' => false,
            'add_voter_attribute' => Page::EDIT,
            'create_url' => null,
            'reload_url' => null,
            'visibility' => [Page::SHOW,]
        ]);

        $resolver->setRequired('create_url');
        $resolver->setRequired('reload_url');

        $resolver->setDefault('definition', fn (Options $options) => $this->getTargetDefinition($options['accessor_path'])::class);
        $resolver->setDefault('class', fn (Options $options) => $this->getTargetDefinition($options['accessor_path'])::getEntity());
        $resolver->setDefault('reload_url', function ($entity) {
            if ($this->getDefinition()::hasCapability(Page::RELOAD)) {
                return $this->urlGenerator->generate(
                    $this->getDefinition()::getRoute(Page::RELOAD), [
                        'id' => $entity->getId(),
                        'field' => $this->acronym,
                    ]
                );
            }
            return null;
        });
        $resolver->setDefault('create_url', function ($entity) {
            if ($this->getOption('definition')::hasCapability(Page::CREATEMODAL)) {
                return $this->urlGenerator->generate(
                    $this->getOption('definition')::getRoute(Page::CREATEMODAL), [
                    $this->getDefinition()::getAlias() => $entity->getId(),
                ],);
            }
            return null;
        });

        $resolver->setAllowedTypes('create_url', ['callable', 'null']);
        $resolver->setAllowedTypes('reload_url', ['callable', 'null']);
        $resolver->setAllowedTypes('table_options', ['array']);
        $resolver->setAllowedTypes('form_options', ['array']);
        $resolver->setAllowedTypes('table_configuration', ['callable', 'null']);
        $resolver->setAllowedTypes('action_configuration', ['callable', 'null']);
        $resolver->setAllowedTypes('query_builder_configuration', ['callable', 'null']);
    }

    public function getRequest(): ?\Symfony\Component\HttpFoundation\Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @return mixed[]
     */
    public function getFormOptions(array $options = []): array
    {
        if (! isset($options['label'])) {
            $this->options['label'] = false;
        }

        if ($this->getOption('form_type') instanceof EntityHiddenType
            || $this->getOption('form_type') instanceof HiddenType) {
            $this->options['label'] = false;
        }

        if ($this->getOption('form_type') instanceof ChoiceType
            && ! isset($options['class'])) {
            $options['class'] = $this->getOption('class');
        }

        if ($this->getOption('form_type') instanceof ChoiceType
            && !isset($options['multiple'])) {
            $options['multiple'] = true;
        }

        return array_merge($options, $this->options['form_options']);
    }

    /**
     * Definiton der Vorselektion.
     *
     * @return string
     */
    public function getPreselectDefinition()
    {
        return $this->getOption('definition');
    }

    /**
     * @param $row
     */
    public function getTable($row): \whatwedo\TableBundle\Table\DoctrineTable
    {
        $options = $this->options['table_options'];

        /*
         * $row = Lesson
         */
        $reverseMapping = $this->getReverseMapping($row);
        $targetDefinition = $this->definitionManager->getDefinitionByClassName($this->getOption('definition'));

        $queryBuilder = $targetDefinition->getQueryBuilder();

        $rootAlias = $targetDefinition::getQueryAlias();
        foreach ($reverseMapping as $field => $value) {
            /*
             * person.studentModuleOccasions => person_studentModuleOccasions
             * person_studentModuleOccasions.occasion => person_studentModuleOccasions_occasion
             * person_studentModuleOccasions_occasion.lessons => person_studentModuleOccasions_occasion_lessons
             */
            $newAlias = $rootAlias.'_'.$field;

            $queryBuilder->leftJoin($rootAlias.'.'.$field, $newAlias);

            if ($value instanceof Collection) {
                $queryBuilder->andWhere($newAlias.' IN (:'.$newAlias.')');
            } else {
                $queryBuilder->andWhere($newAlias.' = :'.$newAlias);
            }

            $queryBuilder->setParameter($newAlias, $value);

            $queryBuilder->addSelect($newAlias);

            $rootAlias = $newAlias;
        }

        $options['query_builder'] = $queryBuilder;

        if (is_callable($this->options['query_builder_configuration'])) {
            $this->options['query_builder_configuration']($queryBuilder, $targetDefinition);
        }

        $table = $this->tableFactory->createDoctrineTable($this->acronym, $options);
        $table->removeExtension(FilterExtension::class);
        $table->removeExtension(SearchExtension::class);
        $targetDefinition->configureTable($table);
        //$targetDefinition->overrideTableConfiguration($table);

        if (is_callable($this->options['table_configuration'])) {
            $this->options['table_configuration']($table);
        }

        $actionColumnItems = [];

        if ($this->hasCapability(Page::SHOW)) {
            $showRoute = $this->getRoute(Page::SHOW);

            $actionColumnItems[Page::SHOW->toRoute()] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => $showRoute,
                'route_parameters' => [],
                'voter_attribute' => Page::SHOW,
            ];
        }

        if ($this->hasCapability(Page::EDIT)) {
            $actionColumnItems[Page::EDIT->toRoute()] = [
                'label' => 'Bearbeiten',
                'icon' => 'pencil',
                'button' => 'warning',
                'route' => $this->getRoute(Page::EDIT),
                'route_parameters' => [],
                'voter_attribute' => Page::EDIT,
            ];
        }

        if ($this->hasCapability(Page::EXPORT)) {
            //$table->setExportRoute($this->getRoute(Page::EXPORT));
        }

        if (is_callable($this->options['action_configuration'])) {
            $actionColumnItems = $this->options['action_configuration']($actionColumnItems);
        }
/*
        $table->addColumn('actions', ActionColumn::class, [
            'items' => $actionColumnItems,
        ]);

        $actionColumn = $table->getActionColumn();

        $actionColumn->setActions(
            [
                IdentityAction::new('')
                    ->setClass('btn btn-xs btn-primary')
                    ->setIcon('fa fa-arrow-right')
                    ->setRoute($this->getRoute(Page::SHOW)),
                IdentityAction::new('')
                    ->setClass('btn btn-xs btn-warning')
                    ->setIcon('fa fa-pencil')
                    ->setRoute($this->getRoute(Page::EDIT)),
                PostAction::new('')
                    ->setClass('btn btn-xs btn-danger')
                    ->setIcon('fa fa-trash-o')
                    ->setRoute($this->getRoute(Page::DELETE)),
            ]
        );
*/
        return $table;
    }

    /**
     * @return mixed[]
     */
    public function getActions(): array
    {
        return $this->options['actions'];
    }

    private function getTargetDefinition($accessorPath = null): \whatwedo\CrudBundle\Definition\DefinitionInterface
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
        $target = array_reduce($associations, fn (string $className, string $association) => $metadataFactory->getMetadataFor($className)->getAssociationTargetClass($association), $this->definition::getEntity());

        return $this->definitionManager->getDefinitionByEntity($target);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getReverseMapping($row): array
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
                'path' => implode('.', array_keys($stack)),
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

    private function getMetadataFactory(): \Doctrine\Persistence\Mapping\ClassMetadataFactory
    {
        return $this->doctrine
            ->getManager()
            ->getMetadataFactory();
    }

    public function getCreateUrl($entity)
    {
        if (is_callable($this->options['create_url'])) {
            return $this->options['create_url']($entity);
        }
        return $this->options['create_url'];
    }

    public function getReloadUrl($entity)
    {
        if (is_callable($this->options['reload_url'])) {
            return $this->options['reload_url']($entity);
        }
        return $this->options['reload_url'];
    }
}
