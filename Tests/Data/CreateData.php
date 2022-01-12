<?php

namespace whatwedo\CrudBundle\Tests\Data;

class CreateData
{
    public function __construct(
        public bool $skip = false,
        public array $queryParameters = [],
        public bool $fillForm = true,
        public array $formData = []
    )
    {
    }

    public static function new(): self
    {
        return new self();
    }

    public function setSkip(bool $skip): self
    {
        $this->skip = $skip;
        return $this;
    }

    public function setQueryParameters(array $queryParameters): self
    {
        $this->queryParameters = $queryParameters;
        return $this;
    }

    public function setFillForm(bool $fillForm): self
    {
        $this->fillForm = $fillForm;
        return $this;
    }

    public function setFormData(array $formData): self
    {
        $this->formData = $formData;
        return $this;
    }
}
