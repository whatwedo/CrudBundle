<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Definition;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use whatwedo\CrudBundle\Action\Action;
use whatwedo\CrudBundle\Action\DeleteAction;
use whatwedo\CrudBundle\Action\SubmitAction;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Controller\CrudController;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\VisibilityEnum;
use whatwedo\CrudBundle\Extension\BreadcrumbsExtension;
use whatwedo\CrudBundle\Extension\ExtensionInterface;
use whatwedo\CrudBundle\Manager\BlockManager;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\View\DefinitionView;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\Table;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

abstract class AbstractDefinition implements DefinitionInterface, ServiceSubscriberInterface
{
    protected ContainerInterface $container;
    protected array $actions = [];

    public const AJAX_LISTEN = 1;
    public const AJAX = 2;

    /**
     * TODO: is this required?
     */
    protected DefinitionBuilder $builder;
    protected Breadcrumbs $breadcrumbs;

    protected array $templates;

    public static function getEntity(): string
    {
       throw new \Exception('\whatwedo\CrudBundle\Definition\AbstractDefinition::getEntity must be implemented');
    }

    /**
     * @var ExtensionInterface[]
     */
    protected array $extensions;

    public static function getEntityTitle(): string
    {
            return static::getPrefix() .  '.title';
    }

    public function createEntity(Request $request)
    {
        $className = self::getEntity();
        return new $className();
    }

    public function addAction(string $acronym, array $options = [], $type = Action::class): static
    {
        $this->actions[$acronym] = new $type($acronym, $options);

        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function configureView(DefinitionBuilder $builder, $data): void
    {
        $this->addAction('index', [
            'label' => 'Übersicht',
            'icon' => 'list',
            'visibility' => [Page::CREATE, Page::SHOW, Page::EDIT],
            'route' => static::getRoute(Page::INDEX),
            'priority' => 10,
        ]);
        $this->addAction('create', [
            'label' => 'Hinzufügen',
            'icon' => 'plus',
            'visibility' => [Page::INDEX],
            'route' => static::getRoute(Page::CREATE),
            'priority' => 20,
        ]);

        if ($data) {
            $this->addAction('view', [
                'label' => 'Ansehen',
                'icon' => 'eye',
                'visibility' => [Page::EDIT],
                'route' => static::getRoute(Page::SHOW),
                'route_parameters' => ['id' => $data->getId()],
                'priority' => 30,
            ]);
            $this->addAction('edit', [
                'label' => 'Bearbeiten',
                'icon' => 'pencil',
                'visibility' => [Page::SHOW],
                'route' => static::getRoute(Page::EDIT),
                'route_parameters' => ['id' => $data->getId()],
                'priority' => 40,
            ]);
            $this->addAction('delete', [
                'label' => 'Löschen',
                'icon' => 'trash',
                'visibility' => [Page::SHOW, Page::EDIT],
                'route' => static::getRoute(Page::DELETE),
                'route_parameters' => ['id' => $data->getId()],
                'priority' => 50,
            ], DeleteAction::class);
            $this->addAction('edit_submit', [
                'label' => 'Speichern',
                'icon' => 'check-lg',
                'visibility' => [Page::EDIT],
                'priority' => 60,
                'attr' => [
                    'form' => 'crud_main_form',
                ]
            ], SubmitAction::class);
            $this->addAction('create_submit', [
                'label' => 'Hinzufügen',
                'icon' => 'check-lg',
                'visibility' => [Page::CREATE],
                'priority' => 60,
                'attr' => [
                    'form' => 'crud_main_form',
                ]
            ], SubmitAction::class);
        }
    }

    public function configureTable(Table $table): void
    {
        $table->setOption('definition', $this);
        $table->setOption('title', $this->getTitle(route: Page::INDEX));
        $table->setOption('primary_link', fn(object|array $row) => $this->container->get(RouterInterface::class)->generate(
            static::getRoute(Page::SHOW),
            ['id' => $row->getId()]
        ));

        $table->addAction('show', [
            'label' => 'Anzeigen',
            'icon' => 'eye',
            'route' => static::getRoute(Page::SHOW),
            'route_parameters' => fn($row) => ['id' => $row->getId()],
            'priority' => 100,
        ]);

        $table->addAction('edit', [
            'label' => 'Bearbeiten',
            'icon' => 'pencil',
            'route' => static::getRoute(Page::EDIT),
            'route_parameters' => fn($row) => ['id' => $row->getId()],
            'priority' => 50,
        ]);

        $table->addAction('delete', [
            'label' => 'Löschen',
            'icon' => 'trash',
            'route' => static::getRoute(Page::DELETE),
            'route_parameters' => fn($row) => ['id' => $row->getId()],
            'priority' => 500,
        ]);

        if ($table->hasExtension(FilterExtension::class)) {
            $table->getFilterExtension()
                ->addFiltersAutomatically(
                    $table,
                    [$this, 'getLabelFor']
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

    public static function getPrefix(): string
    {
        return StringUtil::fqcnToBlockPrefix(static::getEntity());
    }

    public function getTitle($entity = null, ?Page $route = null): string
    {
        return match ($route) {
            Page::INDEX => static::getEntityTitle(),
            Page::DELETE => $entity.' löschen',
            Page::CREATE => 'Hinzufügen',
            default => (string)$entity,
        };
    }

    public static function getCapabilities(): array
    {
        return Page::cases();
    }

    public static function hasCapability($string): bool
    {
        return in_array($string, static::getCapabilities(), true);
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
        return $this->builder ?? throw new RuntimeException('Please call DefinitionInterface::createView before accessing the builder');
    }

    public function createView(Page $route, object $data = null): DefinitionView
    {
        $this->builder = $this->getDefinitionBuilder($data);

        return $this->container->get(DefinitionView::class)->create($this, $route, $data);
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
        return match($routeFrom) {
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
                'delimiter' => ';',
                'enclosure' => '"',
                'escapeChar' => '\\',
                'keySeparator' => '.',
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
     * build breadcrumbs according to route.
     *
     * @param object|null $entity
     * @param string|null $route
     */
    public function buildBreadcrumbs($entity = null, $route = null): void
    {
        if (! $this->hasExtension(BreadcrumbsExtension::class)) {
            return;
        }

        if (static::hasCapability(Page::INDEX)) {
            $this->getBreadcrumbs()->addRouteItem(static::getEntityTitle(), static::getRoute(Page::INDEX), $this->getIndexBreadcrumbParameters([], $entity));
        } else {
            $this->getBreadcrumbs()->addItem(static::getEntityTitle());
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
            $entity = get_class($entity);
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
        return static::getRoutePrefix().'_'.$route->toRoute();
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

    /**
     * @required
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
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
        ];
    }
}
