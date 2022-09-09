<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Definition;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\Extension\ExtensionInterface;
use whatwedo\CrudBundle\View\DefinitionView;
use whatwedo\TableBundle\Table\Table;

#[Autoconfigure(tags: ['whatwedo_crud.definition'])]
interface DefinitionInterface
{
    public static function supports($entity): bool;

    public static function getEntityTitle(): string;

    public static function getEntityTitlePlural(): string;

    public static function getAlias(): string;

    public static function hasCapability(PageInterface $page): bool;

    public static function getEntityAlias(): string;

    public static function getRoutePathPrefix(): string;

    public static function getRoutePrefix(): string;

    public static function getRoute(PageInterface $route): string;

    public function getBuilder(): DefinitionBuilder;

    public function getTitle($entity = null, ?PageInterface $route = null): string;

    /**
     * returns capabilities of this definition.
     *
     * Available Options:
     * - list
     * - show
     * - create
     * - edit
     * - delete
     * - batch
     *
     * @return PageInterface[] capabilities
     */
    public static function getCapabilities(): array;

    public function getActions(): array;

    /**
     * returns FQDN of the controller.
     */
    public static function getController(): string;

    /**
     * returns the fqdn of the entity.
     *
     * @return string fqdn of the entity
     */
    public static function getEntity(): string;

    /**
     * returns the query alias to be used.
     *
     * @return string alias
     */
    public static function getQueryAlias(): string;

    public function createEntity(Request $request);

    /**
     * returns a query builder.
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * table configuration.
     */
    public function configureTable(Table $table): void;

    /**
     * table configuration.
     */
    public function configureTableActions(Table $table): void;

    /**
     * table export configuration.
     */
    public function configureExport(Table $table);

    /**
     * defines the export file name.
     */
    public function getExportFilename(): string;

    /**
     * check if this definition has specific capability.
    /**
     * get template directory of this definition.
     */
    public function getTemplateDirectory(): string;

    /**
     * returns all layouts to be consumed.
     */
    public function getLayout(): string;

    /**
     * returns a view.
     */
    public function createView(PageInterface $route, ?object $data = null): DefinitionView;

    /**
     * builds the interface.
     */
    public function configureView(DefinitionBuilder $builder, object $data);

    /**
     * configure Actions.
     */
    public function configureActions(object $data);

    public function getRedirect(PageInterface $routeFrom, ?object $entity = null): Response;

    public function ajaxForm(object $entity, PageInterface $page): void;

    public function hasExtension($extension): bool;

    public function getExtension($extension): ExtensionInterface;

    public function getParentDefinitionProperty(?object $data): ?string;

    public function jsonSearch(string $q): iterable;

    public function getPage(): ?PageInterface;

    public function getBatchActions(): array;

    public function getFormOptions(PageInterface $page, object $data): array;
}
