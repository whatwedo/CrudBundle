<?php


namespace whatwedo\CrudBundle\Action;


class PostAction extends Action implements IdentityableActionInterface
{
    public function getBlockPrefix(): string
    {
        return 'post';
    }
}
