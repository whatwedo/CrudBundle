<?php

declare(strict_types=1);
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

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Factory\Cache\ChoiceLoader;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Form\ChoiceLoader\AjaxDoctrineChoiceLoader;

class EntityAjaxType extends AbstractType
{

    public function __construct(protected RouterInterface $router)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            [$options['choice_loader'], 'onFormPostSetData']
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            [$options['choice_loader'], 'onFormPostSetData']
        );
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['definition'] && $options['definition']::hasCapability(Page::JSONSEARCH)) {
            $view->vars['attr']['data-whatwedo--core-bundle--select-url-value'] = $this->router->generate(
                $options['definition']::getRoute(Page::JSONSEARCH)
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('choice_loader', function (Options $options, ChoiceLoaderInterface $doctrineChoiceLoader) {
            if ($doctrineChoiceLoader) {
                return new AjaxDoctrineChoiceLoader($doctrineChoiceLoader);
            }
        });
        $resolver->setDefault('definition', null);
        $resolver->setDefault('class', function (Options $options, ?string $className) {
            return $className ?: $options['definition']::getEntity();
        });
    }

    public function getParent()
    {
        return EntityType::class;
    }
}
