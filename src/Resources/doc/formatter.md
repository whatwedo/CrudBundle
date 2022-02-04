# Formatter

Formatter are simple classes to convert a given variable to a string. 

You can create your own formatter (you must implement `whatwedo\CoreBundle\Formatter\FormatterInterface` or extend `whatwedo\CoreBundle\Formatter\AbstractFormatter`) and add the full class name of your formatter to the options array of a content element.

## Register
Your formatters needs to be registered as service with the tag `core.formatter`. For example:
```
    Acme\AppBundle\Formatter\:
        resource: '../../Formatter'
        tags:
            - core.formatter
```

## `EmailFormatter`

```
<?php

namespace whatwedo\CoreBundle\Formatter;

class EmailFormatter extends AbstractFormatter
{
    /**
     * {@inheritdoc}
     */
    public static function getString($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function getHtml($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        return sprintf(
            '<a href="mailto:%s" title="E-Mail senden">%s</a>',
            $value,
            $value
        );
    }
}
```
