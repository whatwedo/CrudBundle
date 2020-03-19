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

namespace whatwedo\CrudBundle\Formatter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\AccessMap;
use whatwedo\CoreBundle\Formatter\AbstractFormatter;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\CrudBundle\Manager\DefinitionManager;

/**
 * @author Maurizio Monticelli <maurizio@whatwedo.ch>
 */
class DefinitionLinkFormatter extends AbstractFormatter
{
    /**
     * @var DefinitionManager
     */
    protected $definitionManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var AccessMap
     */
    protected $accessMap;


    public function __construct(
        AccessMap $accessMap,
        DefinitionManager $definitionManager,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router
        )
    {
        $this->accessMap = $accessMap;
        $this->definitionManager = $definitionManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getString($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml($value)
    {

        $def = $this->definitionManager->getDefinitionFor($value);

        if (null !== $def) {
            if ($this->authorizationChecker->isGranted(RouteEnum::SHOW, $value)
                && $def::hasCapability(RouteEnum::SHOW)) {
                $path = $this->router->generate($def::getRouteName(RouteEnum::SHOW), ['id' => $value->getId()]);

                $granted = false;
                if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_ANONYMOUSLY')) {
                    // if the user is not authenticated, we link it because a
                    // login form is shown if the user tries to access the resource
                    // otherwise there would happens a InsufficientAuthenticationException
                    $fakeRequest = Request::create($path, 'GET');
                    list($roles, $channel) = $this->accessMap->getPatterns($fakeRequest);
                    foreach ($roles as $role) {
                        $granted = $granted || $this->authorizationChecker->isGranted($role);
                    }
                } else {
                    $granted = true;
                }

                if ($granted) {
                    return sprintf('<a href="%s">%s</a>', $path, $value);
                }
            }
        }

        return $value;
    }
}
