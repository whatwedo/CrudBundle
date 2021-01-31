<?php


namespace whatwedo\CrudBundle\Action;


use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class Action implements ActionInterface
{
    protected $data = null;
    protected string $label = '';
    protected string $icon  = '';
    protected string $class = '';
    protected string $route = '';
    protected array $route_parameters = [];
    protected $voter_attribute= null;
    protected $block_prefix= '';
    protected array $attributes = [];

    public static function new(?string $label = null): self
    {
        return (new static())
            ->setLabel($label);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getRouteParameters(): array
    {
        return $this->route_parameters;
    }

    public function setRouteParameters(array $route_parameters): self
    {
        $this->route_parameters = $route_parameters;
        return $this;
    }

    public function getVoterAttribute()
    {
        return $this->voter_attribute;
    }

    public function setVoterAttribute($voter_attribute): self
    {
        $this->voter_attribute = $voter_attribute;
        return $this;
    }

    public function getBlockPrefix(): string
    {
        return $this->block_prefix;
    }

    public function setBlockPrefix(string $block_prefix): self
    {
        $this->block_prefix = $block_prefix;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): ActionInterface
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): self
    {
        $this->data = $data;
        return $this;
    }


}
