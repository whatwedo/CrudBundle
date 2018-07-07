<?php


namespace whatwedo\CrudBundle\Form\ChoiceLoader;


use Symfony\Bridge\Doctrine\Form\ChoiceList\DoctrineChoiceLoader;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\FormEvent;

class AjaxDoctrineChoiceLoader extends DoctrineChoiceLoader
{
    private $selected = [];

    public function onFormPostSetData(FormEvent $event)
    {
        $data = $event->getData();
        $this->selected = is_iterable($data) ? $data : [$data];
    }

    public function loadChoiceList($value = null)
    {
        return new ArrayChoiceList($this->selected, $value);
    }
}