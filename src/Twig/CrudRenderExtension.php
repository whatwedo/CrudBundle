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
use whatwedo\CrudBundle\Block\DefinitionBlock;
use whatwedo\CrudBundle\Content\AbstractContent;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\PageInterface;
use whatwedo\CrudBundle\Exception\BlockNotFoundException;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\View\DefinitionView;

class CrudRenderExtension extends AbstractExtension
{
    public function __construct(
        protected Environment $environment,
        protected FormatterManager $formatterManager,
        protected DefinitionManager $definitionManager,
        protected Environment $twig
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
            new TwigFunction('wwd_definition_block_render', fn ($context, DefinitionBlock $definitionBlock) => $this->renderDefinitionBlock($context, $definitionBlock), $options),
            new TwigFunction('wwd_crud_render_content', fn ($context, $content, Block $block, DefinitionView $view, ?FormView $form = null) => $this->renderContent($context, $content, $block, $view, $form), $options),
            new TwigFunction('wwd_crud_render_action', fn ($context, Action $action, DefinitionView $view, ?FormView $form = null) => $this->renderAction($context, $action, $view), $options),
            new TwigFunction('wwd_crud_render_content_value', fn ($context, AbstractContent $content) => $this->renderContentValue($context, $content), $options),
        ];
    }

    public function renderDefinitionBlock(mixed $context, DefinitionBlock $definitionBlock): string
    {
        $data = $definitionBlock->getData($context['view']->getData());
        if ($data === null) {
            return '';
        }
        $route = $context['view']->getRoute();
        $optionBlock = $definitionBlock->getOption(DefinitionBlock::OPT_BLOCK);
        $definition = $definitionBlock->getReferencingDefinition($data);
        $view = $definition->createView($route, $data);
        $block = $view->getBlocks($route)->filter(static fn (Block $block) => $block->getAcronym() === $optionBlock)->first();
        if ($block === false) {
            throw new BlockNotFoundException('Block "' . $optionBlock . '" does not exist in definition "' . get_class($definition) . '".');
        }
        $templateFile = match ($route) {
            Page::EDIT => 'edit.html.twig',
            Page::CREATE => 'create.html.twig',
            default => 'show.html.twig',
        };
        $template = $this->twig->load($definition->getTemplateDirectory() . $templateFile);

        return $template->renderBlock('block_definition_single_block', [
            'view' => $view,
            'block' => $block,
            'form' => $context['form'] ?? null,
        ]);
    }

    protected function renderBlock(mixed $context, Block $block, DefinitionView $view, PageInterface $page, ?FormView $form): string
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

    protected function renderContent(mixed $context, AbstractContent $content, Block $block, DefinitionView $view, ?FormView $form): string
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

    protected function renderAction(mixed $context, Action $action, DefinitionView $view): string
    {
        $template = $this->environment->load($view->getDefinition()->getLayout());

        $blockName = $action->getOption('block_prefix');

        $renderContext = [
            'action' => $action,
        ];

        return $template->renderBlock($blockName, $renderContext);
    }

    protected function renderContentValue(mixed $context, AbstractContent $content): string
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
