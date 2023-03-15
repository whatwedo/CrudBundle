<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudExtension extends AbstractExtension
{
    public function __construct(
        protected Environment $environment,
        protected DefinitionManager $definitionManager,
        protected UrlGeneratorInterface $urlGenerator,
        protected Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wwd_crud_render_breadcrumbs', [$this, 'renderBreadcrumbs'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('wwd_crud_entity_path', fn ($entityOrClass, PageInterface $page) => $this->getEntityPath($entityOrClass, $page)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wwd_crud_entity_alias', fn ($entityOrClass) => $this->getEntityAlias($entityOrClass)),
            new TwigFilter('wwd_crud_entity_has_definition', fn ($entityOrClass) => $this->hasDefinition($entityOrClass)),
        ];
    }

    public function renderBreadcrumbs(array $options): string
    {
        $fn = $this->environment->getFunction('wo_render_breadcrumbs');
        if ($fn !== null) {
            return $fn->getCallable()($options);
        }

        return '';
    }

    public function hasDefinition(mixed $entityOrClass): bool
    {
        try {
            $this->definitionManager->getDefinitionByEntity($entityOrClass);

            return true;
        } catch (\Exception $ex) {
        }

        return false;
    }

    public function getEntityAlias(mixed $entityOrClass): string
    {
        $defnition = $this->definitionManager->getDefinitionByEntity($entityOrClass);

        return $defnition::getEntityAlias();
    }

    public function getEntityPath(mixed $entityOrClass, PageInterface $page): string
    {
        $defnition = $this->definitionManager->getDefinitionByEntity($entityOrClass);

        $route = $defnition::getRoute($page);

        $routeOptions = [];
        if (
            is_object($entityOrClass)
            && method_exists($entityOrClass, 'getId')
            && ($page === Page::SHOW
            || $page === Page::EDIT
            || $page === Page::DELETE)
        ) {
            $routeOptions['id'] = $entityOrClass->getId();
        }

        return $this->urlGenerator->generate($route, $routeOptions);
    }
}
