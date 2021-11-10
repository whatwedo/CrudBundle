<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Action;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CrudBundle\Enum\Page;
use whatwedo\CrudBundle\Enum\VisibilityEnum;

class Action
{
    protected array $defaultOptions = [
        'label' => null,
        'attr' => null,
        'icon' => null,
        'route' => null,
        'route_parameters' => [],
        'voter_attribute' => null,
        'visibility' => [Page::INDEX, Page::SHOW, Page::EDIT, Page::CREATE],
        'priority' => 0,
    ];

    /**
     * it's possible to pass functions as option value to create dynamic labels, routes and more.
     * TODO: create docs
     */
    public function __construct(protected $acronym, protected array $options) {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array_merge([
            'block_prefix' => StringUtil::fqcnToBlockPrefix(static::class),
        ], $this->defaultOptions));

        $this->options = $resolver->resolve($this->options);
    }

    public function getOption(string $name)
    {
        if (!$this->hasOption($name)) {
            throw new InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
        }

        return $this->options[$name];
    }

    public function setOption($name, $value): static
    {
        if (!$this->hasOption($name)) {
            throw new InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
        }

        $this->options[$name] = $value;

        return $this;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]) || array_key_exists($name, $this->options);
    }
}