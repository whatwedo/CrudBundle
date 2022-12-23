# Testing

For testing your CRUD Application we provide the Abstract Class `\whatwedo\CrudBundle\Test\AbstractCrudTest`.
It extends from `\Symfony\Bundle\FrameworkBundle\Test\WebTestCase` and uses the `Symfony\Bundle\FrameworkBundle\KernelBrowser`.

Basically the test call the index/show/edit/new/export path of the definition. For each path you can provide data to 
perform the test. Namespace `whatwedo\CrudBundle\Test\Data` provides a set of data-objects for providing data to 
the tests.

For perform the test the database must be filled with test data (fixtures).  

We recommend to install `doctrine-test-bundle`, see https://github.com/dmaicher/doctrine-test-bundle

We recommend to install `zenstruck/foundry`, see https://github.com/zenstruck/foundry



## Setting up tests for your project


Create a test-class extend from `\whatwedo\CrudBundle\Test\AbstractCrudTest`, implement the `getDefinitionClass()` mehtod 
to define which Definition you like to test.

If your CRUD page is behind a login page, you need to overwrite the `getBrowser()` method, to return a browser with 
an authenticated user.

```php 

namespace App\Tests\Browser\Crud;

use App\Definition\PersonDefinition;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use whatwedo\CrudBundle\Test\AbstractCrudTest;

class PersonDefinitionTest extends AbstractCrudTest
{

    protected function getDefinitionClass(): string
    {
        return PersonDefinition::class;
    }
    
    /**
     * normaly the crud is behind a firewall, so we need to login
     * overwrite the getBrowser() method
     */
    protected function getBrowser(): KernelBrowser
    {
        parent::getBrowser();
        $this->client->loginUser(static::getContainer()->get(UserRepository::class)->findOneBy([
            'username' => 'admin',
        ]));

        return $this->client;
    }


```

Run your first test

```
$ vendor/bin/phpunit tests/Browser/Crud/PersonDefinitionTest.php --testdox
PHPUnit 9.5.27 by Sebastian Bergmann and contributors.

Person Definition (App\Tests\Browser\Crud\PersonDefinition)
 ✔ Index with default
 ✔ Index sort with firstName-asc
 ✔ Index sort with lastName-asc
 ✔ Export with default
 ✔ Show with id-1
 ↩ Edit with default
 ↩ Create with default

Time: 00:00.223, Memory: 50.50 MB

Summary of non-successful tests:

Person Definition (App\Tests\Browser\Crud\PersonDefinition)
 ↩ Edit with default
 ↩ Create with default
OK, but incomplete, skipped, or risky tests!
Tests: 7, Assertions: 5, Skipped: 2.

```

Note:

- Edit- and Create-Test has been skipped, because no Data has been provided 

### Specify the DataProvider

By overwriting the `getTestData()` method you can provide application specific Data.


```php

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Test\Data\IndexData;

class PersonDefinitionTest extends AbstractCrudTest
{
    public function getTestData(): array
    {
        return [
            Page::INDEX->name => [
                'my-default-call' => [
                    IndexData::new()
                ],               
            ],
            Page::SHOW->name => [],
            Page::CREATE->name => [],
            Page::EDIT->name => [],
        ];        
    }


```

### IndexData 

```php
 use whatwedo\CrudBundle\Test\Data\IndexData; 

 public function getTestData(): array
    {
        return [
            Page::INDEX->name => [
                'my-default-call' => [
                    IndexData::new(),
                ],
                'index-page-99' => [
                    IndexData::new()
                        ->setQueryParameters([
                            'index-page' => '99',
                        ]),
                ],
                'index-callback' => [
                    IndexData::new()
                        // use your own AssertCallback, called when the Page is loaded  
                        ->setAssertCallback(
                            function (Crawler $crawler, KernelBrowser $browser) {
                                static::assertSame(1, $crawler->filter('h1')->count(), 'Should be 1 H1-Tag');
                            }
                        ),
                ],
            ],
        ];        
    }
```

