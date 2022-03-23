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

namespace whatwedo\CrudBundle\Block;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Collection\ContentCollection;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\BlockManager;

#[Autoconfigure(tags: ['whatwedo_crud.block'])]
class BlockBlock extends Block
{

    protected BlockCollection $blocks;

    public function __construct()
    {
        $this->blocks = new BlockCollection();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('block_prefix', StringUtil::fqcnToBlockPrefix(static::class));
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            BlockManager::class,
        ]);
    }

    public function getBlocks(?Page $page = null): BlockCollection
    {
        return $page
            ? $this->blocks->filterVisibility($page)
            : $this->blocks
        ;
    }

    public function getContents(?Page $page = null): ContentCollection
    {
        $contentCollection = new ContentCollection();
        foreach ($this->blocks as $block) {
            $contentCollection->addAll($block->getContents());
        }
        return $contentCollection;
    }

    public function addBlock(string $acronym, ?string $type = null, array $options = [], ?int $position = null): Block
    {
        $element = $this->container->get(BlockManager::class)->getBlock($type ?? Block::class);
        $element->setDefinition($this->getDefinition());
        $element->setAcronym($acronym);
        if (! isset($options['label'])) {
            $options['label'] = sprintf('wwd.%s.block_block.%s', $this->definition::getEntityAlias(), $acronym);
        }
        $element->setOptions($options);
        $this->blocks->set($acronym, $element, $position);
        return $element;
    }

    public function addContent(string $acronym, ?string $type = null, array $options = [], ?int $position = null): static
    {
        throw new \Exception('cannot be used in BlockBlock');
    }

}
