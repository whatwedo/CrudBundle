# Testing

For testing your CRUD Application we provide the Abstract Class `\whatwedo\CrudBundle\Test\AbstractCrudTest`.
It extends from `\Symfony\Bundle\FrameworkBundle\Test\WebTestCase` and uses the `KernerBrowser`.

Basicly the test call the index/show/edit/new/export path of the definition. For each path you can provide data to 
perform the test. Namespace `whatwedo\CrudBundle\Test\Data` provides a set of data-objects for providing test data to 
the tests. By default the test will be performed with the entity with the entityId 1.

For perform the test the database must be filled with test data.  

We recommend to install the `doctrine-test-bundle`, see https://github.com/dmaicher/doctrine-test-bundle

We recommend to install the `doctrine-test-bundle`, see https://github.com/dmaicher/doctrine-test-bundle



## Setting up tests for your project


Craete a test-class extend from `\whatwedo\CrudBundle\Test\AbstractCrudTest`, implement the `getDefinitionClass()` mehtod 
to definine which Definition you like to test.

If your CRUD page is behind a firewall, you need to overwrite the `getBrowser()` method, to return a browser with 
an authenticated user.

```php 

namespace App\Tests\Browser\Crud;

use App\Definition\PersonDefinition;
use App\Repository\UserRepository;
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

- Show-test has been performed with die entity id 1
- Edit- and Create-Test has been skipped, because no Data has been provided 

### Specify the DataProvider

By overwriting the `getTestData()` method you can provide application specific Data.


```php

use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Test\Data\CreateData;
...

class PersonDefinitionTest extends AbstractCrudTest
{
   ...

    public function getTestData(): array
    {
        return [
            Page::INDEX->name => [
                'my-default-call' => [
                    IndexData::new()
                ],
                'index-page-99' => [
                    IndexData::new()
                        ->setQueryParameters([
                            'index-page' => '99'
                        ])
                ]
            ],
            Page::SHOW->name => [
                  'person-id-2' => [
                      ShowData::new()
                        ->setEntityId('2')
                  ],
                  'person-id-99' => [
                      ShowData::new()
                        ->setEntityId('99')
                          // does not exists
                          ->setExpectedStatusCode(404)
                  ],
                  'person-id-999' => [
                      ShowData::new()
                          ->setEntityId('999')
                          // TODO: finish this test case
                          ->setSkip(true)
                  ],
                  'person-id-query-params' => [
                      ShowData::new()
                          ->setEntityId('2')
                          // add some query params
                        ->setQueryParameters([
                              'foo' => 'bar'
                        ])
                  ],
            ],
            Page::CREATE->name => [
                'new-person' => [
                    CreateData::new()->setFormData([
                        'firstName' => 'Bob',
                        'lastName' => 'Coder',
                    ]),
                ],
                'second-person' => [
                    CreateData::new()->setFormData([
                        'firstName' => 'John',
                        'lastName' => 'Tester',
                    ]),
                ],
                'missing-lastName' => [
                    CreateData::new()->setFormData([
                        'firstName' => 'John',
                    ])
                        // get a 500 because the is no validation on the field
                    ->setExpectedStatusCode(500),
                ],
                'missing-firstName' => [
                    CreateData::new()->setFormData([
                        'lastName' => 'Tester',
                    ])
                        // get a 422 because the is validation on the field
                    ->setExpectedStatusCode(422),
                ],
            ],
            Page::EDIT->name => [
                'edit-person-1' => [
                    EditData::new()
                        ->setFormData([
                        'firstName' => 'Change-FirstName',
                        'lastName' => 'Change-LastName',
                    ]),
                ],
                'edit-person-2' => [
                    EditData::new()
                        ->setFormData([
                        'firstName' => 'Change-FirstName',
                        'lastName' => 'Change-LastName',
                    ])
                    ->setEntityId('2'),
                ],
                'edit-person-3-blank-firstName' => [
                    EditData::new()
                    ->setFormData([
                        'firstName' => '',
                        'lastName' => 'Change-LastName',
                    ])
                    ->setEntityId('3')
                    ->setExpectedStatusCode(422)
                    ,
                ],
                'edit-person-4-change-only-lastname' => [
                    EditData::new()
                    ->setFormData([
                        'lastName' => 'Change-LastName',
                    ])
                    ->setEntityId('4')
                    ,
                ],
            ],
        ];        
    }


```


```
$ vendor/bin/phpunit tests/Browser/Crud/PersonDefinitionTest.php --testdox
PHPUnit 9.5.27 by Sebastian Bergmann and contributors.

Person Definition (App\Tests\Browser\Crud\PersonDefinition)
 ✔ Index with my-default-call
 ✔ Index with index-page-99
 ✔ Index sort with firstName-asc
 ✔ Index sort with lastName-asc
 ✔ Export with default
 ✔ Show with person-id-2
 ✔ Show with person-id-99
 ↩ Show with person-id-999
 ✔ Show with person-id-query-params
 ✔ Edit with edit-person-1
 ✔ Edit with edit-person-2
 ✔ Edit with edit-person-3-blank-firstName
 ✔ Edit with edit-person-4-change-only-lastname
 ✔ Create with new-person
 ✔ Create with second-person
 ✔ Create with missing-lastName
 ✔ Create with missing-firstName

Time: 00:01.371, Memory: 68.50 MB

Summary of non-successful tests:

Person Definition (App\Tests\Browser\Crud\PersonDefinition)
 ↩ Show with person-id-999
OK, but incomplete, skipped, or risky tests!
Tests: 17, Assertions: 24, Skipped: 1.

```




