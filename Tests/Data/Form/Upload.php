<?php
namespace whatwedo\CrudBundle\Tests\Data\Form;

class Upload
{
    private string $path;
    public function __construct(string $path, string $fieldName = 'file')
    {
        $this->path = $path;
        $this->field = $fieldName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getField(): string
    {
        return $this->field;
    }
}