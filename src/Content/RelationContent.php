<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Content;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageMode;
use whatwedo\CrudBundle\Form\Type\EntityAjaxType;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Extension\SearchExtension;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\Table;
use function array_keys;
use function array_reduce;
use function array_reverse;
use function implode;

class RelationContent extends TableContent
{
    public const OPT_ACCESSOR_PATH = 'accessor_path';

    public const OPT_TABLE_OPTIONS = 'table_options';

    public const OPT_FORM_TYPE = 'form_type';

    public const OPT_FORM_OPTIONS = 'form_options';

    public const OPT_FORM_OPTIONS_MULTIPLE = 'multiple';

    public const OPT_FORM_OPTIONS_DEFINITTION = self::OPT_DEFINITION;

    public const OPT_HELP = 'help';

    public const OPT_AJAX_FORM_TRIGGER = 'ajax_form_trigger';

    public const OPT_QUERY_BUILDER_CONFIGURATION = 'query_builder_configuration';

    public const OPT_TABLE_CONFIGURATION = 'table_configuration';

    public const OPT_ACTION_CONFIGURATION = 'action_configuration';

    public const OPT_ROUTE_ADDITION_KEY = 'route_addition_key';

    public const OPT_SHOW_INDEX_BUTTON = 'show_index_button';

    public const OPT_ADD_VOTER_ATTRIBUTE = 'add_voter_attribute';

    public const OPT_CREATE_URL = 'create_url';

    public const OPT_RELOAD_URL = 'reload_url';

    public const OPT_VISIBILITY = 'visibility';

    public const OPT_SHOW_TABLE_IN_FORM = 'show_table_in_form';

    public const OPT_DEFINITION = 'definition';

    public const OPT_CLASS = 'class';

    public const OPT_LABEL = 'label';

    public const OPT_CALLABLE = 'callable';

    public const OPT_ATTR = 'attr';

    public const OPT_SHOW_VOTER_ATTRIBUTE = 'show_voter_attribute';

    public const OPT_EDIT_VOTER_ATTRIBUTE = 'edit_voter_attribute';

    public const OPT_CREATE_VOTER_ATTRIBUTE = 'create_voter_attribute';

    public const OPT_BLOCK_PREFIX = 'block_prefix';

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

    public function getIndexRoute(): ?string
    {
        if (! $this->options[self::OPT_SHOW_INDEX_BUTTON]) {
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
     * @return array<int|string, mixed>
     */
    public function getCreateRouteParameters(mixed $data): array
    {
        $parameters = [];

        if ($this->options[self::OPT_ROUTE_ADDITION_KEY] !== null
            && $data) {
            $parameters[$this->options[self::OPT_ROUTE_ADDITION_KEY]] = $data->getId();
        }

        return $parameters;
    }

    public function isAddAllowed(): bool
    {
        $definition = $this->definitionManager->getDefinitionByClassName($this->getOption(self::OPT_DEFINITION));

        return $this->authorizationChecker->isGranted(Page::CREATE, $definition);
    }

    public function getAddVoterAttribute(): mixed
    {
        return $this->options[self::OPT_ADD_VOTER_ATTRIBUTE];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            self::OPT_ACCESSOR_PATH => $this->acronym,
            self::OPT_TABLE_OPTIONS => [],
            self::OPT_FORM_TYPE => EntityAjaxType::class,
            self::OPT_FORM_OPTIONS => fn (Options $options) => [
                self::OPT_FORM_OPTIONS_DEFINITTION => $this->getTargetDefinition($options[self::OPT_ACCESSOR_PATH])::class,
                self::OPT_FORM_OPTIONS_MULTIPLE => true,
            ],
            self::OPT_HELP => null,
            self::OPT_AJAX_FORM_TRIGGER => false,
            self::OPT_QUERY_BUILDER_CONFIGURATION => null,
            self::OPT_TABLE_CONFIGURATION => null,
            self::OPT_ACTION_CONFIGURATION => null,
            self::OPT_ROUTE_ADDITION_KEY => $this->definition::getAlias(),
            self::OPT_SHOW_INDEX_BUTTON => false,
            self::OPT_ADD_VOTER_ATTRIBUTE => Page::EDIT,
            self::OPT_CREATE_URL => null,
            self::OPT_RELOAD_URL => null,
            self::OPT_VISIBILITY => [Page::SHOW, Page::EDIT, Page::CREATE],
            self::OPT_SHOW_TABLE_IN_FORM => false,
        ]);

        $resolver->setRequired(self::OPT_CREATE_URL);
        $resolver->setRequired(self::OPT_RELOAD_URL);
        $resolver->setAllowedTypes(self::OPT_ROUTE_ADDITION_KEY, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPT_SHOW_INDEX_BUTTON, 'boolean');
        $resolver->setAllowedTypes(self::OPT_ADD_VOTER_ATTRIBUTE, ['string', 'null', 'object']);
        $resolver->setAllowedTypes(self::OPT_SHOW_TABLE_IN_FORM, 'boolean');

        $resolver->setDefault(self::OPT_DEFINITION, fn (Options $options) => $this->getTargetDefinition($options['accessor_path'])::class);
        $resolver->setDefault(self::OPT_CLASS, fn (Options $options) => $this->getTargetDefinition($options['accessor_path'])::getEntity());
        $resolver->setDefault(self::OPT_RELOAD_URL, function (mixed $entity) {
            if ($this->getDefinition()::hasCapability(Page::RELOAD)) {
                return $this->urlGenerator->generate(
                    $this->getDefinition()::getRoute(Page::RELOAD),
                    [
                        'id' => $entity->getId(),
                        'block' => $this->getBlock()->getAcronym(),
                        'field' => $this->acronym,
                    ],
                );
            }

            return null;
        });
        $resolver->setDefault(self::OPT_CREATE_URL, function (mixed $entity) {
            if ($this->getOption(self::OPT_DEFINITION)::hasCapability(Page::CREATE)) {
                return $this->urlGenerator->generate(
                    $this->getOption(self::OPT_DEFINITION)::getRoute(Page::CREATE),
                    [
                        $this->getDefinition()::getAlias() => $entity->getId(),
                        'mode' => PageMode::MODAL->value,
                    ],
                );
            }

            return null;
        });

        $resolver->setAllowedTypes(self::OPT_CREATE_URL, ['callable', 'null']);
        $resolver->setAllowedTypes(self::OPT_RELOAD_URL, ['callable', 'null']);
        $resolver->setAllowedTypes(self::OPT_TABLE_OPTIONS, ['array']);
        $resolver->setAllowedTypes(self::OPT_FORM_OPTIONS, ['array']);
        $resolver->setAllowedTypes(self::OPT_TABLE_CONFIGURATION, ['callable', 'null']);
        $resolver->setAllowedTypes(self::OPT_ACTION_CONFIGURATION, ['callable', 'null']);
        $resolver->setAllowedTypes(self::OPT_QUERY_BUILDER_CONFIGURATION, ['callable', 'null']);
    }

