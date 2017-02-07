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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
abstract class AbstractContent implements ContentInterface
{
    /**
     * @var string block acronym
     */
    protected $acronym = '';

    /**
     * @var array block options
     */
    protected $options = [];

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
     * gets the content of the row
     *
     * @param $row
     * @return string
     */
    public function getContents($row)
    {
        if (is_callable($this->options['callable'])) {
            if (is_array($this->options['callable'])) {
                return call_user_func($this->options['callable'], [$row]);
            }

            return $this->options['callable']($row);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            return $propertyAccessor->getValue($row, $this->options['accessor_path']);
        } catch (UnexpectedTypeException $e) {
            return null;
            // return $e->getMessage();
        } catch (NoSuchPropertyException $e) {
            return $e->getMessage();
        }
    }

    /**
     * {@inheritdoc}
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => $this->acronym,
            'callable' => null,
        ]);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }
}
