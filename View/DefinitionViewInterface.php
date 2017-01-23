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


namespace whatwedo\CrudBundle\View;
use Symfony\Component\Form\FormInterface;
use whatwedo\CrudBundle\Block\Block;
use whatwedo\CrudBundle\Collection\BlockCollection;
use whatwedo\CrudBundle\Definition\DefinitionInterface;


/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
interface DefinitionViewInterface
{
    /**
     * sets the definition
     *
     * @param DefinitionInterface $definition
     */
    public function setDefinition(DefinitionInterface $definition);

    /**
     * sets the definition
     *
     * @param object $data
     */
    public function setData($data);

    /**
     * @return object $data
     */
    public function getData();

    /**
     * sets the blocks from the builder
     *
     * @param BlockCollection $blocks
     */
    public function setBlocks(BlockCollection $blocks);

    /**
     * @return BlockCollection|Block[]
     */
    public function getBlocks();

    /**
     * renders show state
     * @return string
     */
    public function renderShow();

    /**
     * renders edit state
     *
     * @return string
     */
    public function renderEdit();

    /**
     * renders create state
     *
     * @return string
     */
    public function renderCreate();

    /**
     * returns an edit form
     *
     * @return FormInterface
     */
    public function getForm();

    /**
     * @param $data
     * @return boolean
     */
    public function allowDelete($data = null);
}