    public function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @return mixed[]
     */
    public function getFormOptions(array $options = []): array
    {
        if ($this->getOption(self::OPT_FORM_TYPE) instanceof EntityHiddenType
            || $this->getOption(self::OPT_FORM_TYPE) instanceof HiddenType) {
            $this->options['label'] = false;
        }

        if ($this->getOption(self::OPT_FORM_TYPE) instanceof ChoiceType
            && ! isset($options[self::OPT_CLASS])) {
            $options[self::OPT_CLASS] = $this->getOption(self::OPT_CLASS);
        }

        if ($this->getOption(self::OPT_FORM_TYPE) instanceof ChoiceType
            && ! isset($options['multiple'])) {
            $options['multiple'] = true;
        }

        return array_merge($options, $this->options[self::OPT_FORM_OPTIONS]);
    }

    public function getPreselectDefinition(): ?string
    {
        return $this->getOption(self::OPT_DEFINITION);
    }

    public function getTable(mixed $entity): Table
    {
        $options = $this->options[self::OPT_TABLE_OPTIONS];

        /*
         * $row = Lesson
         */
        $reverseMapping = $this->getReverseMapping($entity);
        $targetDefinition = $this->definitionManager->getDefinitionByClassName($this->getOption(self::OPT_DEFINITION));

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

            if ($value instanceof Collection) {
                $queryBuilder->andWhere($newAlias . ' IN (:' . $newAlias . ')');
            } else {
                $queryBuilder->andWhere($newAlias . ' = :' . $newAlias);
            }

            $queryBuilder->setParameter($newAlias, $value);

            $queryBuilder->addSelect($newAlias);

            $rootAlias = $newAlias;
        }

        $options['dataloader_options']['query_builder'] = $queryBuilder;

        if (is_callable($this->options[self::OPT_QUERY_BUILDER_CONFIGURATION])) {
            $this->options[self::OPT_QUERY_BUILDER_CONFIGURATION]($queryBuilder, $targetDefinition);
        }

        $table = $this->tableFactory->create($this->acronym, DoctrineDataLoader::class, $options);
        $table->removeExtension(FilterExtension::class);
        $table->removeExtension(SearchExtension::class);
        $table->setOption(Table::OPTION_DEFINITION, $targetDefinition);
        $targetDefinition->configureTable($table);
        $table->setOption('title', null); // no h1 for relation content

        if (is_callable($this->options[self::OPT_TABLE_CONFIGURATION])) {
            $this->options[self::OPT_TABLE_CONFIGURATION]($table);
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

        return $table;
    }

    /**
     * @return mixed[]
     */
    public function getActions(): array
    {
        return $this->options['actions'];
    }

    public function getCreateUrl(mixed $entity): string
    {
        if (is_callable($this->options[self::OPT_CREATE_URL])) {
            return $this->options[self::OPT_CREATE_URL]($entity);
        }

        return $this->options[self::OPT_CREATE_URL];
    }

    public function getReloadUrl(mixed $entity): string
    {
        if (is_callable($this->options[self::OPT_RELOAD_URL])) {
            return $this->options[self::OPT_RELOAD_URL]($entity);
        }

        return $this->options[self::OPT_RELOAD_URL];
    }

    private function getTargetDefinition(?string $accessorPath = null): DefinitionInterface
    {
        $metadataFactory = $this->getMetadataFactory();

        $associations = explode('.', $accessorPath ?: $this->getOption(self::OPT_ACCESSOR_PATH));

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
    private function getReverseMapping(mixed $row): array
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

        foreach (explode('.', $this->getOption(self::OPT_ACCESSOR_PATH)) as $part) {
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

    private function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->doctrine
            ->getManager()
            ->getMetadataFactory();
    }
}
