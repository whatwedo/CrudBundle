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
use whatwedo\CrudBundle\Tests\Data\CreateData;
use whatwedo\CrudBundle\Tests\Data\EditData;
use whatwedo\CrudBundle\Tests\Data\IndexData;
use whatwedo\CrudBundle\Tests\Data\ShowData;

abstract class AbstractCrudTest extends WebTestCase
{
    abstract protected function getDefinitionClass(): string;
    abstract protected function getClient(): KernelBrowser;

    /**
     * @dataProvider indexData()
     */
    public function testIndex(IndexData $indexData): void
    {
        if ($indexData->skip) {
            $this->markTestSkipped('index Test Skipped');
        }

        if ($this->getDefinition()::hasCapability(Page::INDEX)) {
            $this->getClient()->request('GET', $this->getRouter()->generate(
                $this->getDefinition()::getRoute(Page::INDEX),
                $indexData->queryParameters
            ));
            self::assertResponseIsSuccessful();
        } else {
            self::assertTrue(true);
        }
    }

    public function indexData() {
        $testData = $this->getTestData();
        if (isset($testData[Page::INDEX->name])) {
            return $testData[Page::INDEX->name];
        }

        return [
            [
                IndexData::new()
            ]
        ];
    }

    /**
     * @dataProvider showData()
     */
    public function testShow(ShowData $showData): void
    {
        if ($showData->skip) {
            $this->markTestSkipped('show Test Skipped');
        }

        $this->getClient()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::SHOW),
            array_merge(['id' => $showData->entityId], $showData->queryParameters)
        ));
        self::assertResponseIsSuccessful();
    }

    public function showData() {
        $testData = $this->getTestData();
        if (isset($testData[Page::SHOW->name])) {
            return $testData[Page::SHOW->name];
        }

        return [
            [
                ShowData::new()
            ]
        ];
    }

    /**
     * @dataProvider editData()
     */
    public function testEdit(EditData $editData): string
    {
        if ($editData->skip) {
            $this->markTestSkipped('show Test Skipped');
        }

        $editLink = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::EDIT),
            array_merge([
                'id' => $editData->entityId,
            ], $editData->queryParameters)
        );
        $crawler = $this->getClient()->request('GET', $editLink);
        $form = $crawler->filter('#whatwedo-crud-submit')->form([], 'POST');
        $this->fillForm($form, $editData->formData);
        $this->getClient()->submit($form);
        self::assertResponseIsSuccessful();
        return $editLink;
    }

    public function editData() {
        $testData = $this->getTestData();
        if (isset($testData[Page::EDIT->name])) {
            return $testData[Page::EDIT->name];
        }

        return [
            [
                EditData::new()
            ]
        ];
    }

    /**
     * @dataProvider createData()
     */
    public function testCreate(CreateData $createData): string
    {
        if ($createData->skip) {
            $this->markTestSkipped('create Test Skipped');
        }

        $createLink = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::CREATE),
            $createData->queryParameters
        );
        $crawler = $this->getClient()->request('GET', $createLink);
        $form = $crawler->filter('#whatwedo-crud-submit')->form([], 'POST');
        $this->fillForm($form, $createData->formData);
        $this->getClient()->submit($form);
        self::assertResponseIsSuccessful();
        return $createLink;
    }

    public function createData() {
        $testData = $this->getTestData();
        if (isset($testData[Page::CREATE->name])) {
            return $testData[Page::CREATE->name];
        }

        return [
            [
                CreateData::new()
            ]
        ];
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

    protected function fillForm(Form $form, $formData)
    {
        foreach ($formData as $field => $value) {
            $form['form['.$field.']'] = $value;
        }
    }

    public function getTestData(): array {
        return [];
    }
}