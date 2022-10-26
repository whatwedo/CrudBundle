# Events

There are some events, which are triggered while editing or creating entites.

## Events available

- `whatwedo_crud.pre_create`: Is triggered before creating (persist / flush) an entity.
- `whatwedo_crud.post_create`: Is triggered after creating (persist / flush) an entity.
- `whatwedo_crud.pre_edit`: Is triggered before saving (persist / flush) an entity.
- `whatwedo_crud.post_edit`: Is triggered after saving (persist / flush) an entity.
- `whatwedo_crud.pre_create.DEFINITION_ALIAS`: Is triggered before creating (persist / flush) a specific entity.
- `whatwedo_crud.post_create.DEFINITION_ALIAS`: Is triggered after creating (persist / flush) a specific entity.
- `whatwedo_crud.pre_edit.DEFINITION_ALIAS`: Is triggered before saving (persist / flush) a specific entity.
- `whatwedo_crud.post_edit.DEFINITION_ALIAS`: Is triggered after saving (persist / flush) a specific entity.

## Using events

In this example, every time an administrator is adding a new entry into the user's history, the current user is added.

It is only triggered in the user history entity.

```
// src/Agency/UserBundle/EventListener/HistoryCreateEventListener.php

namespace Agency\UserBundle\EventListener;

use Agency\UserBundle\Entity\History;
use Agency\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use whatwedo\CrudBundle\Event\CrudEvent;

class HistoryCreateEventListener
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function historyEntryCreate(CrudEvent $event)
    {
        if ($this->tokenStorage->getToken() instanceof TokenInterface
            && $this->tokenStorage->getToken()->getUser() instanceof User
            && $event->getEntity() instanceof History) {
            $event->getEntity()->setCreator($this->tokenStorage->getToken()->getUser());
        }
    }
}
```

```
# src/Agency/UserBundle/Resources/config/services.yml

services:
    agency_user.event_listener.history_create:
        class: Agency\UserBundle\EventListener\HistoryCreateEventListener
        arguments:
            - '@security.token_storage'
        tags:
            - { name: kernel.event_listener, event: whatwedo_crud.pre_create.agency_user_history, method: historyEntryCreate }

```
