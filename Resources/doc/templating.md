# Templating

## Layout Based Rendering

with Version v0.5 a new templating method is introduced. Like in Symfony form one layout file is introduced with can 
easily extended or overwirtten. The old rendering is still available a is the default rendering engine.  

To enable the layout based rendering you need to change the template path in the `config/packages/whatwedo_crud.yaml` config.

```
whatwedo_crud:
  templateDirectory: '@whatwedoCrud/Templates'
```

The template path can also change individually per Definition. 

```
class PostDefinition extends AbstractDefinition
{
...
    public function getTemplateDirectory(): string
    {
        return 'myTemplates';
    }

```

## Layout Files

The Layout file definies how the crud will be rendered. The default layout file is `@whatwedoCrud/layout/adminlte_layout.html.twig` 
and can be changed in the config `config/packages/whatwedo_crud.yaml`

```
whatwedo_crud:
  layout: 'crud/layout/my_layout.html.twig'
```

Like Symfony Form the layout file can be extended or overwritten. New blocks can be added or overwritten. 

```
{% extends '@whatwedoCrud/layout/adminlte_layout.html.twig' %}

{% block crud_show %}
    my fancy show block
    {{ parent() }}
{% endblock %}

```

## Twig-Functions and Blocks


### Main Twig-Functions
| Twig-Function  | Default Blockname | with Blockprefix        | -----                     |
|--------------- |----------         | ----                    | -----                     |
|`crud_show`     |`show`             | N/A                     | `render_mode = 'edit'`    |
|`crud_create`   |`create`           | N/A                     | `render_mode = 'create'`  |
|`crud_edit`     |`edit`             | N/A                     | `render_mode = 'create'`  |
|`crud_table`    |`table`            | `<block_prefix>_table`  | `render_mode = 'create'`  |


### `crud_content_row` - Twig-Function 

The row block name will be dynamicly.   `<block_prefix>` and `<render_mode>` will be used to create the block name.

### `<block_prefix>`

The `block_prefix` can be set in with the content options.

```
class PostDefinition extends AbstractDefinition
{
....
        $builder
            ->getBlock('post')
            ->addContent(
                'content',
                null,
                [
                    'label' => 'post.content',
                    'block_prefix' => 'postcontent'
                ]
            )
        ;

```

By default, the `block_prefix` is the sneakcase of the class name. 

| Class                                         | Default Block - Prefix |
|---------------                                |-----------             |
|`\whatwedo\CrudBundle\Content\Content`         |`content`               |
|`\whatwedo\CrudBundle\Content\RelationContent` |`relation_content`      |
|`\whatwedo\CrudBundle\Content\TwigContent`     |`twig_content`          |
|`\whatwedo\CrudBundle\Content\Content`         |`content`               | 



### Table Rendering

| Twig-Function             | Default Blockname | with Blockprefix | ---   |
|---------------            | ----------        | ----                   | -----                     |
|`crud_table`               | `table`           | `<block_prefix>_table`<br> eg. `post_table`        |
|`crud_table_header_cell`   | `table_header`    | `<block_prefix>_header`<br> eg. `posttitle_header` |
|`crud_table_content_cell`  | `table_cell`      | `<block_prefix>_cell`<br> eg. `posttitle_cell`     |

