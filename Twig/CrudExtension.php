<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class CrudExtension extends AbstractExtension
{

    public function __construct(
        protected Environment $environment,
        protected DefinitionManager $definitionManager
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wwd_crud_render_breadcrumbs', [$this, 'renderBreadcrumbs'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('wwd_crud_entity_alias', fn ($entityOrClass) => $this->getEntityAlias($entityOrClass)),
        ];
    }

    public function renderBreadcrumbs(array $options)
    {
        $fn = $this->environment->getFunction('wo_render_breadcrumbs');
        if ($fn !== null) {
            return $fn->getCallable()($options);
        }
        return '';
    }

    public function getEntityAlias($entityOrClass) {

        $defnition = $this->definitionManager->getDefinitionByEntity($entityOrClass);

        return $defnition::getEntityAlias();
    }
}
