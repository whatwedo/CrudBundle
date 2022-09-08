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
use whatwedo\CrudBundle\Test\Data\AbstractData;
use whatwedo\CrudBundle\Test\Data\CreateData;
use whatwedo\CrudBundle\Test\Data\EditData;
use whatwedo\CrudBundle\Test\Data\ExportData;
use whatwedo\CrudBundle\Test\Data\Form\Upload;
use whatwedo\CrudBundle\Test\Data\IndexData;
use whatwedo\CrudBundle\Test\Data\ShowData;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\DataLoader\DoctrineTreeDataLoader;
use whatwedo\TableBundle\Entity\TreeInterface;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\Column;

abstract class AbstractCrudTest extends WebTestCase
{
    /**
     * @dataProvider indexData()
     */
    public function testIndex(IndexData $indexData): void
    {
        $this->setUpTestIndex($indexData);
        if (! $this->getDefinition()::hasCapability(Page::INDEX)) {
            $this->markTestSkipped('no index capability, skip test');
        }

        if ($indexData->isSkip()) {
            $this->markTestSkipped('index Test Skipped');
        }

        $this->getBrowser()->request('GET', $this->getRouter()->generate(
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
     * @dataProvider indexSortData()
     */
    public function testIndexSort(IndexData $indexData): void
    {
        $this->setUpTestIndexSort($indexData);
        if (! $this->getDefinition()::hasCapability(Page::INDEX)) {
            $this->markTestSkipped('no index capability, skip test');
        }

        if ($indexData->isSkip()) {
            $this->markTestSkipped('index Test Skipped');
        }

        $this->getBrowser()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::INDEX),
            $indexData->getQueryParameters()
        ));
        $this->assertResponseStatusCodeSame($indexData->getExpectedStatusCode());
    }

    public function indexSortData()
    {
        $testData = $this->getTestData();

        if (isset($testData[Page::INDEX->name])) {
            $testData = $testData[Page::INDEX->name];
        }

        $testData = [
            [
                IndexData::new(),
            ],
        ];

        $sortTestData = [];

        $tableFactory = self::getContainer()->get(TableFactory::class);
        $dataLoader = DoctrineDataLoader::class;
        if (is_subclass_of($this->getDefinition()::getEntity(), TreeInterface::class)) {
            $dataLoader = DoctrineTreeDataLoader::class;
        }

        $table = $tableFactory->create('index', $dataLoader, [
            'dataloader_options' => [
                DoctrineDataLoader::OPTION_QUERY_BUILDER => $this->getDefinition()->getQueryBuilder(),
            ],
        ]);

        $this->getDefinition()->configureTable($table);

        if ($table->getSortExtension()) {
            $sortExtension = $table->getSortExtension();

            foreach ($table->getColumns() as $column) {
                if ($column->getOption(Column::OPTION_SORTABLE)) {
                    $sortQueryData = $sortExtension->getOrderParameters($column, 'asc');
                    foreach ($testData as $testKey => $testItem) {
                        /** @var IndexData $indexData */
                        $indexData = clone $testItem[0];
                        $indexData->setQueryParameters(
                            array_merge(
                                $indexData->getQueryParameters(),
                                $sortQueryData
                            )
                        );
                        $sortTestData[$column->getIdentifier() . '-asc'] = [$indexData];
                    }
                }
            }
        }

        return $sortTestData;
    }

    /**
     * @dataProvider exportData()
     */
    public function testExport(ExportData $indexData): void
    {
        $this->setUpTestExport($indexData);
        if (! $this->getDefinition()::hasCapability(Page::EXPORT)) {
            $this->markTestSkipped('no export capability, skip test');
        }

        if ($indexData->isSkip()) {
            $this->markTestSkipped('index Test Skipped');
        }

        $this->getBrowser()->request('GET', $this->getRouter()->generate(
            $this->getDefinition()::getRoute(Page::EXPORT),
            $indexData->getQueryParameters()
        ));
        $this->assertResponseStatusCodeSame($indexData->getExpectedStatusCode());
    }

    public function exportData()
    {
        $testData = $this->getTestData();
        if (isset($testData[Page::EXPORT->name])) {
            return $testData[Page::EXPORT->name];
        }

        return [
            [
                ExportData::new(),
            ],
        ];
    }

    /**
     * @dataProvider showData()
     */
    public function testShow(ShowData $showData): void
    {
        $this->setUpTestShow($showData);
        if (! $this->getDefinition()::hasCapability(Page::SHOW)) {
            $this->markTestSkipped('no show capability, skip test');
        }

        if ($showData->isSkip()) {
            $this->markTestSkipped('show Test Skipped');
        }

        $this->getBrowser()->request('GET', $this->getRouter()->generate(
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
        $this->setUpTestEdit($editData);
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
        $crawler = $this->getBrowser()->request('GET', $editLink);
        $this->assertSame(200, $this->getBrowser()->getResponse()->getStatusCode());
        $form = $crawler->filter('#crud_main_form')->form([], 'POST');
        $this->fillForm($form, $editData->getFormData());
        $this->getBrowser()->submit($form);
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
        $this->setUpTestCreate($createData);
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
        $crawler = $this->getBrowser()->request('GET', $createLink);
        $this->assertSame(200, $this->getBrowser()->getResponse()->getStatusCode());
        $form = $crawler->filter('#crud_main_form')->form([], 'POST');
        $this->fillForm($form, $createData->getFormData());
        $this->getBrowser()->submit($form);

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

    abstract protected function getBrowser(): KernelBrowser;

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

    protected function setUpTestIndex(IndexData $indexData)
    {
        $this->setUpTest($indexData, 'index');
    }

    protected function setUpTestIndexSort(IndexData $indexData)
    {
        $this->setUpTest($indexData, 'indexSort');
    }

    protected function setUpTestExport(ExportData $indexData)
    {
        $this->setUpTest($indexData, 'export');
    }

    protected function setUpTestShow(ShowData $showData)
    {
        $this->setUpTest($showData, 'show');
    }

    protected function setUpTestEdit(EditData $editData)
    {
        $this->setUpTest($editData, 'edit');
    }

    protected function setUpTestCreate(CreateData $createData)
    {
        $this->setUpTest($createData, 'create');
    }

    protected function setUpTest(AbstractData $createData, string $testType)
    {
    }
}
