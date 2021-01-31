<?php


namespace whatwedo\CrudBundle\Action;


interface ActionInterface
{

    public static function new(?string $label = null);

    public function getLabel(): string;
    public function setLabel(string $label): self;

    public function getIcon(): string;
    public function setIcon(string $icon): self;

    public function getClass(): string;
    public function setClass(string $class): self;

    public function getRoute(): string;
    public function setRoute(string $route): self;

    public function getRouteParameters(): array;
    public function setRouteParameters(array $route_parameters): self;

    public function getVoterAttribute();
    public function setVoterAttribute($voter_attribute): self;

    public function getBlockPrefix(): string;
    public function setBlockPrefix(string $block_prefix): self;

    public function getAttributes(): array;
    public function setAttributes(array $attributes): self;

    public function getData();
    public function setData($data): self;


}
