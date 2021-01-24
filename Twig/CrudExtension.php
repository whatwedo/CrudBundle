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
use whatwedo\TableBundle\Table\Table;

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
            new TwigFunction( 'crud_create', [$this, 'crudCreate'], $options ),
            new TwigFunction( 'crud_show', [$this, 'crudShow'], $options ),
            new TwigFunction( 'crud_edit', [$this, 'crudEdit'], $options ),
            new TwigFunction( 'crud_block', [$this, 'crudBlock'], $options ),
            new TwigFunction( 'crud_content_row', [$this, 'crudContentRow'], $options ),

            new TwigFunction( 'crud_table', [$this, 'crudTable'], $options ),
            new TwigFunction( 'crud_table_header_cell', [$this, 'crudTableHeaderCell'], $options ),
            new TwigFunction( 'crud_table_content_cell', [$this, 'crudTableContentCell'], $options ),
        ];
    }

    public function crudCreate(Environment $environment, $context,  DefinitionViewInterface $view)
    {
        $this->environment = $environment;
        $template = $environment->load($this->templateFile);
        $context['form'] = $view->getCreateForm()->createView();

        // TODO: renderMode?
        $context['renderMode'] = RouteEnum::CREATE;
        $renderedBlock = $template->renderBlock('crud_create', $context);
        return $renderedBlock;
    }

    public function crudShow(Environment $environment, $context,  DefinitionViewInterface $view)
    {
        $this->environment = $environment;
        $template = $environment->load($this->templateFile);

        // TODO: renderMode?
        $context['renderMode'] = RouteEnum::SHOW;
        $renderedBlock = $template->renderBlock('crud_show', $context);
        return $renderedBlock;
    }

    public function crudEdit(Environment $environment, $context,  DefinitionViewInterface $view)
    {
        $this->environment = $environment;
        $template = $environment->load($this->templateFile);
        $context['form'] = $view->getEditForm()->createView();

        // TODO: renderMode?
        $context['renderMode'] = RouteEnum::EDIT;
        $renderedBlock = $template->renderBlock('crud_edit', $context);
        return $renderedBlock;
    }

    public function crudBlock(Environment $environment, $context,  DefinitionViewInterface $view, Block $block)
    {
        $this->environment = $environment;
        $template = $environment->load($this->templateFile);

        $blockName = $this->getBlockName($block->getBlockPrefix(),
            'block');

        $renderedBlock = $template->renderBlock($blockName, $context);

        return $renderedBlock;
    }

    public function crudContentRow(Environment $environment, $context,  DefinitionViewInterface $view, ContentInterface $content, string $blockKey)
    {
        $this->environment = $environment;
        $template = $environment->load($this->templateFile);

        $blockName = $this->getBlockName($content->getBlockPrefix(), 'content_'.$context['renderMode'] , 'row');

        $renderedBlock = $template->renderBlock($blockName, $context);

        return $renderedBlock;
    }

    public function crudTable(Environment $environment, $context, Table $table, string $tableKey = null)
    {
        $this->environment = $environment;
        $table->loadData();

        $template = $environment->load($this->templateFile);

        $context['renderMode'] = RouteEnum::INDEX;
        $context['table'] = $table;
        $blockNames = $template->getBlockNames($context);
        if (in_array(sprintf('crud_%s_table', $tableKey), $blockNames)) {
            $blockName = sprintf('crud_%s_table', $tableKey);
        } else {
            $blockName = 'crud_table';
        }

        $renderedBlock = $template->renderBlock($blockName, $context);
        return $renderedBlock;
    }

    public function crudTableHeaderCell(Environment $environment, $context)
    {
        $this->environment = $environment;
        $template = $environment->load($this->templateFile);
        // TODO: renderMode?
        $context['renderMode'] = RouteEnum::INDEX;
        $renderedBlock = $template->renderBlock('crud_table_header_cell', $context);
        return $renderedBlock;
    }

    public function crudTableContentCell(Environment $environment, $context)
    {
        $this->environment = $environment;
        $template = $environment->load($this->templateFile);
        // TODO: renderMode?
        $context['renderMode'] = RouteEnum::INDEX;
        $blockNames = $template->getBlockNames($context);

        if (in_array(sprintf('crud_table_content_%s_cell', $context['column']->getColumn()->getAcronym()), $blockNames)) {
            $blockName = sprintf('crud_table_content_%s_cell', $context['column']->getColumn()->getAcronym());
        } else {
            $blockName = 'crud_table_content_cell';
        }

        $renderedBlock = $template->renderBlock($blockName, $context);
        return $renderedBlock;
    }

    private function getBlockName(string $blockPrefix, string $default, ?string $suffix = null)
    {
        // todo: cache this
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

        $defaultBlockName = 'crud_' . $default;
        if ($suffix) {
            $defaultBlockName .= '_' . $suffix;
        }

        if (!in_array($defaultBlockName, $blockNames)) {
            throw new \Exception(sprintf('block %s not found in Template', $defaultBlockName));
        }


        return $defaultBlockName;
    }

}
