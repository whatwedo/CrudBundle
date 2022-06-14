<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Twig;

use Symfony\Component\Form\FormView;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use whatwedo\CoreBundle\Action\Action;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\View\DefinitionView;

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
            new TwigFunction('wwd_crud_render_block', fn ($context, Block $block, DefinitionView $view, PageInterface $page, ?FormView $form = null) => $this->renderBlock($context, $block, $view, $page, $form), $options),
            new TwigFunction('wwd_crud_render_content', fn ($context, $content, Block $block, DefinitionView $view, ?FormView $form = null) => $this->renderContent($context, $content, $block, $view, $form), $options),
            new TwigFunction('wwd_crud_render_action', fn ($context, Action $action, DefinitionView $view, ?FormView $form = null) => $this->renderAction($context, $action, $view), $options),
            new TwigFunction('wwd_crud_render_content_value', fn ($context, AbstractContent $content) => $this->renderContentValue($context, $content), $options),
        ];
    }

    protected function renderBlock($context, Block $block, DefinitionView $view, PageInterface $page, ?FormView $form): string
    {
        $template = $this->environment->load($view->getDefinition()->getLayout());

        $blockName = $block->getOption('block_prefix');

        $renderContext = [
            'view' => $view,
            'block' => $block,
            'form' => $form,
            'page' => $page,
        ];

        return $template->renderBlock(
            $blockName,
            $renderContext
        );
    }

    protected function renderContent($context, $content, Block $block, DefinitionView $view, ?FormView $form): string
    {
        $template = $this->environment->load($view->getDefinition()->getLayout());

        $blockName = $content->getBlockPrefix();

        $renderContext = [
            'view' => $view,
            'block' => $block,
            'content' => $content,
        ];

        if ($form) {
            $renderContext['form'] = $form;
        }

        return $template->renderBlock($blockName, $renderContext);
    }

    protected function renderAction($context, Action $action, DefinitionView $view): string
    {
        $template = $this->environment->load($view->getDefinition()->getLayout());

        $blockName = $action->getOption('block_prefix');

        $renderContext = [
            'action' => $action,
        ];

        return $template->renderBlock($blockName, $renderContext);
    }

    protected function renderContentValue($context, AbstractContent $content): string
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
