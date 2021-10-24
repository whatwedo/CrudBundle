<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TemplateWrapper;

class CrudExtension extends AbstractExtension
{
    private Environment $environment;

    private ?TemplateWrapper $template = null;

    public function getFunctions(): array
    {
        $options = [
            'needs_environment' => true,
            'needs_context' => true,
            'is_safe' => ['html'],
            'is_safe_callback' => true,
            'blockName' => 'blockName',
        ];

        return [
            //new TwigFunction('crud_index', fn (Environment $environment, $context, DefinitionViewInterface $view) => $this->crudRender(Page::INDEX, $environment, $context, $view), $options),
            //new TwigFunction('crud_create', fn (Environment $environment, $context, DefinitionViewInterface $view) => $this->crudRender(Page::CREATE, $environment, $context, $view), $options),
            //new TwigFunction('crud_show', fn (Environment $environment, $context, DefinitionViewInterface $view) => $this->crudRender(Page::SHOW, $environment, $context, $view), $options),
            //new TwigFunction('crud_edit', fn (Environment $environment, $context, DefinitionViewInterface $view) => $this->crudRender(Page::EDIT, $environment, $context, $view), $options),
            //new TwigFunction('crud_block', fn (Environment $environment, $context, DefinitionViewInterface $view, Block $block) => $this->crudBlock($environment, $context, $view, $block), $options),
            //new TwigFunction('crud_content_row', fn (Environment $environment, $context, DefinitionViewInterface $view, ContentInterface $content) => $this->crudContentRow($environment, $context, $view, $content), $options),
//
            //new TwigFunction('crud_table', fn (Environment $environment, $context, Table $table) => $this->crudTable($environment, $context, $table), $options),
            //new TwigFunction('crud_table_header_cell', fn (Environment $environment, $context, ColumnInterface $column) => $this->crudTableHeaderCell($environment, $context, $column), $options),
            //new TwigFunction('crud_table_content_cell', fn (Environment $environment, $context, ColumnReflection $column, RowColumnIterator $row) => $this->crudTableContentCell($environment, $context, $column->getColumn(), $row), $options),
        ];
    }


/*
    public function crudRender(string $mode, Environment $environment, $context, DefinitionViewInterface $view)
    {
        $this->template = $this->getTemplate($view->getLayoutFile(), $environment);
        if (Page::EDIT === $mode
            || Page::CREATE === $mode
        ) {
            $context['form'] = $view->getCreateForm()->createView();
        }

        $context['renderMode'] = $mode;
        $renderedBlock = $this->template->renderBlock($mode, $context);

        return $renderedBlock;
    }

    public function crudBlock(Environment $environment, $context, DefinitionViewInterface $view, Block $block)
    {
        $blockName = $this->getBlockName($block->getBlockPrefix(), '', 'block');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $block->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);
    }

    public function crudContentRow(Environment $environment, $context, DefinitionViewInterface $view, ContentInterface $content)
    {
        $blockName = $this->getBlockName($content->getBlockPrefix(), 'content', $context['renderMode'].'_'.'row');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $content->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);
    }

    public function crudTable(Environment $environment, $context, Table $table)
    {
        $this->template = $this->getTemplate($context['view']->getLayoutFile(), $environment);
        $table->loadData();
        $context['table'] = $table;
        $blockName = $this->getBlockName($table->getBlockPrefix(), '', 'table');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $table->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);
    }

    public function crudTableHeaderCell(Environment $environment, $context, ColumnInterface $column)
    {
        $blockName = $this->getBlockName($column->getBlockPrefix(), '', 'table_header');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $column->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);
    }

    public function crudTableContentCell(Environment $environment, $context, ColumnInterface $column, RowColumnIterator $row)
    {
        $context['column'] = $column;
        $context['rowData'] = $row->getData();

        $blockName = $this->getBlockName($column->getBlockPrefix(), '', 'table_cell');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $column->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);
    }

    private function getBlockName(string $blockPrefix, string $default, ?string $suffix = null)
    {
        $blockNames = $this->template->getBlockNames();

        if ('' !== $blockPrefix) {
            $blockName = $blockPrefix;
            if ($suffix) {
                $blockName .= '_'.$suffix;
            }

            if (in_array($blockName, $blockNames, true)) {
                return $blockName;
            }
        }

        if ($default) {
            $defaultBlockName = $default;
            if ($suffix) {
                $defaultBlockName .= '_'.$suffix;
            }
        } else {
            $defaultBlockName = $suffix;
        }

        if (! in_array($defaultBlockName, $blockNames, true)) {
            throw new \Exception(sprintf('block "%s" not found in Template', $defaultBlockName));
        }

        return $defaultBlockName;
    }
*/
    private function getTemplate(string $layoutFile, Environment $environment): \Twig\TemplateWrapper
    {
        /** @var Twig\Environment environment */
        return $environment->load($layoutFile);
    }
}
