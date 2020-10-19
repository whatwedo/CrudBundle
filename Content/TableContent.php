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

namespace whatwedo\CrudBundle\Content;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Enum\RouteEnum;
use whatwedo\TableBundle\Table\ActionColumn;
use whatwedo\TableBundle\Table\Table;

class TableContent extends AbstractContent
{
    public function isTable()
    {
        return true;
    }

    public function renderTable(Table $table, $row)
    {
        if (is_callable($this->options['table_configuration'])) {
            $this->options['table_configuration']($table);
        }

        $actionColumnItems = [];

        if ($this->getOption('definition')
            && $this->hasCapability(RouteEnum::SHOW)) {
            $showRoute = $this->getRoute(RouteEnum::SHOW);

            $table->setShowRoute($showRoute);
            $actionColumnItems[] = [
                'label' => 'Details',
                'icon' => 'arrow-right',
                'button' => 'primary',
                'route' => $showRoute,
                'route_parameters' => [],
                'voter_attribute' => RouteEnum::SHOW,
            ];
        }

        if ($actionColumnItems) {
            $table->addColumn('actions', ActionColumn::class, [
                'items' => $actionColumnItems,
            ]);
        }

        $data = $this->getContents($row);

        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        if (is_string($data)) {
            throw new \Exception($data);
        }

        $table->setResults(array_values($data));

        return $table->renderTable();
    }

    /**
     * @param $row
     * @return string
     */
    public function render($row)
    {
        return 'call TableContent::renderTable()';
    }

    /**
     * @param $key
     * @param $value
     */
    public function setOption($key, $value)
    {
        if (isset($this->options[$key])) {
            $this->options[$key] = $value;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'accessor_path' => $this->acronym,
            'table_configuration' => null,
            'definition' => null,
            'route_addition_key' => null,
            'show_index_button' => false,
        ]);
    }

    /**
     * @param $capability
     */
    protected function hasCapability($capability): bool
    {
        return call_user_func([$this->getOption('definition'), 'hasCapability'], $capability);
    }

    /**
     * @param $suffix
     */
    protected function getRoute($suffix): string
    {
        return call_user_func([$this->options['definition'], 'getRouteName'], $suffix);
    }
}
