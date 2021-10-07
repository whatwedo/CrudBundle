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
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\CrudBundle\Form\Type\EntityHiddenType;

class Content extends AbstractContent implements EditableContentInterface
{
    /**
     * @var FormatterManager
     */
    protected $formatterManager;

    /**
     * Content constructor.
     */
    public function __construct(FormatterManager $formatterManager)
    {
        $this->formatterManager = $formatterManager;
    }

    public function render($row)
    {
        return (string) $this->formatData($this->getContents($row), $this->options['formatter'], $this->options['formatter_options']);
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->options['form_type'];
    }

    /**
     * @param array $options
     * @return array
     */
    public function getFormOptions($options = [])
    {
        // Override options for the EntityHiddenType and HiddenType
        if (in_array($this->getFormType(), [EntityHiddenType::class, HiddenType::class])) {
            $this->options['label'] = false;
        }

        // Override help option
        if (!isset($this->options['form_options']['help']) && $this->getHelp() != null) {
            $this->options['form_options']['help'] = $this->options['help'];
        }

        // Override label option
        if (!isset($this->options['form_options']['label']) && $this->getLabel() != null) {
            $this->options['form_options']['label'] = $this->options['label'];
        }

        // Override label
        return array_merge_recursive($options, $this->options['form_options']);
    }

    /**
     * @return string
     */
    public function getPreselectDefinition()
    {
        return $this->options['preselect_definition'];
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return $this->options['help'];
    }

    /**
     * @param $key
     * @param $value
     */
    public function setOption($key, $value)
    {
        if (isset($this->options[$key])) {
            $this->options[$key] = $value;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym, // Zugriffsmöglichkeit auf die Daten
            'callable' => null, // Falls nicht null: anzuzeigender Inhalt (muss string oder Objekt mit __toString sein)
            'formatter' => DefaultFormatter::class, // Formatierer
            'formatter_options' => [],
            'form_type' => null, // Formular-Typ (Klasse)
            'form_options' => [], // Formular-Optionen
            'help' => null, // Hilfetext
            'preselect_definition' => null, // Vorausgewählte Entity folgender Definition
            'attr' => [], // Attribute auf dem Element
        ]);
    }

    protected function formatData($data, $formatter, $formatterOptions)
    {
        if (is_string($formatter)) {
            $formatterObj = $this->formatterManager->getFormatter($formatter);
            $formatterObj->processOptions($formatterOptions);
            return $formatterObj->getHtml($data);
        }

        if (is_callable($formatter)) {
            return $formatter($data);
        }

        if (is_array($formatter)) {
            foreach ($formatter as $index => $aFormatter) {
                $data = $this->formatData($data, $aFormatter, isset($formatterOptions[$index]) ? $formatterOptions[$index] : $formatterOptions);
            }

            return $data;
        }

        return $data;
    }
}
