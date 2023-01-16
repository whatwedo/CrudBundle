# Menu Extension

This extension allows building a custom navigation menu.

## Requirements
This extension requires the [KnpLabs/KnpMenuBundle](https://github.com/KnpLabs/KnpMenuBundle) bundle.
The bundle is already included in the CRUD Bundle. 

## Installation

By default, the menu is created automatically. A menu item is displayed for the dashboard and all definitions.
If you want to create your own menu, you can do it as follows:
1. create a new file in `src/Menu/MenuBuilder.php` or you can copy it from the CRUD bundle and customize the namespace.
   The file should look like this:

```php
declare(strict_types=1);

namespace App\Menu;

use Knp\Menu\ItemInterface;
use whatwedo\CrudBundle\Builder\DefinitionMenuBuilder;
use whatwedo\CrudBundle\Definition\FilterDefinition;

class MenuBuilder extends DefinitionMenuBuilder
{
    public function createMainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('');
        $menu->addChild('Dashboard', [
            self::OPT_ROUTE => 'whatwedo_crud_dashboard',
            self::OPT_ATTR => [
                self::OPT_ATTR_ICON => 'house-door',
            ],
        ]);
        foreach ($this->definitionManager->getDefinitions() as $definition) {
            $class = get_class($definition);
            if ($class === FilterDefinition::class) {
                continue;
            }
            $this->addDefinition($menu, $class);
        }

        return $menu;
    }

    public function createSubMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('');

        return $menu;
    }
}
```

2. Add the Menu in `services.yaml`:

```yml
parameters:
    whatwedo_crud.menu_builder.class: App\Menu\MenuBuilder
```

## Usage

### Customize your menu

You may want to create a sub navigation item at menu item `Dashboard`. You can do that as follows:

```php
$menu = $this->factory->createItem('');
$dashboardMenu = $menu->addChild('Dashboard', [
    self::OPT_ROUTE => 'whatwedo_crud_dashboard',
    self::OPT_ATTR => [
        self::OPT_ATTR_ICON => 'house-door',
    ],
]);
$dashboardMenu->addChild('Statistics', [
    self::OPT_ROUTE => 'statistics_route',
    self::OPT_ATTR => [
        self::OPT_ATTR_ICON => 'graph-down',
    ],
]);
```

In the Crud Bundle we use the [Bootstrap Icons](https://icons.getbootstrap.com/).

### Add a definition to the menu

```php
$menu = $this->factory->createItem('');
$this->addDefinition($menu, CustomerDefinition::class);
```

### Add a definition menu with filter

For example, we want to make a navigation point which shows me all orders in open status. This can be done as follows:

```php
$this->addDefinition($doctorsMenu, OrderDefinition::class, [
    self::OPT_ROUTE_PARAMETERS => [
        'index_filter_column[0][0]' => 'status',
        'index_filter_operator[0][0]' => 'equal',
        'index_filter_value[0][0]' => 'open',
    ]
], 'Open Orders');
```

### Submenu
There is also a submenu, which is displayed at the very bottom of the navigation. It is suitable for settings, for example.

```php
public function createSubMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('Settings');
        $this->addDefinition($menu, UserSettingsDefinition::class);

        return $menu;
    }
}
```

## Options
[php-doc-parser(whatwedo/CrudBundle:src/Builder/DefinitionMenuBuilder.php:public const OPT_)]