### ShowData
```php
 use whatwedo\CrudBundle\Test\Data\ShowData;
 
 public function getTestData(): array
    {
        return [
            Page::SHOW->name => [
                'person-id-2' => [
                    ShowData::new()
                        // default is 1
                        ->setEntityId('2'),
                ],
                'person-id-not-existing' => [
                    ShowData::new()
                        // default is 1
                        ->setEntityId('99')
                        ->setExpectedStatusCode(404),
                ],
                'person-id-skip' => [
                    ShowData::new()
                        // default is 1
                        ->setEntityId('999')
                        ->setSkip(true),
                ],
                'person-whit-query-params' => [
                    ShowData::new()
                        // default is 1
                        ->setEntityId('2')
                        ->setQueryParameters([
                            'foo' => 'bar',
                        ]),
                ],
                'person-callback' => [
                    ShowData::new()
                        // use your own AssertCallback, called when the Page is loaded
                        ->setAssertCallback(
                            function (Crawler $crawler, KernelBrowser $browser) {
                                static::assertSame(1, $crawler->filter('h1')->count(), 'Should be 1 H1-Tag');
                            }
                        )                    
                ]
            ],
        ];        
    }
```

### CreateData
```php
 use whatwedo\CrudBundle\Test\Data\CreateData;

 public function getTestData(): array
    {
        return [
            Page::CREATE->name => [
                'new-person' => [
                    CreateData::new()->setFormData([
                        'firstName' => 'Bob',
                        'lastName' => 'Coder',
                    ]),
                ],
                'missing-firstName' => [
                    CreateData::new()->setFormData([
                        'lastName' => 'Tester',
                    ])
                        // get a 422 because the is validation on the field
                        ->setExpectedStatusCode(422),
                ],
                'second-person' => [
                    CreateData::new()
                        ->setFormData([
                            'firstName' => 'John',
                            'lastName' => 'Tester',
                        ])
                        // use your own AssertCallback, called before the Send Button is clicked
                        ->setAssertBeforeSendCallback(
                            function (Crawler $crawler, KernelBrowser $browser) {
                                static::assertSame(1, $crawler->filter('h1')->count(), 'Should be 1 H1-Tag');
                            }
                        )
                        // Follow the Redirects, affects the Status Code
                        ->setFollowRedirects(true)
                        ->setExpectedStatusCode(200)
                        // use your own AssertCallback, called the new Page is loaed
                        ->setAssertCallback(
                            function (Crawler $crawler, KernelBrowser $browser) {
                                static::assertSame(1, $crawler->filter('h1')->count(), 'Should be 1 H1-Tag');
                            }
                        ),
                ],
        ];        
    }
```

### EditData
```php
 use whatwedo\CrudBundle\Test\Data\EditData;

 public function getTestData(): array
    {
        return [
            Page::EDIT->name => [
                'edit-person-1' => [
                    EditData::new()
                        ->setFormData([
                            'firstName' => 'Change-FirstName',
                            'lastName' => 'Change-LastName',
                        ]),
                ],
                'edit-person-3-blank-firstName' => [
                    EditData::new()
                        ->setFormData([
                            'firstName' => '',
                            'lastName' => 'Change-LastName',
                        ])
                        // default is 1
                        ->setEntityId('3')
                        ->setExpectedStatusCode(422),
                ],
                'edit-person-4-change-only-lastname' => [
                    EditData::new()
                        ->setFormData([
                            'lastName' => 'Change-LastName',
                        ])
                        // default is 1
                        ->setEntityId('4'),
                ],
                'edit-person-2' => [
                    EditData::new()
                        ->setFormData([
                            'firstName' => 'Change-FirstName',
                            'lastName' => 'Change-LastName',
                        ])
                        // use your own AssertCallback, called before the Send Button is clicked
                        ->setAssertBeforeSendCallback(
                            function (Crawler $crawler, KernelBrowser $browser) {
                                static::assertSame(1, $crawler->filter('h1')->count(), 'Should be 1 H1-Tag');
                            }
                        )
                        // default is 1
                        ->setEntityId('2')
                        // Follow the Redirects, affects the Status Code
                        ->setFollowRedirects(true)
                        ->setExpectedStatusCode(200)
                        // use your own AssertCallback, called the new Page is loaded
                        ->setAssertCallback(
                            function (Crawler $crawler, KernelBrowser $browser) {
                                static::assertSame(1, $crawler->filter('h1')->count(), 'Should be 1 H1-Tag');
                            }
                        ),
                ],            
            ],
        ];        
    }
```






