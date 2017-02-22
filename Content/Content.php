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

namespace whatwedo\CrudBundle\Content;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CoreBundle\Formatter\DefaultFormatter;
use whatwedo\CrudBundle\Form\EntityHiddenType;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class Content extends AbstractContent implements EditableContentInterface
{

    public function isTable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function render($row)
    {
        $data = $this->getContents($row);

        $formatter = $this->options['formatter'];

        if (is_string($formatter)) {
            return (string) call_user_func($formatter . '::getHtml', $data);
        }

        if (is_callable($formatter)) {
            return (string) $formatter($data);
        }

        return (string) $data;
    }

    public function getFormType()
    {
        return $this->options['form_type'];
    }

    public function isReadOnly()
    {
        return $this->options['read_only'] ? true : false;
    }

    public function getFormOptions($options = [])
    {
        if (in_array($this->getFormType(), [EntityHiddenType::class, HiddenType::class])) {
            $this->options['label'] = false;
        }
        return array_merge($options, ['label' => $this->getLabel()], $this->options['form_options']);
    }

    public function getPreselectDefinition()
    {
        return $this->options['preselect_definition'];
    }

    public function getAutoFill()
    {
        return $this->options['auto_fill'];
    }

    public function setOption($key, $value)
    {
        if (isset($this->options[$key])) {
            $this->options[$key] = $value;
        }
    }

    public function getViewClass()
    {
        if (array_key_exists('class', $this->options['view_options'])) {
            return $this->options['view_options']['class'];
        }
        return '';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym,
            'callable' => null,
            'formatter' => DefaultFormatter::class,
            'read_only' => false,
            'form_type' => null,
            'form_options' => [],
            'preselect_definition' => null,
            'auto_fill' => null,
            'view_options' => []
        ]);
    }
}
