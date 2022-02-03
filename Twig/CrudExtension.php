<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

class CrudExtension extends AbstractExtension
{

    public function __construct(protected Environment $environment)
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

    public function renderBreadcrumbs(array $options)
    {
        $fn = $this->environment->getFunction('wo_render_breadcrumbs');
        if ($fn !== null) {
            return $fn->getCallable()($options);
        }
        return '';
    }
}
