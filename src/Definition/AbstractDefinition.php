<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Definition;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use whatwedo\CrudBundle\Action\Action;
use whatwedo\CrudBundle\Action\PostAction;
use whatwedo\CrudBundle\Action\SubmitAction;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Controller\CrudController;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\Enum\PageMode;
use whatwedo\CrudBundle\Enum\PageModeInterface;
use whatwedo\CrudBundle\Extension\BreadcrumbsExtension;
use whatwedo\CrudBundle\Extension\ExtensionInterface;
use whatwedo\CrudBundle\Extension\JsonSearchExtension;
use whatwedo\CrudBundle\Manager\BlockManager;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\View\DefinitionView;
use whatwedo\SearchBundle\Repository\IndexRepository;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Extension\PaginationExtension;
use whatwedo\TableBundle\Extension\SortExtension;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\Table;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

abstract class AbstractDefinition implements DefinitionInterface, ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    protected TranslatorInterface $translator;

    /**
     * @var \whatwedo\CoreBundle\Action\Action[]
     */
    protected array $actions = [];

    protected array $batchActions = [];

    protected DefinitionBuilder $builder;

    protected Breadcrumbs $breadcrumbs;

    protected array $templates;

    protected string $formAccessorPrefix = '';

    /**
     * @var ExtensionInterface[]
     */
    protected array $extensions;

    public static function getEntity(): string
    {
        throw new \Exception('\whatwedo\CrudBundle\Definition\AbstractDefinition::getEntity must be implemented');
    }

    public static function getEntityTitle(): string
    {
        return 'wwd.' . static::getEntityAlias() . '.title';
    }

    public static function getEntityTitlePlural(): string
    {
        return 'wwd.' . static::getEntityAlias() . '.title_plural';
    }

    public function createEntity(Request $request): mixed
    {
        $className = static::getEntity();

        return new $className();
    }

    public function addAction(string $acronym, array $options = [], string $type = Action::class): static
    {
        if (! isset($options['label'])) {
            $options['label'] = sprintf('wwd.%s.action.%s', self::getEntityAlias(), $acronym);
        }
        $this->actions[$acronym] = new $type($acronym, $options);

        return $this;
    }

    public function removeAction(string $acronym): static
    {
        if (isset($this->actions[$acronym])) {
            unset($this->actions[$acronym]);
        }

        return $this;
    }

    public function getActions(): array
    {
        uasort(
            $this->actions,
            fn (\whatwedo\CoreBundle\Action\Action $a, \whatwedo\CoreBundle\Action\Action $b) => $a->getOption('priority') <=> $b->getOption('priority')
        );

        return $this->actions;
    }

    public function addBatchAction(string $acronym, array $options = [], string $type = Action::class): static
    {
        if (! isset($options['voter_attribute'])) {
            $options['voter_attribute'] = 'batch_action';
        }
        $this->batchActions[$acronym] = new $type($acronym, $options);

        return $this;
    }

    public function removeBatchAction(string $acronym): static
    {
        if (isset($this->batchActions[$acronym])) {
            unset($this->batchActions[$acronym]);
        }

        return $this;
    }

    public function getBatchActions(): array
    {
        return $this->batchActions;
    }

    public function configureView(DefinitionBuilder $builder, mixed $data): void
    {
    }

    public function configureTable(Table $table): void
    {
    }

    public function configureExport(Table $table): void
    {
        $this->configureTable($table);
    }

    public function getExportFilename(): string
    {
        $prefix = $this->translator->trans(static::getEntityTitlePlural());
        $suffix = date('Y-m-d\TH_i_s');

        return sprintf('%s_%s.xlsx', $prefix, $suffix);
    }

    public static function getAlias(): string
    {
        return str_replace(
            ['\\', '_definition', '_bundle'],
            ['_', '', ''],
            strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', static::class))
        );
    }

    public static function getEntityAlias(): string
    {
        return str_replace(
            ['\\', '_definition', '_bundle'],
            ['_', '', ''],
            strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', static::getEntity()))
        );
    }

    public function getTitle(mixed $entity = null, ?PageInterface $route = null): string
    {
        $add = $this->translator->trans('whatwedo_crud.add');
        $delete = $this->translator->trans('whatwedo_crud.delete');
        $edit = $this->translator->trans('whatwedo_crud.edit');

        return match ($route) {
            Page::INDEX => static::getEntityTitlePlural(),
            Page::DELETE => $delete,
            Page::CREATE => $add,
            Page::EDIT => $edit,
            Page::SHOW => static::getEntityTitle(),
            default => (string) $entity,
        };
    }

    public static function getCapabilities(): array
    {
        return [
            Page::INDEX,
            Page::SHOW,
            Page::RELOAD,
            Page::CREATE,
            Page::EDIT,
            Page::DELETE,
            Page::JSONSEARCH,
        ];
    }

    public static function hasCapability(PageInterface $page): bool
    {
        return in_array($page, static::getCapabilities(), true);
    }

    public static function getController(): string
    {
        return CrudController::class;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getRepository()->createQueryBuilder(static::getQueryAlias());
    }

    /**
     * returns the query alias to be used.
     *
     * @return string alias
     */
    public static function getQueryAlias(): string
    {
        return static::getAlias();
    }

    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    public function getBreadcrumbs(): Breadcrumbs
    {
        return $this->getExtension(BreadcrumbsExtension::class)->getBreadcrumbs();
    }

    public function getTemplateDirectory(): string
    {
        return '@whatwedoCrud/Crud/';
    }

    public function getLayout(): string
    {
        return '@whatwedoCrud/layout.html.twig';
    }

    public function getBuilder(): DefinitionBuilder
    {
        return $this->builder ?? throw new \RuntimeException('Please call DefinitionInterface::createView before accessing the builder');
    }

    public function createView(PageInterface $route, object $data = null): DefinitionView
    {
        $this->builder = $this->getDefinitionBuilder($data);

        return $this->container->get(DefinitionView::class)->create($this, $route, $data);
    }

    public function getJsonSearchUrl(string $entityClass): string
    {
        $clazz = new \ReflectionClass($entityClass);
        try {
            $instance = $clazz->newInstance();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not automatically detect relation definition for class ' . $entityClass . '. Please override getJsonSearchUrl() in ' . static::class . ' or make the Entity Constructor argument less.', previous: $e);
        }
        /** @var DefinitionInterface $definition */
        $definition = $this
            ->container->get(DefinitionManager::class)
            ->getDefinitionByEntity($instance)
        ;
        if ($definition::hasCapability(Page::JSONSEARCH)) {
            return $this->container->get(RouterInterface::class)
                ->generate($definition::getRoute(Page::JSONSEARCH))
            ;
        }
        $this->container->get(LoggerInterface::class)
            ->warning('you need to enable Page::JSONSEARCH Capability on the "' . get_class($definition) . '" definition to allow ajax filtering.')
        ;

        return '';
    }

    public function getLabelFor(?Table $table, string $property): string
    {
        if ($table instanceof Table) {
            foreach ($table->getColumns() as $column) {
                if ($column->getIdentifier() === $property) {
                    $label = $column->getOption('label');
                    if ($label) {
                        return $label;
                    }
                    break;
                }
            }
        }

        foreach ($this->getDefinitionBuilder()->getBlocks() as $block) {
            foreach ($block->getContents() as $content) {
                if ($content->getAcronym() === $property) {
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

    public function getContent(string $acronym): ?AbstractContent
    {
        foreach ($this->getDefinitionBuilder()->getBlocks() as $block) {
            foreach ($block->getContents() as $content) {
                if ($content->getAcronym() === $acronym) {
                    return $content;
                }
            }
        }

        return null;
    }

    public function getRedirect(PageInterface $routeFrom, ?object $entity = null): Response
    {
        return match ($routeFrom) {
            Page::CREATE, Page::EDIT => new RedirectResponse(
                $this->container->get(RouterInterface::class)->generate(static::getRoute(Page::SHOW), [
                    'id' => $entity->getId(),
                ])
            ),
            default => new RedirectResponse(
                $this->container->get(RouterInterface::class)->generate(static::getRoute(Page::INDEX))
            ),
        };
    }

    public static function getChildRouteAddition(): string
    {
        return static::getQueryAlias();
    }

    public function ajaxForm(object $entity, PageInterface $page): void
    {
    }

    public function jsonSearch(string $q): iterable
    {
        if (! $this->hasExtension(JsonSearchExtension::class)) {
            throw new \Exception('either install whatwedo search bundle or override your jsonSearch function in the definition.');
        }
        $ids = $this->container->get(IndexRepository::class)->search($q, static::getEntity());

        return $this->getRepository()
            ->createQueryBuilder('xxx')
            ->where('xxx.id IN (:ids)')->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * build breadcrumbs according to route.
     */
    public function buildBreadcrumbs(mixed $entity = null, ?PageInterface $route = null, ?Breadcrumbs $breadcrumbs = null): void
    {
        if (! $this->hasExtension(BreadcrumbsExtension::class)) {
            return;
        }
        if ($breadcrumbs === null) {
            $breadcrumbs = $this->getBreadcrumbs();
        }
        $property = $this->getParentDefinitionProperty($entity);
        if ($entity && $property) {
            $parentEntity = PropertyAccess::createPropertyAccessor()->getValue($entity, $property);
            if ($parentEntity) {
                $this
                    ->container->get(DefinitionManager::class)
                    ->getDefinitionByEntity($parentEntity)
                    ->buildBreadcrumbs($parentEntity, Page::SHOW, $breadcrumbs)
                ;
            }
        }

        if (in_array($route, [Page::INDEX, Page::EDIT, Page::SHOW, Page::CREATE], true)) {
            if (static::hasCapability(Page::INDEX)) {
                $this->getBreadcrumbs()->addRouteItem(static::getEntityTitlePlural(), static::getRoute(Page::INDEX));
            } else {
                $this->getBreadcrumbs()->addItem(static::getEntityTitlePlural());
            }
        }

        if (in_array($route, [Page::EDIT, Page::SHOW], true)) {
            if (static::hasCapability(Page::SHOW)) {
                $this->getBreadcrumbs()->addRouteItem($this->getTitle($entity, Page::SHOW), static::getRoute(Page::SHOW), [
                    'id' => $entity->getId(),
                ]);
            } else {
                $this->getBreadcrumbs()->addItem($this->getTitle($entity, Page::SHOW));
            }
        }

        if ($route === Page::EDIT) {
            if (static::hasCapability(Page::EDIT)) {
                $this->getBreadcrumbs()->addRouteItem($this->getTitle($entity, Page::EDIT), static::getRoute(Page::EDIT), [
                    'id' => $entity->getId(),
                ]);
            } else {
                $this->getBreadcrumbs()->addItem($this->getTitle($entity, Page::EDIT));
            }
        }

        if ($route === Page::CREATE) {
            if (static::hasCapability(Page::CREATE)) {
                $this->getBreadcrumbs()->addRouteItem($this->getTitle($entity, Page::CREATE), static::getRoute(Page::CREATE));
            } else {
                $this->getBreadcrumbs()->addItem($this->getTitle($entity, Page::CREATE));
            }
        }
    }

    public function getTemplateParameters(PageInterface $route, array $parameters = [], mixed $entity = null): array
    {
        return $parameters;
    }

    public function getExtension(string $extension): ExtensionInterface
    {
        if (! $this->hasExtension($extension)) {
            throw new \InvalidArgumentException(sprintf('Extension %s is not enabled. Please configure it first.', $extension));
        }

        return $this->extensions[$extension];
    }

    public function hasExtension(string $extension): bool
    {
        return isset($this->extensions[$extension]);
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->extensions[get_class($extension)] = $extension;
    }

    public static function supports(mixed $entity): bool
    {
        if (is_object($entity)) {
            $entity = ClassUtils::getClass($entity);
        }

        return is_a(ClassUtils::getRealClass($entity), static::getEntity(), true);
    }

    public static function getRoutePathPrefix(): string
    {
        return static::getAlias();
    }

    public static function getRoutePrefix(): string
    {
        return static::getAlias();
    }

    public static function getRoute(PageInterface $route): string
    {
        return static::getRoutePrefix() . '_' . $route->toRoute();
    }

    #[Required]
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function getParentDefinitionProperty(?object $data): ?string
    {
        return null;
    }

    public function getFormOptions(PageInterface $page, object $data): array
    {
        return [];
    }

    public static function getSubscribedServices(): array
    {
        return [
            BlockManager::class,
            EntityManagerInterface::class,
            DefinitionManager::class,
            DefinitionView::class,
            DefinitionBuilder::class,
            RouterInterface::class,
            IndexRepository::class,
            RequestStack::class,
            LoggerInterface::class,
            TableFactory::class,
        ];
    }

    public function getPage(): ?PageInterface
    {
        $exploded = explode('_', $this->container->get(RequestStack::class)->getCurrentRequest()->attributes->get('_route'));
        $route = end($exploded);
        foreach (Page::cases() as $page) {
            if ($page->toRoute() === $route) {
                return $page;
            }
        }

        return null;
    }

    public function getPageMode(): ?PageModeInterface
    {
        $page = PageMode::tryFrom($this->container->get(RequestStack::class)->getCurrentRequest()->get('mode', ''));
        return $page ?? PageMode::NORMAL;
    }

    public function getSubTable(object $entity): ?Table
    {
        $subQueryBuilder = $this->getSubTableQueryBuilder($entity);
        if ($subQueryBuilder === null) {
            return null;
        }

        /** @var TableFactory $tableFactory */
        $tableFactory = $this->container->get(TableFactory::class);
        $table = $tableFactory->create('sub_table_' . $entity->getId(), DoctrineDataLoader::class, [
            'dataloader_options' => [
                DoctrineDataLoader::OPT_QUERY_BUILDER => $subQueryBuilder,
            ],
        ]);

        /** @var DefinitionManager $definitionManager */
        $definitionManager = $this->container->get(DefinitionManager::class);
        $definition = $definitionManager->getDefinitionByClassName($this->getSubTableDefinition());

        $table->setOption(Table::OPT_DEFINITION, $definition);
        $table->setOption(Table::OPT_TITLE, $definition->getTitle(entity: $entity, route: Page::INDEX));
        $table->setOption(Table::OPT_THEME, '@whatwedoTable/tailwind_2_layout_sub_table.html.twig');
        $table->removeExtension(SortExtension::class);
        $table->removeExtension(PaginationExtension::class);
        $definition->configureTable($table);

        return $table;
    }

    public function getSubTableQueryBuilder(object $entity): ?QueryBuilder
    {
        return null;
    }

    public function getSubTableDefinition(): string
    {
        throw new \RuntimeException('You need to define the Definition for the SubTable!');
    }

    public function configureActions(mixed $data): void
    {
        if ($this::hasCapability(Page::INDEX)) {
            $this->addAction('index', [
                'label' => 'whatwedo_crud.index',
                'icon' => 'list',
                'visibility' => [Page::CREATE, Page::SHOW, Page::EDIT],
                'route' => static::getRoute(Page::INDEX),
                'attr' => [
                    'class' => 'whatwedo-crud-button--action-neutral',
                ],
                'voter_attribute' => Page::INDEX,
                'priority' => 10,
            ]);
        }

        if ($this::hasCapability(Page::CREATE)) {
            $this->addAction('create', [
                'label' => 'whatwedo_crud.add',
                'icon' => 'plus',
                'visibility' => [Page::INDEX],
                'route' => static::getRoute(Page::CREATE),
                'priority' => 20,
                'voter_attribute' => Page::CREATE,
            ]);
        }

        if ($data) {
            if ($this::hasCapability(Page::SHOW)) {
                $this->addAction('view', [
                    'label' => 'whatwedo_crud.view',
                    'icon' => 'eye',
                    'visibility' => [Page::EDIT],
                    'route' => static::getRoute(Page::SHOW),
                    'route_parameters' => [
                        'id' => $data->getId(),
                    ],
                    'priority' => 30,
                    'voter_attribute' => Page::SHOW,
                ]);
            }
            if ($this::hasCapability(Page::EDIT)) {
                $this->addAction('edit', [
                    'label' => 'whatwedo_crud.edit',
                    'icon' => 'pencil',
                    'visibility' => [Page::SHOW],
                    'route' => static::getRoute(Page::EDIT),
                    'route_parameters' => [
                        'id' => $data->getId(),
                    ],
                    'priority' => 40,
                    'voter_attribute' => Page::EDIT,
                ]);
            }

            if ($this::hasCapability(Page::DELETE)) {
                $this->addAction('delete', [
                    'label' => 'whatwedo_crud.delete',
                    'icon' => 'trash',
                    'visibility' => [Page::SHOW, Page::EDIT],
                    'route' => static::getRoute(Page::DELETE),
                    'route_parameters' => [
                        'id' => $data->getId(),
                    ],
                    'attr' => [
                        'class' => 'whatwedo-crud-button--action-danger',
                    ],
                    'priority' => 50,
                    'voter_attribute' => Page::DELETE,
                ], PostAction::class);
            }

            if ($this::hasCapability(Page::EDIT)) {
                $this->addAction('edit_submit', [
                    'label' => 'whatwedo_crud.save',
                    'icon' => 'check-lg',
                    'visibility' => [Page::EDIT],
                    'priority' => 20,
                    'attr' => [
                        'form' => 'crud_main_form',
                    ],
                    'voter_attribute' => Page::EDIT,
                ], SubmitAction::class);
            }

            if ($this::hasCapability(Page::CREATE)) {
                $this->addAction('create_submit', [
                    'label' => 'whatwedo_crud.add',
                    'icon' => 'check-lg',
                    'visibility' => [Page::CREATE],
                    'priority' => 20,
                    'attr' => [
                        'form' => 'crud_main_form',
                    ],
                    'voter_attribute' => Page::CREATE,
                ], SubmitAction::class);
            }
        }
    }

    public function configureTableActions(Table $table): void
    {
        $table->setOption('primary_link', function (object|array $row) {
            if (static::hasCapability(Page::SHOW)) {
                return $this->container->get(RouterInterface::class)->generate(
                    static::getRoute(Page::SHOW),
                    [
                        'id' => $row->getId(),
                    ]
                );
            }

            return null;
        });

        if ($this::hasCapability(Page::SHOW)) {
            $table->addAction('show', [
                'label' => 'whatwedo_crud.view',
                'icon' => 'eye',
                'route' => static::getRoute(Page::SHOW),
                'route_parameters' => fn ($row) => [
                    'id' => $row->getId(),
                ],
                'priority' => 100,
                'voter_attribute' => Page::SHOW,
            ]);
        }

        if ($this::hasCapability(Page::EDIT)) {
            $table->addAction('edit', [
                'label' => 'whatwedo_crud.edit',
                'icon' => 'pencil',
                'route' => static::getRoute(Page::EDIT),
                'route_parameters' => fn ($row) => [
                    'id' => $row->getId(),
                ],
                'priority' => 50,
                'voter_attribute' => Page::EDIT,
            ]);
        }

        if ($this::hasCapability(Page::DELETE)) {
            $table->addAction('delete', [
                'label' => 'whatwedo_crud.delete',
                'icon' => 'trash',
                'route' => static::getRoute(Page::DELETE),
                'route_parameters' => fn ($row) => [
                    'id' => $row->getId(),
                ],
                'priority' => 500,
                'voter_attribute' => Page::DELETE,
            ], PostAction::class);
        }
    }

    public function configureFilters(Table $table): void
    {
        if ($table->hasExtension(FilterExtension::class)) {
            $table->getFilterExtension()
                ->addFiltersAutomatically(
                    $table,
                    [$this, 'getLabelFor'],
                    [$this, 'getJsonSearchUrl'],
                );
        }
    }

    public function getFormAccessorPrefix(): string
    {
        return $this->formAccessorPrefix;
    }

    public function setFormAccessorPrefix(string $formAccessorPrefix): void
    {
        $this->formAccessorPrefix = $formAccessorPrefix;
    }

    protected function getDefinitionBuilder(object|array|null $data = null): DefinitionBuilder
    {
        static $cache;

        if ($cache === null || $data !== null) {
            $builder = $this->container->get(DefinitionBuilder::class);
            $builder->setDefinition($this);
            $this->configureActions($data);
            $this->configureView($builder, $data);

            if ($data === null) {
                $cache = $builder;
            } else {
                return $builder;
            }
        }

        return $cache;
    }

    protected function getRepository(): ObjectRepository
    {
        return $this->container->get(EntityManagerInterface::class)->getRepository(static::getEntity());
    }
}
