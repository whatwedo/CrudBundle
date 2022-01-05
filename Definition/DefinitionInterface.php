<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Definition;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Extension\ExtensionInterface;
use whatwedo\CrudBundle\View\DefinitionView;
use whatwedo\TableBundle\Table\Table;

#[Autoconfigure(tags: ['whatwedo_crud.definition'])]
interface DefinitionInterface
{
    public static function supports($entity): bool;

    public static function getEntityTitle(): string;

    public static function getAlias(): string;

    public static function getPrefix(): string;

    public static function getRoutePathPrefix(): string;

    public static function getRoutePrefix(): string;

    public static function getRoute(Page $route): string;
    public function getBuilder(): DefinitionBuilder;
    /**
     * @param object|null $entity
     * @param object|null $route
     */
    public function getTitle($entity = null, ?Page $route = null): string;

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
     * @return string[] capabilities
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

    /**
     * returns a query builder.
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * table configuration.
     *
     * @return
     */
    public function configureTable(Table $table): void;

    /**
     * check if this definition has specific capability.
     *
     * @param $string
     */
    public static function hasCapability($string): bool;

    /**
     * get template directory of this definition.
     */
    public function getTemplateDirectory(): string;

    /**
     * returns all layouts to be consumed
     */
    public function getLayout(): string;

    /**
     * returns a view.
     *
     * @param $data
     */
    public function createView(Page $route, ?object $data = null): DefinitionView;

    /**
     * builds the interface.
     *
     * @param $data
     */
    public function configureView(DefinitionBuilder $builder, $data);

    public function getRedirect(Page $routeFrom, ?object $entity = null): Response;

    public function getExportAttributes(): array;

    public function getExportCallbacks(): array;

    public function getExportHeaders(): array;

    public function getExportOptions(): array;

    public function addAjaxOnChangeListener(): array;

    /**
     * @param $data
     *
     * @return \stdClass
     */
    public function ajaxOnDataChanged($data): ? \stdClass;

    public function addExtension(ExtensionInterface $extension): void;

    /**
     * @param string $extension FQDN of extension
     */
    public function hasExtension($extension): bool;

    /**
     * @param string $extension FQDN of extension
     */
    public function getExtension($extension): ExtensionInterface;

    /**
     * @param string $class
     * @param string $property
     *
     * @return \Symfony\Component\Form\Guess\Guess|\Symfony\Component\Form\Guess\TypeGuess|null
     *
    public function guessType($class, $property);
     * */
}
