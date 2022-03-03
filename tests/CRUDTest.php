<?php

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

namespace whatwedo\CrudBundle\Tests;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Test\AbstractCrudTest;
use whatwedo\CrudBundle\Test\Data\CreateData;
use whatwedo\CrudBundle\Tests\App\Definition\CompanyDefinition;
use whatwedo\CrudBundle\Tests\App\Entity\Company;

class CRUDTest extends AbstractCrudTest
{

    protected ?KernelBrowser $client = null;

    protected function getBrowser(): KernelBrowser
    {
        if (!$this->client) {
            if (!self::$booted) {
                $this->client = static::createClient();
            } else {
                $this->client = self::getContainer()->get('test.client');
            }
            $this->client->followRedirects(true);
        }
        return $this->client;
    }

    protected function setUp(): void
    {
        $this->getBrowser();
        $testCompany = new Company();
        $testCompany->setName('TEST name');
        $testCompany->setCity('TEST city');
        $testCompany->setCountry('TEST country');
        $testCompany->setTaxIdentificationNumber('TEST tax');
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($testCompany);
        $em->flush($testCompany);
    }

    protected function getDefinitionClass(): string
    {
        return CompanyDefinition::class;
    }

    public function getTestData(): array
    {
        return [
            Page::CREATE->name => [
                [
                    'with-data' => CreateData::new()->setFormData([
                        'name' => 'whatwedo GmbH',
                        'city' => 'Bern',
                        'country' => 'CH',
                        'taxIdentificationNumber' => 'CH-036.4.059.123-4',
                    ]),
                ], [
                    'empty' => CreateData::new()->setExpectedStatusCode(422),
                ],
            ],
        ];
    }
}
