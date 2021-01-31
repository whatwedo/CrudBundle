<?php

namespace whatwedo\CrudBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Node\Expression\FunctionExpression;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;
use whatwedo\CrudBundle\Action\IdentityableActionInterface;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Content\ContentInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\View\DefinitionViewInterface;
use whatwedo\TableBundle\Iterator\RowColumnIterator;
use whatwedo\TableBundle\Table\Table;
use whatwedo\TableBundle\Table\ColumnInterface;
use whatwedo\TableBundle\Table\ColumnReflection;
use whatwedo\CrudBundle\Action\IdentityAction;

class CrudExtension extends AbstractExtension
{

    /**
     * @var Environment
     */
    private Environment $environment;
    private ?TemplateWrapper $template = null;

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
            new TwigFunction( 'crud_actions', fn (Environment $environment, $context,  DefinitionViewInterface $view) => $this->crudActions( $environment, $context, $view), $options ),
            new TwigFunction( 'crud_index', fn (Environment $environment, $context,  DefinitionViewInterface $view) => $this->crudRender( RouteEnum::INDEX, $environment, $context, $view), $options ),
            new TwigFunction( 'crud_create', fn (Environment $environment, $context,  DefinitionViewInterface $view) => $this->crudRender( RouteEnum::CREATE, $environment, $context, $view), $options ),
            new TwigFunction( 'crud_show', fn (Environment $environment, $context,  DefinitionViewInterface $view) => $this->crudRender( RouteEnum::SHOW, $environment, $context, $view), $options ),
            new TwigFunction( 'crud_edit', fn (Environment $environment, $context,  DefinitionViewInterface $view) => $this->crudRender( RouteEnum::EDIT, $environment, $context, $view), $options ),
            new TwigFunction( 'crud_block',  fn (Environment $environment, $context,  DefinitionViewInterface $view, Block $block) => $this->crudBlock($environment, $context, $view, $block), $options ),
            new TwigFunction( 'crud_content_row', fn (Environment $environment, $context,  DefinitionViewInterface $view, ContentInterface $content) => $this->crudContentRow($environment, $context, $view, $content), $options ),

            new TwigFunction( 'crud_table',  fn (Environment $environment, $context, Table $table) => $this->crudTable($environment, $context, $table), $options ),
            new TwigFunction( 'crud_table_header_cell',  fn (Environment $environment, $context, ColumnInterface $column) => $this->crudTableHeaderCell($environment, $context, $column), $options ),
            new TwigFunction( 'crud_table_content_cell', fn (Environment $environment, $context, ColumnReflection $column, RowColumnIterator $row) => $this->crudTableContentCell($environment, $context, $column->getColumn(), $row), $options ),
        ];
    }

    public function crudActions(Environment $environment, $context,  DefinitionViewInterface $view)
    {
        $this->template = $this->getTemplate($view->getLayoutFile(), $environment);


        if (!isset($context['_route'])) {
            return 'not Actions';
        }

        $route = $context['_route'];

        $actions = $view->getDefinition()->getActions()[$route];


        $renderedBlock = '';
        foreach ($actions as $action) {
            $blockName = $this->getBlockName($action->getBlockPrefix(), '' , 'action');

            $action->setData($view->getData());
            if ($action instanceof IdentityableActionInterface) {
                $action->setRouteParameters(array_merge($action->getRouteParameters(), ['id' => $view->getData()->getId()]));
            }

            $context['action'] = $action;
            $renderedBlock .= $this->template->renderBlock($blockName, $context);
        }

        return $renderedBlock;
    }

    public function crudRender(string $mode, Environment $environment, $context,  DefinitionViewInterface $view)
    {
        $this->template = $this->getTemplate($view->getLayoutFile(), $environment);
        if ($mode === RouteEnum::EDIT
            || $mode === RouteEnum::CREATE
        ) {
            $context['form'] = $view->getCreateForm()->createView();
        }

        $context['renderMode'] = $mode;
        $renderedBlock = $this->template->renderBlock($mode, $context);
        return $renderedBlock;
    }

    public function crudBlock(Environment $environment, $context,  DefinitionViewInterface $view, Block $block)
    {
        $blockName = $this->getBlockName($block->getBlockPrefix(),'', 'block');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $block->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);
    }

    public function crudContentRow(Environment $environment, $context,  DefinitionViewInterface $view, ContentInterface $content)
    {
        $blockName = $this->getBlockName($content->getBlockPrefix(), 'content' , $context['renderMode'] . '_' . 'row');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $content->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);
    }

    public function crudTable(Environment $environment, $context, Table $table)
    {
        $this->template = $this->getTemplate($context['view']->getLayoutFile(), $environment);
        $table->loadData();
        $context['table'] = $table;
        $blockName = $this->getBlockName($table->getBlockPrefix(), '' , 'table');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $table->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);
    }

    public function crudTableHeaderCell(Environment $environment, $context, ColumnInterface $column)
    {
        $blockName = $this->getBlockName($column->getBlockPrefix(), '' , 'table_header');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $column->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);;
    }

    public function crudTableContentCell(Environment $environment, $context, ColumnInterface $column, RowColumnIterator $row)
    {
        $context['column'] = $column;
        $context['rowData'] = $row->getData();

        $blockName = $this->getBlockName($column->getBlockPrefix(), '' , 'table_cell');
        $context['blockName'] = $blockName;
        $context['blockPrefix'] = $column->getBlockPrefix();

        return $this->template->renderBlock($blockName, $context);;
    }

    private function getBlockName(string $blockPrefix, string $default, ?string $suffix = null)
    {
        $blockNames = $this->template->getBlockNames();

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
            throw new \Exception(sprintf('block "%s" not found in Template', $defaultBlockName));
        }

        return $defaultBlockName;
    }

    private function getTemplate(string $layoutFile, Environment $environment): \Twig\TemplateWrapper
    {
        /** @var Twig\Environment environment */
        return $environment->load($layoutFile);
    }

}
