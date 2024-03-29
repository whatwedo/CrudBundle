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

namespace whatwedo\CrudBundle\Menu;

use Knp\Menu\ItemInterface;
use whatwedo\CrudBundle\Builder\DefinitionMenuBuilder;
use whatwedo\CrudBundle\Definition\FilterDefinition;
use whatwedo\CrudBundle\Enums\Page;

class MenuBuilder extends DefinitionMenuBuilder
{
    public function createMainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('');
        $menu->addChild('Dashboard', [
            self::OPT_LABEL => 'whatwedo_crud.dashboard',
            self::OPT_ROUTE => 'whatwedo_crud_dashboard',
            self::OPT_ATTR => [
                self::OPT_ATTR_ICON => 'house-door',
            ],
        ]);
        foreach ($this->definitionManager->getDefinitions() as $definition) {
            $class = get_class($definition);
            if ($class === FilterDefinition::class) {
                continue;
            }
            if (! $definition::hasCapability(Page::INDEX)) {
                continue;
            }
            $this->addDefinition($menu, $class);
        }

        return $menu;
    }

    public function createSubMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('');

        return $menu;
    }
}
