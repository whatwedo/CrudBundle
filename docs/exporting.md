# Exporting
The CrudBundle allows you to easily export your data to CSV files. For doing so follow these steps:

1: Enable the export route in your definition:
```
    /**
     * {@inheritdoc}
     */
    public static function getCapabilities()
    {
        return [
            Page::INDEX,
            Page::SHOW,
            Page::DELETE,
            Page::EDIT,
            Page::CREATE,
            Page::EXPORT			<----- Export Route
        ];
    }
```

You now see an export button at the bottom of your table.

By default the table configuration will be exported.

## Customization

To define your custom export, just override the `configureExport` method. Create columns as you need them.

```   
    public function configureExport(Table $table)
    {
        $this->configureTable($table);

        $table->addColumn('id', null, [
            Column::OPTION_PRIORITY => 200
        ])
        ->addColumn('jobTitle');
    }
```



## Export Column options

```   
    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name', null, [
                Column::OPTION_EXPORT => [
                    Column::OPTION_EXPORT_EXPORTABLE => false
                    Column::OPTION_EXPORT_TEXTWRAP => true
                ]
            ])
        ;
    }
```   


