# Table Configuration

For basic table configuration refer to the [WhatwedoTableBundle Documentations](https://doc.whatwedo.ch/whatwedo/tablebundle/table-configuration)

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


## Advanced Table Configuration with CrudBundle

### Filters
The CrudBundle will add filters automatically to your table. You can however override this behaviour. 

In your definition override the method `overrideTableConfiguration`:
```
...
public function overrideTableConfiguration(Table $table)
{
    // call parent to add automatically filters
    parent::overrideTableConfiguration($table);
    $table
        ->overrideFilterName('acronym', 'new Label')
        ->simpleEnumFilter('acronym', YourEnumClass::class)
        ->removeFilter('acronym')
    ;
}
...
```

***Attention:***
* These methods only work when the parent method was called as well!
* To use `simpleEnumFilter` your Enum Class needs to extend from `whatwedo\CoreBundle\Enum\AbstractSimpleEnum`

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
