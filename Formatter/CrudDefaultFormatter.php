<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Formatter;

use Symfony\Component\Routing\RouterInterface;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\CoreBundle\Formatter\DefaultFormatter;

class CrudDefaultFormatter extends DefaultFormatter
{
    public function __construct(
        protected DefinitionManager $definitionManager,
        protected RouterInterface $router
    ) { }

    public function getHtml($value): string
    {
        if (is_object($value)
            && ($definition = $this->definitionManager->getDefinitionByEntity($value))) {
            return sprintf(
                '<a href="%s">%s</a>',
                $this->router->generate($definition::getRoute(Page::SHOW), ['id' => $value->getId()]),
                (string)$value
            );
        }

        return parent::getHtml($value);
    }
}
