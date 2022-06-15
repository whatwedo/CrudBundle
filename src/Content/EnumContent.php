<?php

declare(strict_types=1);
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

namespace whatwedo\CrudBundle\Content;

use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CoreBundle\Formatter\EnumFormatter;

class EnumContent extends Content
{
    public const OPT_CLASS = 'class';

    public const OPT_FORMATTER = 'formatter';

    public const OPT_FORM_TYPE = 'form_type';

    public const OPT_FORM_OPTIONS = 'form_options';

    public const OPT_FORM_OPTIONS_CLASS = 'class';

    public const OPT_LABEL = 'label';

    public const OPT_CALLABLE = 'callable';

    public const OPT_ATTR = 'attr';

    public const OPT_VISIBILITY = 'visibility';

    public const OPT_SHOW_VOTER_ATTRIBUTE = 'show_voter_attribute';

    public const OPT_EDIT_VOTER_ATTRIBUTE = 'edit_voter_attribute';

    public const OPT_CREATE_VOTER_ATTRIBUTE = 'create_voter_attribute';

    public const OPT_BLOCK_PREFIX = 'block_prefix';

    public const OPT_ACCESSOR_PATH = 'accessor_path';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            self::OPT_CLASS => null,
            self::OPT_FORMATTER => EnumFormatter::class,
            self::OPT_FORM_TYPE => EnumType::class,
            self::OPT_FORM_OPTIONS => fn (Options $option) => [
                self::OPT_FORM_OPTIONS_CLASS => $option[self::OPT_CLASS],
            ],
        ]);

        $resolver->setAllowedTypes('class', ['string']);
        $resolver->setAllowedValues('class', function ($value) {
            $isNull = $value === null;
            $isEnumClass = !$isNull && enum_exists($value);
            return $isNull || $isEnumClass;
        });
    }
}
