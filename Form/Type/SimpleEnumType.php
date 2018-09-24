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

namespace whatwedo\CrudBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CoreBundle\Enum\AbstractSimpleEnum;

/**
 * Class SimpleEnumType
 * @package whatwedo\CrudBundle\Form
 */
class SimpleEnumType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('choices', function (Options $options) {
            /** @var AbstractSimpleEnum|string $enumClass */
            $enumClass = $options['class'];

            if(!is_subclass_of($enumClass, AbstractSimpleEnum::class))
            {
                throw new \InvalidArgumentException(sprintf('Option \'class\' needs to be subclass of %s', AbstractSimpleEnum::class));
            }

            if(!$options['choice_values']) return $enumClass::getFormValues();

            $choices = [];
            foreach($options['choice_values'] as $choiceValue) {
                $choices[$enumClass::getRepresentation($choiceValue)] = $choiceValue;
            }

            return $choices;
        });

        $resolver->setRequired('class');
        $resolver->setDefault('choice_values', null);
        $resolver->setAllowedTypes('choice_values', ['array', 'null']);
        $resolver->setAllowedTypes('class', ['string']);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }


}
