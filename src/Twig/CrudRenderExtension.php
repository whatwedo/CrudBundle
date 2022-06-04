<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Content\AbstractContent;

class CrudRenderExtension extends AbstractExtension
{
    public function __construct(
        protected Environment $environment,
        protected FormatterManager $formatterManager,
    ) {
    }

    public function getFunctions(): array
    {
        $options = [
            'needs_context' => true,
            'is_safe' => ['html'],
            'is_safe_callback' => true,
            'blockName' => 'blockName',
        ];

        return [
            new TwigFunction('wwd_crud_render_block', fn ($context, Block $block) => $this->renderBlock($context, $block), $options),
            new TwigFunction('wwd_crud_render_content', fn ($context, AbstractContent $content, Block $block) => $this->renderContent($context, $content, $block), $options),
            new TwigFunction('wwd_crud_render_content_value', fn ($context, AbstractContent $content) => $this->renderContentItem($context, $content), $options),
        ];
    }

    protected function renderBlock($context, Block $block): string
    {
        return $this->environment->render(
            '/whatwedoCrud/includes/layout/_block.html.twig',
            [
                'view' => $context['view'],
                'block' => $block,
            ]
        );
    }

    protected function renderContent($context, AbstractContent $content, Block $block): string
    {
        $template = $this->environment->load('/whatwedoCrud/includes/layout/_content.html.twig');

        $blockName = $content->getBlockPrefix();

        return $template->renderBlock($blockName, [
            'view' => $context['view'],
            'block' => $block,
            'content' => $content,
        ]);
    }

    protected function renderContentItem($context, AbstractContent $content): string
    {
        $data = $content->getContents($context['view']->getData());
        $formatter = $content->getOption('formatter');
        $formatterOptions = $content->getOption('formatter_options');

        if (is_string($formatter)) {
            $formatterObj = $this->formatterManager->getFormatter($formatter);
            $formatterObj->processOptions($formatterOptions);

            return (string) $formatterObj->getHtml($data);
        }

        if (is_callable($formatter)) {
            return (string) $formatter($data);
        }

        return (string) $data;
    }
}
