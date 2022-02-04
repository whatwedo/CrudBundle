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

namespace whatwedo\CrudBundle\Test;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Routing\RouterInterface;
use whatwedo\CrudBundle\Definition\DefinitionInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CrudBundle\Tests\Data\CreateData;
use whatwedo\CrudBundle\Tests\Data\EditData;
use whatwedo\CrudBundle\Tests\Data\Form\Upload;
use whatwedo\CrudBundle\Tests\Data\IndexData;
use whatwedo\CrudBundle\Tests\Data\ShowData;

abstract class AbstractCrudTest extends WebTestCase
{
    /**
     * @dataProvider indexData()
     */
    public function testIndex(IndexData $indexData): void
    {
        if (! $this->getDefinition()::hasCapability(Page::INDEX)) {
            $this->markTestSkipped('no index capability, skip test');
        }

        if ($indexData->isSkip()) {
            $this->markTestSkipped('index Test Skipped');
        }

        $this->getClient()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::INDEX),
            $indexData->getQueryParameters()
        ));
        $this->assertResponseStatusCodeSame($indexData->getExpectedStatusCode());
    }

    public function indexData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::INDEX->name])) {
            return $testData[Page::INDEX->name];
        }

        return [
            [
                IndexData::new(),
            ],
        ];
    }

    /**
     * @dataProvider showData()
     */
    public function testShow(ShowData $showData): void
    {
        if (! $this->getDefinition()::hasCapability(Page::SHOW)) {
            $this->markTestSkipped('no show capability, skip test');
        }

        if ($showData->isSkip()) {
            $this->markTestSkipped('show Test Skipped');
        }

        $this->getClient()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::SHOW),
            array_merge([
                'id' => $showData->getEntityId(),
            ], $showData->getQueryParameters())
        ));
        $this->assertResponseStatusCodeSame($showData->getExpectedStatusCode());
    }

    public function showData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::SHOW->name])) {
            return $testData[Page::SHOW->name];
        }

        return [
            [
                ShowData::new(),
            ],
        ];
    }

    /**
     * @dataProvider editData()
     */
    public function testEdit(EditData $editData): string
    {
        if (! $this->getDefinition()::hasCapability(Page::EDIT)) {
            $this->markTestSkipped('no edit capability, skip test');
        }

        if ($editData->isSkip()) {
            $this->markTestSkipped('show Test Skipped');
        }

        $editLink = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::EDIT),
            array_merge([
                'id' => $editData->getEntityId(),
            ], $editData->getQueryParameters())
        );
        $crawler = $this->getClient()->request('GET', $editLink);
        $form = $crawler->filter('#crud_main_form')->form([], 'POST');
        $this->fillForm($form, $editData->getFormData());
        $this->getClient()->submit($form);
        $this->assertResponseStatusCodeSame($editData->getExpectedStatusCode());

        return $editLink;
    }

    public function editData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::EDIT->name])) {
            return $testData[Page::EDIT->name];
        }

        return [
            [
                EditData::new(),
            ],
        ];
    }

    /**
     * @dataProvider createData()
     */
    public function testCreate(CreateData $createData): string
    {
        if (! $this->getDefinition()::hasCapability(Page::CREATE)) {
            $this->markTestSkipped('no create capability, skip test');
        }

        if ($createData->isSkip()) {
            $this->markTestSkipped('create Test Skipped');
        }

        $createLink = $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::CREATE),
            $createData->getQueryParameters()
        );
        $crawler = $this->getClient()->request('GET', $createLink);
        $form = $crawler->filter('#crud_main_form')->form([], 'POST');
        $this->fillForm($form, $createData->getFormData());
        $this->getClient()->submit($form);

        $this->assertResponseStatusCodeSame($createData->getExpectedStatusCode());

        return $createLink;
    }

    public function createData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::CREATE->name])) {
            return $testData[Page::CREATE->name];
        }

        return [
            [
                CreateData::new(),
            ],
        ];
    }

    public function getTestData(): array
    {
        return [];
    }

    abstract protected function getDefinitionClass(): string;

    abstract protected function getClient(): KernelBrowser;

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
            if ($value instanceof Upload) {
                $form['form[' . $field . '][' . $value->getField() . ']']->upload($value->getPath());
            } else {
                $form['form[' . $field . ']'] = $value;
            }
        }
    }
}
