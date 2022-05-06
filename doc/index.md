# Getting Started

This documentation provides a basic view of the possibilities of the whatwedoCrudBundle. 
The documentation will be extended while developing the bundle.

## Requirements

This bundle has been tested on PHP >= 8.0 and Symfony >= 6.0. 
We don't guarantee that it works on lower versions.

## Templates

The views of this template are based on [Tailwind CSS](https://tailwindcss.com/) layout. You can overwrite them at any time. 

## Installation
The bundle depends on bootstrap icons. To get them running smoothly in your project
add this repository to you composer.json: (Sadly composer cannot load repositories recursively)
```
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "twbs/icons",
            "version": "1.8.1",
            "source": {
                "url": "https://github.com/twbs/icons",
                "type": "git",
                "reference": "tags/v1.8.1"
            }
        }
    }
]
```
Then, add the bundle to your dependencies and install it.
```
composer require whatwedo/crud-bundle
```
The v1 version is still in developing,
so you need to add these lines manually to the composer.json to get the version constraint right:
```
    ...
    "whatwedo/core-bundle": "dev-1.0-dev as v1.0.0",
    "whatwedo/crud-bundle": "dev-1.0-dev as v1.0.0",
    "whatwedo/search-bundle": "dev-3.0-dev as v3.0.0",
    "whatwedo/table-bundle": "dev-1.0-dev as v1.0.0",
    ...
```
After successfully installing the bundle, you should see changes in the files
`assets/controller.json`, `config/bundles.php`, `package.json`, `symfony.lock`, `composer.json` and `composer.lock`.

Then, add our routes to your ```config/routes.yaml```
```
whatwedo_crud_bundle:
    resource: "@whatwedoCrudBundle/Resources/config/routing.yml"
    prefix: /
```
As the CrudBundle needs the TableBundle you also need to include the routes of the TableBundle:
```
whatwedo_table_bundle:
    resource: "@whatwedoTableBundle/Resources/config/routing.yml"
    prefix: /
```

## Use the bundle

### Prepare UI
Your base template needs to extend `'@whatwedoCrud/base.html.twig'` or contain the same blocks
and stimulus controllers.

If you are using our template, you will need a route named `dashboard`. You also will need two menus (main and sub). 
You can configure them like this:  

`services.yaml`  
```yaml
App\Menu\MenuBuilder:
    tags:
        - { name: knp_menu.menu_builder, method: createMainMenu, alias: main }
        - { name: knp_menu.menu_builder, method: createSubMenu, alias: sub }
```

`App\Menu\MenuBuilder.php`
```php
namespace App\Menu;

use Knp\Menu\ItemInterface;
use whatwedo\CrudBundle\Builder\DefinitionMenuBuilder;

class MenuBuilder extends DefinitionMenuBuilder
{
    public function createMainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('');
        $menu->addChild('Dashboard', [
            'route' => 'dashboard',
            'attributes' => [
                'icon' => 'house-door',
            ],
        ]);
        return $menu;
    }

    public function createSubMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('');
        return $menu;
    }
}
```

### Configure Tailwind
Be sure to include this config in the `tailwind.config.js`:
````js
...
content: [
    './assets/**/*.js',
    './templates/**/*.{html,html.twig}',
    './vendor/whatwedo/**/*.{html,html.twig,js}',
    './var/cache/twig/**/*.php',
    './src/Definition/*.php',
],
...
plugins: [
    require('@tailwindcss/forms'),
],
...
````
The `@tailwindcss/forms` can be added to your project like `yarn add @tailwindcss/forms` 

### Create an entity

First, you need to create a new entity for your data.
In our example, we want to create a User management system. 

Use your existing `User.php` entity or create a new one with `php bin/console make:entity`. 
Our class looks like this:
```php
<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Email]
    #[Assert\NotNull]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotNull]
    private $firstname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotNull]
    private $lastname;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotNull]
    private $password;

    #[ORM\Column(type: 'array')]
    #[Assert\NotNull]
    #[Assert\Count(min: 1)]
    private $roles = [self::ROLE_USER,];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
}
```

### Create a definition

In the definition file, you explain and configure your entity. 

It contains all information to create your CRUD view.

```php
<?php

namespace App\Definition;

use App\Entity\User;
use whatwedo\CrudBundle\Definition\AbstractDefinition;

class UserDefinition extends AbstractDefinition
{
    public static function getEntity(): string
    {
        return User::class;
    }
}
```


### Definition Configuration
Per default the entry point of the definition will be `/{wwd-crud-prefix}/{namespace_entity}`.
In our case `/app_user` (as we defined an empty prefix). However you can change this by 
overriding the method `getRoutePathPrefix`:
```php
class UserDefinition extends AbstractDefinition
{
    public static function getRoutePathPrefix(): string
    {
        return 'user';
    }
}
```
Now the entry point is at `/user`.

### configure whatwedoTableBundle
How to list the entities on their index page is defined with the table bundle.  
Example:
```php
...
    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('firstname')
            ->addColumn('lastname')
            ->addColumn('email')
        ;
    }
...
```
Full documentation can be found here: 
[whatwedoTableBundle](https://github.com/whatwedo/TableBundle)

### configure create & update
To define which fields can be edited and how you override the `configureView` method. It could look like this:
```php
public function configureView(DefinitionBuilder $builder, $data): void
{
    parent::configureView($builder, $data);
    $builder
        ->addBlock('base')
        ->addContent('firstname', null, [
            'help' => false,
        ])
        ->addContent('lastname', null, [
            'help' => false,
        ])
        ->addContent('email')
    ;

    $builder
        ->addBlock('security')
        ->addContent('plainPassword', null, [
            'form_type' => PasswordType::class,
        ])
    ;
}
```

### try it
That's all.

```http://127.0.0.1:8000/app_user```

### More resources

- [View Configuration](view-configuration.md)
- [Table Configuration](table-configuration.md)
- [Formatter](formatter.md)
- [Events](events.md)
- [Ajax](ajax.md)
- [Exporting](exporting.md)
- [Templating](templating.md)

### Extensions
- [Breadcrumbs](extensions/breadcrumbs.md)
