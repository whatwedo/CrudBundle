<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
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
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Content\Content;
use whatwedo\CrudBundle\Content\ContentInterface;
use whatwedo\CrudBundle\Manager\DefinitionManager;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class Block
{
    const BLOCK_SIZE_SMALL = 'small';
    const BLOCK_SIZE_LARGE = 'large';

    /**
     * @var string block acronym
     */
    protected $acronym = '';

    /**
     * @var array block options
     */
    protected $options = [];

    /**
     * @var array containing content elements
     */
    protected $elements = [];

    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    /**
     * Block constructor.
     *
     * @param string $acronym
     * @param array $options
     */
    public function __construct($acronym, array $options = [])
    {
        $this->acronym = $acronym;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * @return string
     */
    public function getAcronym()
    {
        return $this->acronym;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->options['label'];
    }


    /**
     * @return string
     */
    public function getSize()
    {
        return $this->options['size'];
    }

    /**
     * adds a new content to the block
     *
     * @param string $acronym acronym of the block
     * @param string|null $type type of the block (class name)
     * @param array $options configuration
     * @return $this
     */
    public function addContent($acronym, $type = null, array $options = [])
    {
        if ($type === null) {
            $type = Content::class;
        }

        $this->elements[$acronym] = new $type($acronym, $options);
        $this->elements[$acronym]->setDefinitionManager($this->definitionManager);
        return $this;
    }

    /**
     * @return array|ContentInterface[]
     */
    public function getContents()
    {
        return $this->elements;
    }

    /**
     * @return ContentInterface|null
     */
    public function getContent($acronym)
    {
        return isset($this->elements[$acronym]) ? $this->elements[$acronym] : null;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => '',
            'size' => static::BLOCK_SIZE_SMALL,
        ]);
    }

    /**
     * @return DefinitionManager
     */
    public function getDefinitionManager()
    {
        return $this->definitionManager;
    }

    /**
     * @param DefinitionManager $definitionManager
     * @return Block
     */
    public function setDefinitionManager(DefinitionManager $definitionManager)
    {
        $this->definitionManager = $definitionManager;
        return $this;
    }
}
