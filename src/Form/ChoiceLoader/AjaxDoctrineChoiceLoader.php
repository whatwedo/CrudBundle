<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Form\ChoiceLoader;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\FormEvent;

class AjaxDoctrineChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var array|Collection
     */
    private $selected = [];

    public function __construct(
        protected ChoiceLoaderInterface $doctrineChoiceLoader
    ) {
    }

    public function onFormPostSetData(FormEvent $event): void
    {
        $data = $event->getForm()->getData();
        if ($data) {
            $this->selected = is_iterable($data) ? $data : [$data];
        } else {
            $this->selected = [];
        }
    }

    public function loadChoiceList(callable $value = null): ChoiceListInterface
    {
        return new ArrayChoiceList($this->selected, $value);
    }

    public function loadChoicesForValues(array $values, callable $value = null): array
    {
        return $this->doctrineChoiceLoader->loadChoicesForValues($values, $value);
    }

    public function loadValuesForChoices(array $choices, callable $value = null): array
    {
        return $this->doctrineChoiceLoader->loadValuesForChoices($choices, $value);
    }
}
