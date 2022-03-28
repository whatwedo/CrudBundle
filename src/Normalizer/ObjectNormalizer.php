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

namespace whatwedo\CrudBundle\Normalizer;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as BaseObjectNormalizer;
use whatwedo\CrudBundle\Definition\DefinitionInterface;

class ObjectNormalizer extends BaseObjectNormalizer
{
    /**
     * @var DefinitionInterface
     */
    private $definition;

    /**
     * @var array
     */
    private $customCallbacks = [];

    public function __construct(DefinitionInterface $definition)
    {
        parent::__construct(null, null, null, null);
        $this->definition = $definition;
    }

    /**
     * @return array
     */
    public function getCustomCallbacks()
    {
        return $this->customCallbacks;
    }

    /**
     * @param array $customCallbacks
     *
     * @return self
     */
    public function setCustomCallbacks($customCallbacks)
    {
        $this->customCallbacks = $customCallbacks;

        return $this;
    }

    /**
     * @return array
     */
    protected function getAllowedAttributes(object|string $classOrObject, array $context, bool $attributesAsString = false): array|bool
    {
        return $this->definition->getExportAttributes();
    }

    /**
     * Gets the attribute value.
     */
    protected function getAttributeValue(object $object, string $attribute, ?string $format = null, array $context = []): mixed
    {
        $attrValue = $this->propertyAccessor->getValue($object, $attribute);
        if (isset($this->customCallbacks[$attribute])) {
            $attrValue = call_user_func($this->customCallbacks[$attribute], $attrValue, $object);
        }

        return $attrValue;
    }
}
