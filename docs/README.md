# Getting Started

This documentation provides a basic view of the possibilities of the whatwedoCrudBundle.  

## Requirements

This bundle has been tested on PHP >= 8.0 and Symfony >= 6.0.
We don't guarantee that it works on lower versions.  
It presumes a fresh symfony 6.x installation following the [symfony docs](https://symfony.com/doc/current/setup.html).  

## Templates

The views of this template are based on [Tailwind CSS](https://tailwindcss.com/) layout.
You can overwrite them at any time.  
More info about that can be found in the [Templating](templating.md) section of this documentation.

## Installation
### Composer
The bundle depends on bootstrap icons. To get them running smoothly in your project
add this repository to you composer.json: ([Sadly composer cannot load repositories recursively](https://getcomposer.org/doc/faqs/why-cant-composer-load-repositories-recursively.md))
```json
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
**TODO: remove after release**  

The v1 version is still in developing,
so you need to add these lines manually to the `composer.json` `require` to get the version constraint right:
```json
    ...
    "whatwedo/core-bundle": "dev-1.0-dev as v1.0.0",
    "whatwedo/crud-bundle": "dev-1.0-dev as v1.0.0",
    "whatwedo/search-bundle": "dev-3.0-dev as v3.0.0",
    "whatwedo/table-bundle": "dev-1.0-dev as v1.0.0",
    ...
```
Run `composer update`  
After successfully installing the bundle, you should see changes in these files:
 - `assets/controller.json`
 - `config/bundles.php`
 - `package.json`
 - `symfony.lock`
 - `composer.json`
 - `composer.lock`.

### Routing
Add our routes ```config/routes/wwd_crud.yaml```
```yaml
whatwedo_crud_bundle:
    resource: "@whatwedoCrudBundle/Resources/config/routing.yml"
    prefix: /
```
Here you can define a prefix for the whole crud part.
We mostly use `/admin` as often times this bundle is used as backend. 
You can however use and configure it to whatever suits your business case. 

### ORM
The table bundle allows you to save filters on the go.
These filters save the creator, therefore you need to configure your user class. 
You do this in your `packges/doctrine.yaml` file:
```yaml
doctrine:
    orm:
        resolve_target_entities:
            # The class which will be returned with "Symfony\Component\Security\Core\Security::getUser"
            whatwedo\TableBundle\Entity\UserInterface: App\Entity\User
```

### Tailwind and Webpack
To give you full access over the build and look-and-feel of the application you install these dependencies in your project locally.  
To get it up and running like whatwedo, install following:  
```shell
yarn add @tailwindcss/forms
yarn add tailwindcss postcss-loader sass-loader sass autoprefixer --dev
```

#### Tailwind
Be sure to extends tailwinds default config. You need a `primary` color and a `error` color.
Furthermore, you need to add our files to the `content` section. The `@tailwindcss/forms` plugin is a basic form style resetter. 
The config is located at `tailwind.config.js`.  

If you don't already have this file, generate it with `npx tailwind init`. Here is what a config could look like:
````js
const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        './assets/**/*.js',
        './templates/**/*.{html,html.twig}',
        './vendor/whatwedo/**/*.{html,html.twig,js}',
        './var/cache/twig/**/*.php',
        './src/Definition/*.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    lightest: '#6EDBFF',
                    light: '#48C0E8',
                    DEFAULT: '#007EA8',
                    dark: '#336C80',
                    darkest: '#0F4152',
                },
                error: colors.red,
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
}

````

#### Webpack
Create a `postcss.config.js` file in your root directory with this content:
```js
let tailwindcss = require('tailwindcss');

module.exports = {
    plugins: [
        tailwindcss('./tailwind.config.js'),
        require('autoprefixer'),
    ]
}
```
Enable sass and postcss support in the `webpack.config.js`, like following:
```js
Encore
    .enableSassLoader()
    .enablePostCssLoader()
;
```
Your main style, for instance `assets/styles/app.scss`, should be a `sass` file.
If your file is named `app.css` rename it to `app.scss`. Also change the import in main entrypoint file, for instance `assets/app.js`.
```js
import './styles/app.scss';
```

Import following styles into the `app.scss`:
```scss
@tailwind base;
@tailwind components;
@tailwind utilities;

@import "~@whatwedo/core-bundle/styles/_tailwind.scss";
@import "~@whatwedo/table-bundle/styles/_tailwind.scss";
```
It is **important** that you include the @whatwedo styles after the tailwind styles.

Run `yarn dev`, it should end with the message `webpack compiled successfully`. 

### Prepare UI

#### Base template
Our default views extend your `templates/base.html.twig` template. To get the default up and running create the file as following:
```twig
{% extends '@whatwedoCrud/base.html.twig' %}
```
If you create your own template without extending ours be sure to use the same block names and stimulus controllers.

##### Config with our templates
If you are using our template, you will need a route named `dashboard`. A simple dashboard template file could look this this:  

`config/routes/wwd_crud.yaml`
```yaml
dashboard:
  path: /dashboard
  controller: Symfony\Bundle\FrameworkBundle\Controller\TemplateController
  defaults:
    template: dashboard.html.twig
```
`templates/dashboard.html.twig`
```twig
{% extends 'base.html.twig' %}
{% block main %}
    Your static dashboard.
{% endblock %}
```
You also will need two menus (main and sub).
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

Done! The whatwedoCrudBundle is fully installed. You should see your dashboard at: http://localhost:8000/dashboard. Now start using it!

## Use Bundle

The Bundle uses translations files, currently only german is provided doe. Feel free to open a PR with new translations!
To use it in german set your applications `default_locale` to `de` like following:
```yaml
framework:
    default_locale: de
```

### Create an entity

First, you need to create a new entity for your data.
In our example, we want to create a User management system.

Use your existing `User.php` entity or create a new one with `php bin/console make:entity`.

Every CRUD managed entity needs to have a `__toString` method. Don't forget to create a migration or update your database according to the new entities. 
The crud bundle itself will create two tables for you: `whatwedo_search_index` and `whatwedo_table_filter`. 

### Create a definition

In the definition file, you explain and configure your entity.
It contains all information to create your CRUD view.
You can also generate a definition with our make command: `bin/console make:definition`

### try it
That's all.

```http://localhost:8000/app_user```