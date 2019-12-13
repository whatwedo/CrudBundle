<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\CrudBundle\Event;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class CrudEvent extends Event
{
    const PRE_CREATE_PREFIX = 'whatwedo_crud.pre_create';
    const POST_CREATE_PREFIX = 'whatwedo_crud.post_create';
    const PRE_DELETE_PREFIX = 'whatwedo_crud.pre_delete';
    const POST_DELETE_PREFIX = 'whatwedo_crud.post_delete';
    const PRE_EDIT_PREFIX = 'whatwedo_crud.pre_edit';
    const POST_EDIT_PREFIX = 'whatwedo_crud.post_edit';
    const PRE_VALIDATE_PREFIX = 'whatwedo_crud.pre_validate';
    const POST_VALIDATE_PREFIX = 'whatwedo_crud.post_validate';
    const PRE_SHOW_PREFIX = 'whatwedo_crud.pre_show';
    const CREATE_SHOW_PREFIX = 'whatwedo_crud.create_show';

    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}
