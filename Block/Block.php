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

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Content\RelationContent;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Enum\VisibilityEnum;
use whatwedo\CrudBundle\Manager\ContentManager;
use whatwedo\CrudBundle\Content\Content;
use whatwedo\CrudBundle\Content\ContentInterface;
use whatwedo\CrudBundle\Enum\BlockSizeEnum;
use whatwedo\CrudBundle\Traits\VisibilityTrait;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class Block
{

    use VisibilityTrait;

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
     * @var ContentManager
     */
    protected $contentManager;

    /**
     * @var DefinitionInterface
     */
    protected $definition;

    /**
     * @param string $acronym
     */
    public function setAcronym($acronym)
    {
        $this->acronym = $acronym;
    }

    /**
     * @return string
     */
    public function getAcronym()
    {
        return $this->acronym;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->options['label'];
    }

    /**
     * @return array
     */
    public function getAttr()
    {
        return $this->options['attr'];
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->options['size'];
    }

    /**
     * @return string
     */
    public function getShowVoterAttribute()
    {
        return $this->options['show_voter_attribute'];
    }

    /**
     * @return string
     */
    public function getEditVoterAttribute()
    {
        return $this->options['edit_voter_attribute'];
    }

    /**
     * @return string
     */
    public function getCreateVoterAttribute()
    {
        return $this->options['create_voter_attribute'];
    }

    /**
     * adds a new content to the block
     *
     * @param string $acronym acronym of the block
     * @param string|null $type type of the block (class name)
     * @param array $options configuration
     * @return $this
     */
    public function addContent($acronym, $type = null, $options = [])
    {
        $type = $this->getType($acronym, $type, $options);

        $this->elements[$acronym] = $this->contentManager->getContent($type);
        $this->elements[$acronym]->setDefinition($this->definition);
        $this->elements[$acronym]->setAcronym($acronym);
        $this->elements[$acronym]->setOptions($options);

        return $this;
    }

    private function getType($acronym, $type = null, $options = [])
    {
        if (!is_null($type)) {
            return $type;
        }
        $accessor = isset($options['accessor_path']) ? $options['accessor_path'] : $acronym;
        $typeGuess = $this->definition->guessType($this->definition->getEntity(), $accessor);
        $needRelationContent = [
            EntityType::class
        ];
        if (!is_null($typeGuess)
            && in_array($typeGuess->getType(), $needRelationContent)
            && $typeGuess->getOptions()['multiple']
            && $this->optionsCouldBeRelationContent($options)) {
            return RelationContent::class;
        } else {
            return Content::class;
        }
    }

    private function optionsCouldBeRelationContent($options = [])
    {
        $notAllowedOptions = [
            'form_options', 'formatter', 'callable', 'form_type', 'help', 'preselect_definition', 'auto_fill', 'attr'
        ];
        foreach ($notAllowedOptions as $notAllowedOption) {
            if (in_array($notAllowedOption, array_keys($options))) {
                return false;
            }
        }
        return true;
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
            'attr' => [],
            'size' => BlockSizeEnum::SMALL,
            'visibility' => VisibilityEnum::SHOW | VisibilityEnum::EDIT | VisibilityEnum::CREATE,
            'show_voter_attribute' => RouteEnum::SHOW,
            'edit_voter_attribute' => RouteEnum::EDIT,
            'create_voter_attribute' => RouteEnum::CREATE,
        ]);
    }

    /**
     * @return DefinitionInterface
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param DefinitionInterface $definition
     * @return $this
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * @return ContentManager
     */
    public function getContentManager()
    {
        return $this->contentManager;
    }

    /**
     * @param $contentManager
     * @return $this
     */
    public function setContentManager($contentManager)
    {
        $this->contentManager = $contentManager;

        return $this;
    }
}
