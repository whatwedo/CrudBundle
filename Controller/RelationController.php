<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
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

namespace whatwedo\CrudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use whatwedo\CrudBundle\Manager\DefinitionManager;
use whatwedo\TableBundle\Event\ResultRequestEvent;

/**
 * Class RelationController
 */
class RelationController extends AbstractController
{
    protected $eventDispatcher;

    /**
     * @var DefinitionManager
     */
    private $definitionManager;

    /**
     * RelationController constructor.
     * @param $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, DefinitionManager $definitionManager)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->definitionManager = $definitionManager;
    }

    /**
     * @return JsonResponse
     */
    public function ajaxAction(Request $request)
    {
        $entity = $request->get('entity', false);
        $term = $request->get('q', false);
        $resultRequestEvent = new ResultRequestEvent($entity, $term);

        $definition = $this->definitionManager->getDefinitionFromClass($entity) ?: $this->definitionManager->getDefinitionFromEntityClass($entity);
        if ($definition) {
            $resultRequestEvent->setEntity($definition::getEntity());
            $resultRequestEvent->setQueryBuilder($definition->getQueryBuilder());
        }

        $this->eventDispatcher->dispatch($resultRequestEvent, ResultRequestEvent::RELATION_SET);
        return $resultRequestEvent->getResult();
    }
}
