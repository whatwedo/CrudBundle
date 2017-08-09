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

namespace whatwedo\CrudBundle\Builder;

use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Manager\BlockManager;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Exception\ElementNotFoundException;
use whatwedo\CrudBundle\Manager\DefinitionManager;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class DefinitionBuilder
{

    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    /**
     * @var BlockManager
     */
    protected $blockManager;

    /**
     * @var array
     */
    protected $definition = [];

    public function __construct(BlockManager $blockManager, DefinitionManager $definitionManager)
    {
        $this->blockManager = $blockManager;
        $this->definitionManager = $definitionManager;
    }

    /**
     * adds a new block to the definition
     *
     * @param string $acronym unique block acronym
     * @param array  $options options
     * @param string $type block type (class name)
     *
     * @return Block
     */
    public function addBlock($acronym, $type = Block::class, array $options = [])
    {
        if ($type === null) {
            $type = Block::class;
        }

        $this->definition[$acronym] = $this->blockManager->getBlock($type);
        $this->definition[$acronym]->setAcronym($acronym);
        $this->definition[$acronym]->setOptions($options);

        return $this->definition[$acronym];
    }

    /**
     * returns a formally created block
     *
     * @param string $acronym
     * @return Block
     * @throws ElementNotFoundException
     */
    public function getBlock($acronym)
    {
        if (!isset($this->definition[$acronym])) {
            throw new ElementNotFoundException(sprintf('Specified block "%s" not found.', $acronym));
        }

        return $this->definition[$acronym];
    }

    public function getBlocks()
    {
        return new BlockCollection($this->definition);
    }
}
