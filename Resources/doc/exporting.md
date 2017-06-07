# Exporting
The CrudBundle allows you to easily export your data to CSV files. For doing so follow these steps:

1: Enable the Export Route in your Definition:
```
    /**
     * {@inheritdoc}
     */
    public static function getCapabilities()
    {
        return [
            RouteEnum::INDEX,
            RouteEnum::SHOW,
            RouteEnum::DELETE,
            RouteEnum::EDIT,
            RouteEnum::CREATE,
            RouteEnum::EXPORT			<----- Export Route
        ];
    }
```

2: Choose which attributes to export. Override `getExportAttributes()` in your Definition: (Attributes need to have a getter in your entity class)
```
    public function getExportAttributes()
    {
        return ['attrname', 'attrname2'];
    }
```

You now see a export button at the bottom of your table.


## Customization

To format your output like a DateTime use Export Callbacks like follwing:
```
    public function getExportCallbacks()
    {
        return [
            'attrname' => function ($attr, $entity) {
            	// TODO: customize
                return $attr;
            }
        ];
    }
```

To override the headers use Export Headers:
```
    public function getExportHeaders()
    {
        return [
            'attrname' => 'Label Attrname',
            'attrname2' => 'Label Attrname 2'
        ];
    }
```

To override specific Csv Options like delimiter use Export Options:
```
    /**
     * @return array
     */
    public function getExportOptions()
    {
        return array_merge([
            'csv' => [
                'delimiter' => "\t"			<--- use tabs instead of ";" 
            ]
        ], parent::getExportOptions());
    }
```
You can override following options (these are also the default values):
```
	'delimiter'     => ';',
	'enclosure'     => '"',
	'escapeChar'    => '\\',
	'keySeparator'  => '.'
```
