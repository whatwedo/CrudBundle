<?php

namespace whatwedo\CrudBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Node\Expression\FunctionExpression;
use Twig\TwigFilter;
use Twig\TwigFunction;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Content\ContentInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\View\DefinitionViewInterface;
use whatwedo\TableBundle\Iterator\RowColumnIterator;
use whatwedo\TableBundle\Table\Table;
use whatwedo\TableBundle\Table\ColumnInterface;
use whatwedo\TableBundle\Table\ColumnReflection;

class CrudExtension extends AbstractExtension
{

    /**
     * @var Environment
     */
    private Environment $environment;
    private string $templateFile = '';

    public function setTemplateFile(string $templateFile): void
    {
        $this->templateFile = $templateFile;
    }

    public function getFunctions(): array
    {
        $options = [
            'needs_environment' => true,
            'needs_context' => true,
            'is_safe' => ['html'],
            'is_safe_callback' => true,
            'blockName' => 'blockName'
        ];
        return [
            new TwigFunction( 'crud_create', fn (Environment $environment, $context,  DefinitionViewInterface $view) => $this->crudRender( RouteEnum::CREATE, $environment, $context, $view), $options ),
            new TwigFunction( 'crud_show', fn (Environment $environment, $context,  DefinitionViewInterface $view) => $this->crudRender( RouteEnum::SHOW, $environment, $context, $view), $options ),
            new TwigFunction( 'crud_edit', fn (Environment $environment, $context,  DefinitionViewInterface $view) => $this->crudRender( RouteEnum::EDIT, $environment, $context, $view), $options ),
            new TwigFunction( 'crud_block',  fn (Environment $environment, $context,  DefinitionViewInterface $view, Block $block) => $this->crudBlock($environment, $context, $view, $block), $options ),
            new TwigFunction( 'crud_content_row', fn (Environment $environment, $context,  DefinitionViewInterface $view, ContentInterface $content, string $blockKey) => $this->crudContentRow($environment, $context, $view, $content, $blockKey), $options ),

            new TwigFunction( 'crud_table',  fn (Environment $environment, $context, Table $table) => $this->crudTable($environment, $context, $table), $options ),
            new TwigFunction( 'crud_table_header_cell',  fn (Environment $environment, $context, ColumnInterface $column) => $this->crudTableHeaderCell($environment, $context, $column), $options ),
            new TwigFunction( 'crud_table_content_cell', fn (Environment $environment, $context, ColumnReflection $column, RowColumnIterator $row) => $this->crudTableContentCell($environment, $context, $column->getColumn(), $row), $options ),
        ];
    }

    public function crudRender(string $mode, Environment $environment, $context,  DefinitionViewInterface $view)
    {
        $template = $this->getTemplate($environment);
        if ($mode === RouteEnum::EDIT
            || $mode === RouteEnum::CREATE
        ) {
            $context['form'] = $view->getCreateForm()->createView();
        }

        $context['renderMode'] = $mode;
        $renderedBlock = $template->renderBlock($mode, $context);
        return $renderedBlock;
    }

    public function crudBlock(Environment $environment, $context,  DefinitionViewInterface $view, Block $block)
    {
        $template = $this->getTemplate($environment);
        $blockName = $this->getBlockName($block->getBlockPrefix(),'block');

        return $template->renderBlock($blockName, $context);
    }

    public function crudContentRow(Environment $environment, $context,  DefinitionViewInterface $view, ContentInterface $content, string $blockKey)
    {
        $template = $this->getTemplate($environment);
        $blockName = $this->getBlockName($content->getBlockPrefix(), 'content' , $context['renderMode'] . '_' . 'row');

        return $template->renderBlock($blockName, $context);
    }

    public function crudTable(Environment $environment, $context, Table $table)
    {
        $this->environment = $environment;
        $table->loadData();

        $template = $environment->load($this->templateFile);

        $context['renderMode'] = RouteEnum::INDEX;
        $context['table'] = $table;

        $blockName = $this->getBlockName($table->getBlockPrefix(), '' , 'table');

        return $template->renderBlock($blockName, $context);
    }

    public function crudTableHeaderCell(Environment $environment, $context, ColumnInterface $column)
    {
        $template = $this->getTemplate($environment);
        // TODO: renderMode?
        $context['renderMode'] = RouteEnum::INDEX;
        $blockName = $this->getBlockName($column->getBlockPrefix(), '' , 'table_header');
        $renderedBlock = $template->renderBlock($blockName, $context);
        return $renderedBlock;
    }

    public function crudTableContentCell(Environment $environment, $context, ColumnInterface $column, RowColumnIterator $row)
    {
        $template = $this->getTemplate($environment);

        $context['renderMode'] = RouteEnum::INDEX;

        $context['column'] = $column;
        $context['rowData'] = $row->getData();
        $blockPrefix = $column->getBlockPrefix();
        $blockName = $this->getBlockName($column->getBlockPrefix(), '' , 'table_cell');

        $renderedBlock = $template->renderBlock($blockName, $context);
        return $renderedBlock;
    }

    private function getBlockName(string $blockPrefix, string $default, ?string $suffix = null)
    {
        $template = $this->environment->load($this->templateFile);
        $blockNames = $template->getBlockNames();

        if ($blockPrefix != '') {
            $blockName = $blockPrefix;
            if ($suffix) {
                $blockName .= '_' . $suffix;
            }

            if (in_array($blockName, $blockNames)) {
                return $blockName;
            }
        }

        if ($default) {
            $defaultBlockName = $default;
            if ($suffix) {
                $defaultBlockName .= '_' . $suffix;
            }
        } else {
            $defaultBlockName = $suffix;
        }


        if (!in_array($defaultBlockName, $blockNames)) {
            throw new \Exception(sprintf('block %s not found in Template', $defaultBlockName));
        }

        return $defaultBlockName;
    }

    /**
     * @param Environment $environment
     * @return \Twig\TemplateWrapper
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getTemplate(Environment $environment): \Twig\TemplateWrapper
    {
        $this->environment = $environment;
        return $environment->load($this->templateFile);
    }

}
