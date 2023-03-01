# Events

There are some events, which are triggered while editing or creating entites.

## Events available

- `whatwedo_crud.pre_show`: Is triggered before the show action is executed.
- `whatwedo_crud.pre_create`: Is triggered before creating (persist / flush) an entity.
- `whatwedo_crud.post_create`: Is triggered after creating (persist / flush) an entity.
- `whatwedo_crud.create_show`: Is triggered on creating a new entity and showing the form.
- `whatwedo_crud.pre_edit_form_creation`: Is triggered before creating the edit form.
- `whatwedo_crud.pre_edit`: Is triggered before saving (persist / flush) an entity.
- `whatwedo_crud.post_edit`: Is triggered after saving (persist / flush) an entity.
- `whatwedo_crud.new`: Is triggered before creating a new entity.
- `whatwedo_crud.pre_validate`: Is triggered before validating an entity.
- `whatwedo_crud.post_validate`: Is triggered after validating an entity.
- `whatwedo_crud.pre_delete`: Is triggered before deleting an entity.
- `whatwedo_crud.post_delete`: Is triggered after deleting an entity.

Each event can be suffixed with the definition alias to only get events for a specific definition.
For example: `whatwedo_crud.pre_show.my_definition_alias`.

## Using events

In this example, every time an administrator is adding a new entry into the user's history, the current user is added.

It is only triggered in the user history entity.

```php
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

This example uses event listeners defined in the services.yaml file.
You can also use Event Subscriber with the same result.
See the official docs for more information: https://symfony.com/doc/current/event_dispatcher.html

```php
# config/services.yml

services:
    agency_user.event_listener.history_create:
        class: App\EventListener\HistoryCreateEventListener
        arguments:
            - '@security.token_storage'
        tags:
            - { name: kernel.event_listener, event: whatwedo_crud.pre_create.agency_user_history, method: historyEntryCreate }

```
