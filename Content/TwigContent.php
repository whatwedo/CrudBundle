<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
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

use Symfony\Component\OptionsResolver\OptionsResolver;

class TwigContent extends AbstractContent
{

    const INCLUDE_READ      = 1;
    const INCLUDE_CREATE    = 2;
    const INCLUDE_EDIT      = 4;

    public function isTwigContent()
    {
        return true;
    }

    public function render($row)
    {
    }

    public function setOption($key, $value)
    {
        if (isset($this->options[$key])) {
            $this->options[$key] = $value;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'template' => null,
            'show' => self::INCLUDE_CREATE | self::INCLUDE_EDIT | self::INCLUDE_READ,
            'parameters' => []
        ]);
    }

    public function getTemplate()
    {
        return $this->options['template'];
    }

    public function showOn($show)
    {
        $showMapping = [
            'read' => self::INCLUDE_READ,
            'create' => self::INCLUDE_CREATE,
            'edit' => self::INCLUDE_EDIT
        ];
        return $this->options['show'] & $showMapping[$show];
    }

    public function getParameters()
    {
        return $this->options['parameters'];
    }

    public function getLabel()
    {
        return false;
    }

    public function getViewClass()
    {
        return '';
    }

}