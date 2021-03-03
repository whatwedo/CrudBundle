<?php

namespace whatwedo\CrudBundle\Form\ChoiceLoader;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\ChoiceList\Factory\Cache\ChoiceLoader;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\FormEvent;

class AjaxDoctrineChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var array|Collection
     */
    private $selected = [];

    /**
     * @var ChoiceLoader
     */
    private $doctrineChoiceLoader;

    public function __construct(ChoiceLoader $doctrineChoiceLoader)
    {
        $this->doctrineChoiceLoader = $doctrineChoiceLoader;
    }

    public function onFormPostSetData(FormEvent $event)
    {
        $data = $event->getData();
        $this->selected = is_iterable($data) ? $data : [$data];
    }

    public function loadChoiceList($value = null)
    {
        return new ArrayChoiceList($this->selected, $value);
    }

    public function loadChoicesForValues(array $values, $value = null)
    {
        return $this->doctrineChoiceLoader->loadChoicesForValues($values, $value);
    }

    public function loadValuesForChoices(array $choices, $value = null)
    {
        return $this->doctrineChoiceLoader->loadValuesForChoices($choices, $value);
    }
}
