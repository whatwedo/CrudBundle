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
            Page::INDEX,
            Page::SHOW,
            Page::DELETE,
            Page::EDIT,
            Page::CREATE,
            Page::EXPORT			<----- Export Route
        ];
    }
```

You now see a export button at the bottom of your table.

By default the Table configuration will be exported.

## Customization

To export define your custom export, just override the `configureExport` method. Column as you need.

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
                ]
            ])
        ;
    }
```   


