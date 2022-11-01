# Table Configuration

For a basic table configuration refer to the [WhatwedoTableBundle Documentations](https://whatwedo.github.io/TableBundle/#/)

```php

class LocationDefinition extends AbstractDefinition
{
....

    public function configureTable(Table $table)
    {
        $table
            ->addColumn(
                'name', 
                null, [
                    'label' => 'Name',
                ]
            )
            ->addColumn('zip', null, ['label' => 'ZIP']);
    }

...
}
```

## Filter with joins

It is possible to create filters based on columns that have to be joined.  
FilterTypes accept an array of filters.

### Automatically added Filters

explain how they are added automatically and how to customize them. 

### Custom Filters
#### Simple
Filter all rooms included in a house with a specific color.  
This filter would be applied on the `RoomDefinition`.
```php
public function overrideTableConfiguration(Table $table)
{
    $filterExtension = $table->getFilterExtension();

    $filterExtension->addFilter('roofColor', 'Roof Color',
        new AjaxRelationFilterType('houseRoof.color', HouseColor::class, $this->doctrine,
            [
                'house' => self::getQueryAlias().'house',
                'houseRoof' => 'house.roof'
            ]
        )
    );
}
```

### Advanced

In this example we join a ManyToOne (Room -> House) and then a OneToMany (House -> Furniture) relation.  
The goal is to filter all rooms contained in all houses which include a furniture with a specific status (StatusEnum).  
This filter would be applied on the `RoomDefinition` as well.  
```php
public function overrideTableConfiguration(Table $table)
{
    $filterExtension = $table->getFilterExtension();

    $filterExtension->addFilter('houseIncludesFurniture', 'House includes furniture', new SimpleEnumFilterType('houseFurniture.status', [
        'house' => ['innerJoin', self::getQueryAlias().'.house'],
        'houseFurniture' => ['innerJoin', Furniture::class, 'WITH', 'houseFurniture.house = house.id'],
    ], FurnitureStatus::class));
}
```

### Action Buttons
The CrudBundle will add two Action Buttons to each row (show / edit). To add more use the same method `overrideTableConfiguration`.

```
...
public function overrideTableConfiguration(Table $table)
{
    parent::overrideTableConfiguration($table);
    $table
        ->addActionItem('Label', 'icon', 'btn', 'route')
    ;
}
...
```
Where:
- `icon` = fa icon suffix (for "fa-clone" just write "clone")
- `btn` = button collor (default = grey, success = green, warning = yellow, danger = red, primary = blue)
- `route` = route name (CrudBundle will pass the entity "id" to the route generator)
