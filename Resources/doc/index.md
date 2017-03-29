# Getting Started

This documentation provides a basic view of the possibilities of the whatwedoCrudBundle. 
The documentation will be extended while developing the bundle.

## Requirements

This bundle has been tested on PHP >= 7.0 and Symfony >= 3.0. 
We don't guarantee that it works on lower versions.

## Templates

The views of this template are based on [AdminLTE](https://almsaeedstudio.com/) boxes. You can overwrite them at any time. 

## Installation

First, add the bundle to your dependencies and install it.

```
composer require whatwedo/crud-bundle
```

Secondly, enable this bundle and the whatwedoTableBundle in your kernel.
```
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new whatwedo\TableBundle\whatwedoTableBundle(),
        new whatwedo\CrudBundle\whatwedoCrudBundle(),
        // ...
    );
}
```

Thirdly, add our routes to your ```app/config/routing.yml```
```
whatwedo_crud_bundle:
    resource: "@whatwedoCrudBundle/Resources/config/routing.yml"
    prefix: /
```
    
## Use the bundle

### Step 1: Create an entity

First, you need to create a new entity for your data. In our example, we want to store all locations of our company.

```

<?php
// src/Agency/LocationBundle/Entity/Location.php

namespace Agency\LocationBundle\Entity;

/**
 * @ORM\Entity
 * @ORM\Table(name="location")
 */
class Location
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=120)
     * @Assert\NotBlank();
     */
    protected $name;

// ...

    public function __toString()
    {
        return $this->getName();
    }
}

```

### Step 2: Create a definition

In the definition file, you explain and configure your entity. 

It contains all information to create your CRUD view.

```
<?php
// src/Agency/LocationBundle/Definition/LocationDefinition.php

namespace Agency\LocationBundle\Definition;

use Agency\LocationBundle\Entity\Location;
use whatwedo\CrudBundle\Builder\DefinitionBuilder;
use whatwedo\CrudBundle\Definition\AbstractDefinition;
use whatwedo\TableBundle\Table\Table;

class LocationDefinition extends AbstractDefinition
{
    /**
     * an alias for your entity. also used for routing (alias_show, alias_create, ...)
     */
    public static function getAlias()
    {
        return 'oepfelchasper_location_location';
    }

    /**
     * the fqdn of the entity
     */
    public static function getEntity()
    {
        return Location::class;
    }

    /**
     * the query alias to be used when query data
     */
    public static function getQueryAlias()
    {
        return 'location';
    }

    /**
     * table (list) configuration
     */
    public function configureTable(Table $table)
    {
        // Your Table Configuration
    }

    /**
     * interface (create, edit, view) configuration
     */
    public function configureView(DefinitionBuilder $builder, $data)
    {
        // Your View Configuration
    }
}

```

You can read more about the Table configuration in the [whatwedoTableBundle](https://github.com/whatwedo/TableBundle). 

Check out our documentation for the [view configuration](view-configuration.md).

### Step 3: Create a service

Now, create a new service is your services.yml. Important: the alias given here is the same as defined in your definition-file!

```
# src/Agency/LocationBundle/Resources/services.yml
services:
    # Definitions
    agency_location.definition.location:
        class: Agency\LocationBundle\Definition\LocationDefinition
        parent: whatwedo_crud.definition.abstract_definition
        public: false
        tags:
            - { name: crud.definition, alias: agency_user_user } # put in the alias of your definition

```

### Step 4: Create routing

Now we need to tell our router that there are new routes. In our projects, we always create new routing-files for every controller - you can just put it in one file if you want.

```
# src/Agency/LocationBundle/Resources/config/routing.yml
agency_location_location_import:
    prefix: /location
    resource: "@AgencyLocationBundle/Resources/config/routing/location.yml"

# src/Agency/LocationBundle/Resources/config/routing/location.yml
agency_location_location_crud:
    resource: 'agency_location_location' # put in the alias of your definition
    type: crud

```

### Step 5: Try it

That's all.

### More resources

- [View Configuration](view-configuration.md)
- [Table Configuration](https://doc.whatwedo.ch/whatwedo/tablebundle/table-configuration)
- [Formatter](formatter.md)
- [Events](events.md)

### Extensions
- [Breadcrumbs](extensions/breadcrumbs.md)
