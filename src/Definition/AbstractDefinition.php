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
use whatwedo\CrudBundle\Extension\BreadcrumbsExtension;
use whatwedo\CrudBundle\Extension\ExtensionInterface;
use whatwedo\CrudBundle\Extension\JsonSearchExtension;
use whatwedo\CrudBundle\Manager\BlockManager;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\View\DefinitionView;
use whatwedo\SearchBundle\Repository\IndexRepository;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\Table;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

abstract class AbstractDefinition implements DefinitionInterface, ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    protected TranslatorInterface $translator;

    protected array $actions = [];

    protected array $batchActions = [];

    /**
     * TODO: is this required?
     */
    protected DefinitionBuilder $builder;

    protected Breadcrumbs $breadcrumbs;

    protected array $templates;

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

    public function createEntity(Request $request)
    {
        $className = static::getEntity();

        return new $className();
    }

    public function addAction(string $acronym, array $options = [], $type = Action::class): static
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
        return $this->actions;
    }

    public function addBatchAction(string $acronym, array $options = [], $type = Action::class): static
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

    public function configureView(DefinitionBuilder $builder, $data): void
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
                        'class' => 'whatwedo-crud-button--action-warning',
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
                    'priority' => 60,
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
                    'priority' => 60,
                    'attr' => [
                        'form' => 'crud_main_form',
                    ],
                    'voter_attribute' => Page::CREATE,
                ], SubmitAction::class);
            }
        }
    }

    public function configureTable(Table $table): void
    {
        $table->setOption('definition', $this);
        $table->setOption('title', $this->getTitle(route: Page::INDEX));
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

        if ($table->hasExtension(FilterExtension::class)) {
            $table->getFilterExtension()
                ->addFiltersAutomatically(
                    $table,
                    [$this, 'getLabelFor'],
                    [$this, 'getJsonSearchUrl'],
                );
        }
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

    public function getTitle($entity = null, ?Page $route = null): string
    {
        $title = $this->translator->trans(static::getEntityTitle());
        $add = $this->translator->trans('whatwedo_crud.add');
        $delete = $this->translator->trans('whatwedo_crud.delete');
        $edit = $this->translator->trans('whatwedo_crud.edit');

        return match ($route) {
            Page::INDEX => static::getEntityTitlePlural(),
            Page::DELETE => $entity . ' ' . $delete,
            Page::CREATE => $title . ' ' . $add,
            Page::EDIT => '"' . (string) $entity . '" ' . $edit,
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

    public function createView(Page $route, object $data = null): DefinitionView
    {
        $this->builder = $this->getDefinitionBuilder($data);

        return $this->container->get(DefinitionView::class)->create($this, $route, $data);
    }

    public function getJsonSearchUrl($entityClass)
    {
        /** @var DefinitionInterface $definition */
        $definition = $this
            ->container->get(DefinitionManager::class)
            ->getDefinitionByEntityClass($entityClass)
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

    /**
     * @param DoctrineTable $table
     * @param               $property
     */
    public function getLabelFor($table, $property): string
    {
        if ($table instanceof DoctrineTable) {
            foreach ($table->getColumns() as $column) {
                if ($column->getAcronym() === $property) {
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

    public function getRedirect(Page $routeFrom, ?object $entity = null): Response
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

    public function ajaxForm(object $entity, Page $page): void
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
     *
     * @param object|null $entity
     * @param string|null $route
     */
    public function buildBreadcrumbs($entity = null, $route = null, ?Breadcrumbs $breadcrumbs = null): void
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

        if (in_array($route, [Page::INDEX, Page::EDIT, Page::SHOW], true)) {
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
    }

    public function getTemplateParameters(Page $route, array $parameters = [], $entity = null): array
    {
        return $parameters;
    }

    public function getExtension($extension): ExtensionInterface
    {
        if (! $this->hasExtension($extension)) {
            throw new \InvalidArgumentException(sprintf('Extension %s is not enabled. Please configure it first.', $extension));
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

    public static function supports($entity): bool
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

    public static function getRoute(Page $route): string
    {
        return static::getRoutePrefix() . '_' . $route->toRoute();
    }

    /**
     * @required
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @required
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function getParentDefinitionProperty(?object $data): ?string
    {
        return null;
    }

    public function getFormOptions(Page $page, object $data): array
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
        ];
    }

    public function getPage(): ?Page
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

    protected function getDefinitionBuilder(object|array|null $data = null): DefinitionBuilder
    {
        static $cache;

        if ($cache === null || $data !== null) {
            $builder = $this->container->get(DefinitionBuilder::class);
            $builder->setDefinition($this);
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
