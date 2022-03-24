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

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class DefinitionBlock extends Block
{

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'accessor_path' => null,
            'callable' => null,
            'block' => $this->acronym,
            'definition' => null,
        ]);
        $resolver->setRequired([
            'block',
        ]);
        $resolver->setAllowedTypes('definition', ['string', 'null',]);
        $resolver->setAllowedTypes('block', 'string');
    }

    public function getData($row): mixed
    {
        if (is_callable($this->options['callable'])) {
            if (is_array($this->options['callable'])) {
                return call_user_func($this->options['callable'], [$row]);
            }

            return $this->options['callable']($row);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor()
        ;

        try {
            return $propertyAccessor->getValue($row, $this->options['accessor_path']);
        } catch (UnexpectedTypeException) {
            return null;
        } catch (NoSuchPropertyException $noSuchPropertyException) {
            return $noSuchPropertyException->getMessage();
        }
    }

}
