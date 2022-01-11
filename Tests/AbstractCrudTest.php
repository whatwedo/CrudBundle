<?php
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

namespace whatwedo\CrudBundle\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Routing\RouterInterface;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;

abstract class AbstractCrudTest extends WebTestCase
{
    abstract protected function getDefinitionClass(): string;
    abstract protected function getClient(): KernelBrowser;

    public function testIndex(): void
    {
        if ($this->getDefinition()::hasCapability(Page::INDEX)) {
            $this->getClient()->request('GET', $this->getRouter()->generate(
                $this->getDefinition()::getRoute(Page::INDEX)
            ));
            self::assertResponseIsSuccessful();
        } else {
            self::assertTrue(true);
        }
    }

    public function testShow(): void
    {
        $this->getClient()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::SHOW), [
                'id' => $this->getTestEntityId(),
            ]
        ));
        self::assertResponseIsSuccessful();
    }

    public function testEdit(): string
    {
        $editLink = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::EDIT), [
                'id' => $this->getTestEntityId(),
            ]
        );
        $crawler = $this->getClient()->request('GET', $editLink);
        $form = $crawler->filter('#whatwedo-crud-submit')->form([], 'POST');
        $this->getClient()->submit($form);
        self::assertResponseIsSuccessful();
        return $editLink;
    }

    public function testCreate(): string
    {
        $createLink = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::CREATE)
        );
        $crawler = $this->getClient()->request('GET', $createLink);
        $form = $crawler->filter('#whatwedo-crud-submit')->form([], 'POST');
        $this->fillCreateForm($form, Page::CREATE);
        $this->getClient()->submit($form);
        self::assertResponseIsSuccessful();
        return $createLink;
    }

    protected function getDefinition(): DefinitionInterface
    {
        /** @var DefinitionManager $manager */
        $manager = self::getContainer()->get(DefinitionManager::class);
        return $manager->getDefinitionByClassName($this->getDefinitionClass());
    }

    protected function getRouter(): RouterInterface
    {
        return self::getContainer()->get(RouterInterface::class);
    }

    protected function getTestEntityId(): int
    {
        return 1;
    }

    protected function fillCreateForm(Form $form, Page $page)
    {
        $createFormData = $this->getFormData($page);
        foreach ($createFormData as $field => $value) {
            $form['form['.$field.']'] = $value;
        }
    }

    protected function getFormData(Page $page): array
    {
        return [];
    }
}
