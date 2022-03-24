<?php
/*
 * Copyright (c) 2022, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\CrudBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use whatwedo\CoreBundle\Action\Action;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Block\DefinitionBlock;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\Table;

class CrudRenderExtension extends AbstractExtension
{

    public function __construct(protected DefinitionManager $definitionManager, protected Environment $twig)
    {
    }

    public function getFunctions(): array
    {
        $options = [
            'needs_context' => true,
            'is_safe' => ['html'],
            'is_safe_callback' => true,
        ];

        return [
            new TwigFunction('wwd_definition_block_render', fn ($context, DefinitionBlock $definitionBlock) => $this->renderDefinitionBlock($context, $definitionBlock), $options),
        ];
    }

    public function renderDefinitionBlock($context, DefinitionBlock $definitionBlock): string
    {
        $data = $definitionBlock->getData($context['view']->getData());
        if ($data === null) {
            return '';
        }
        $optionDefinition = $definitionBlock->getOption('definition');
        $optionBlock = $definitionBlock->getOption('block');
        if ($optionDefinition === null) {
            $definition = $this->definitionManager->getDefinitionByEntity($data);
        } else {
            $definition = $this->definitionManager->getDefinitionByClassName($optionDefinition);
        }
        $view = $definition->createView(Page::SHOW, $data);
        $block = $view->getBlocks(Page::SHOW)->filter(static fn (Block $block) => $block->getAcronym() === $optionBlock)->first();
        if ($block === false) {
            throw new BlockNotFoundException('Block "'.$optionBlock.'" does not exist in definition "'.get_class($definition).'".');
        }
        $template = $this->twig->load($definition->getTemplateDirectory().'show.html.twig');
        return $template->renderBlock('block_definition_single_block', [
            'view' => $view,
            'block' => $block,
        ]);
    }
}
