<?php

declare(strict_types=1);
/*
 * Copyright (c) 2021, whatwedo GmbH
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

namespace whatwedo\CrudBundle\Builder;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use whatwedo\CrudBundle\Enums\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;

class DefinitionMenuBuilder
{
    /**
     * Defines the symfony route.
     * Accepts: <code>string</code>
     */
    public const OPT_ROUTE = 'route';

    /**
     * Defines the symfony route parameters.
     * Accepts: <code>array</code>
     */
    public const OPT_ROUTE_PARAMETERS = 'routeParameters';

    /**
     * Defines the rendered label. You can use translation strings here.
     * Accepts: <code>string</code>
     */
    public const OPT_LABEL = 'label';

    /**
     * Defines the custom attributes on the menu item.
     * Accepts: <code>array</code>
     */
    public const OPT_ATTR = 'attributes';

    /**
     * Defines the icon of the menu item. We use bootstrap icons here. If the bootstrap class is <code>bi bi-arrow-90deg-up</code> you can use <code>arrow-90deg-up</code> here.
     * This option has to be passed in the <code>OPT_ATTR</code> option.
     * Accepts: <code>string</code>
     */
    public const OPT_ATTR_ICON = 'icon';

    protected Request $request;

    public function __construct(
        protected FactoryInterface $factory,
        protected DefinitionManager $definitionManager,
        protected AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    protected function addDefinition(ItemInterface $parent, string $definition, array $options = [], ?string $title = null): ? ItemInterface
    {
        $definitionObject = $this->definitionManager->getDefinitionByClassName($definition);

        if ($definitionObject
            && $this->authorizationChecker->isGranted(Page::INDEX, $definitionObject)) {
            if (! $title) {
                $title = $definitionObject::getEntityTitlePlural();
            }

            if (! isset($options[self::OPT_ROUTE])) {
                $options[self::OPT_ROUTE] = $definitionObject::getRoute(Page::INDEX);
            }

            $child = $parent->addChild($title, $options);

            if ($this->request) {
                try {
                    $current = $this->definitionManager->getDefinitionByRoute($this->request->attributes->get('_route')) === $definitionObject;
                } catch (\InvalidArgumentException) {
                    $current = null;
                }

                if ($current
                    && isset($options[self::OPT_ROUTE_PARAMETERS])
                    && $options[self::OPT_ROUTE_PARAMETERS]) {
                    foreach ($options[self::OPT_ROUTE_PARAMETERS] as $k => $v) {
                        $current = $current && ($this->request->query->get($k) === $v);
                    }
                }
                $child->setCurrent($current);
            }

            return $child;
        }

        return null;
    }
}
