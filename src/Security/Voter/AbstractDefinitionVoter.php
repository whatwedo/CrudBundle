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

namespace whatwedo\CrudBundle\Security\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Exception\DefinitionNotFoundException;
use whatwedo\CrudBundle\Manager\DefinitionManager;

abstract class AbstractDefinitionVoter extends Voter
{
    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    public function __construct(DefinitionManager $definitionManager)
    {
        $this->definitionManager = $definitionManager;
    }

    /**
     * Get fully qualified definition class name.
     *
     * @return string
     */
    abstract protected static function getDefinitionClassName();

    /**
     * Get additional attributes which are not in capabilities.
     *
     * @return array
     */
    protected static function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * @return DefinitionInterface
     *
     * @throws DefinitionNotFoundException
     */
    protected function getDefinition()
    {
        $definitionName = static::getDefinitionClassName();
        $definition = $this->definitionManager->getDefinitionByClassName(static::getDefinitionClassName());
        if ($definition === null) {
            throw new DefinitionNotFoundException('Definition ' . $definitionName . ' not found');
        }

        return $definition;
    }

    /**
     * @param $subject
     *
     * @return bool
     */
    protected function isSubjectSupported($subject)
    {
        if ($subject === null) {
            return false;
        }
        $entityName = $this->getDefinition()->getEntity();
        $entityReflector = new \ReflectionClass($entityName);

        return $entityReflector->isInstance($subject);
    }

    /**
     * @param $attribute
     *
     * @return bool
     */
    protected function isAttributeSupported($attribute)
    {
        $supportedAttributes = array_merge($this->getDefinition()::getCapabilities(), static::getAdditionalAttributes());

        return in_array($attribute, $supportedAttributes, true);
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isSubjectSupported($subject) && $this->isAttributeSupported($attribute);
    }
}
