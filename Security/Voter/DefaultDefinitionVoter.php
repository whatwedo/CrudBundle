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

namespace whatwedo\CrudBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\TraceableVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Manager\DefinitionManager;

/**
 * Class DefaultDefinitionVoter
 */
class DefaultDefinitionVoter implements VoterInterface
{
    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    /**
     * @var VoterInterface[]
     */
    protected $voters = [];

    /**
     * DefaultDefinitionVoter constructor.
     */
    public function __construct(DefinitionManager $definitionManager)
    {
        $this->definitionManager = $definitionManager;
    }

    public function addVoter(VoterInterface $voter)
    {
        if ($voter instanceof self || $voter instanceof TraceableVoter) {
            return;
        }
        $this->voters[] = $voter;
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param mixed $subject The subject to secure
     * @param array $attributes An array of attributes associated with the method being invoked
     *
     * @return int either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        // Check if subject is set
        if (is_null($subject)) {
            return static::ACCESS_ABSTAIN;
        }

        // Check if base on definition
        $definition = $subject instanceof DefinitionInterface ? $subject : $this->definitionManager->getDefinitionFor($subject);
        if (is_null($definition)) {
            return static::ACCESS_ABSTAIN;
        }

        // Check if another possible voter matches
        foreach ($this->voters as $voter) {
            $result = $voter->vote($token, $subject, $attributes);
            if ($result != static::ACCESS_ABSTAIN) {
                return static::ACCESS_ABSTAIN;
            }
        }

        // Check attributes
        foreach ($attributes as $attribute) {
            if ($definition::hasCapability($attribute)) {
                return static::ACCESS_GRANTED;
            }
        }

        // Abstain, if don't know what to do
        return static::ACCESS_ABSTAIN;
    }
}
