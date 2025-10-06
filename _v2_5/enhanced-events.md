---
layout: default
title: Enhanced Events
---

# Enhanced Events

## Enabling enhanced events

In order to use the enhanced events, you have to register the Doctrine Enhanced Events event subscriber to the
`EventManager` that is passed to the `EntityManager` factory:

```php
use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber as EnhancedEventsSubscriber;

$eventManager = new EventManager();
$eventManager->addEventSubscriber(new EnhancedEventsSubscriber());

$entityManager = EntityManager::create($connection, $config, $eventManager);
```

You can also retrieve the `EventManager` instance after the `EntityManager` was created:

```php
use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber as EnhancedEventsSubscriber;

$eventManager = $entityManager->getEventManager();
$eventManager->addEventSubscriber(new EnhancedEventsSubscriber());
```

## Listening and subscribing to enhanced events

A basic lifecycle event listener can be defined and registered as follows:

```php
class MyEventListener
{
    public function onUpdateEnhanced()
    {
        // ...
    }

    public function preUpdateEnhanced()
    {
        // ...
    }

    public function postUpdateEnhanced()
    {
        // ...
    }

    public function postFlushEnhanced()
    {
        // ...
    }
}
```

```php
use DarkWebDesign\DoctrineEnhancedEvents\Events as EnhancedEvents;

$eventManager->addEventListener(
    [
        EnhancedEvents::onFlushEnhanced,
        EnhancedEvents::preUpdateEnhanced,
        EnhancedEvents::postUpdateEnhanced,
        EnhancedEvents::postFlushEnhanced,
    ],
    new MyEventListener()
);
```

A basic lifecycle event subscriber can be defined and registered as follows:

```php
use DarkWebDesign\DoctrineEnhancedEvents\Events as EnhancedEvents;
use Doctrine\Common\EventSubscriber;

class MyEventSubscriber implements EventSubscriber
{
    public function onUpdateEnhanced()
    {
        // ...
    }

    public function preUpdateEnhanced()
    {
        // ...
    }

    public function postUpdateEnhanced()
    {
        // ...
    }

    public function postFlushEnhanced()
    {
        // ...
    }

    public function getSubscribedEvents()
    {
        return [
            EnhancedEvents::onFlushEnhanced,
            EnhancedEvents::preUpdateEnhanced,
            EnhancedEvents::postUpdateEnhanced,
            EnhancedEvents::postFlushEnhanced,
        ];
    }
}
```

```php
$eventManager->addEventSubscriber(new MyEventSubscriber());
```

## preUpdateEnhanced, postUpdateEnhanced

Via the `EnhancedUpdateEventArgs` you have access to the original entity, which can be used to compare changes:

```php
use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs as EnhancedUpdateEventArgs;

class MyEventListener
{
    public function preUpdateEnhanced(EnhancedUpdateEventArgs $event)
    {
        $entity = $event->getEntity();
        $originalEntity = $event->getOriginalEntity();
    
        if ($entity->getUsername() !== $originalEntity->getUsername()) {
            // Do something when the username is changed.
        }
    }
}
```

The updated entity can be changed via the `preUpdateEnhanced` event. The changes will be automatically persisted after
the event. Only properties of the updated entity itself can be changed, changed properties of relations are ignored:

```php
use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs as EnhancedUpdateEventArgs;

class MyEventListener
{
    public function preUpdateEnhanced(EnhancedUpdateEventArgs $event)
    {
        $entity = $event->getEntity();
        $entity->setModifiedAt(new DateTime());
    }
}
```

## onFlushEnhanced, postFlushEnhanced

Via the `EnhancedFlushEventArgs` you have access to the created, updated (including original entities, which can be used
to compare changes) and deleted entities:

```php
use DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs as EnhancedFlushEventArgs;

class MyEventListener
{
    public function onFlushEnhanced(EnhancedFlushEventArgs $event)
    {
        foreach ($event->getEntityUpdates() as $entityUpdate) {
            list ($originalEntity, $entity) = $entityUpdate;

            if ($entity->getUsername() !== $originalEntity->getUsername()) {
                // Do something when the username is changed.
            }
        }
    }
}
```

The created, updated and deleted entities can be changed via the `onFlushEnhanced` event. The changes will be
automatically persisted after the event. Only properties of the entities itself can be changed, changed properties of
relations are ignored:

```php
use DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs as EnhancedFlushEventArgs;

class MyEventListener
{
    public function onFlushEnhanced(EnhancedFlushEventArgs $event)
    {
        foreach ($event->getEntityUpdates() as $entityUpdate) {
            list ($originalEntity, $entity) = $entityUpdate;

            $entity->setModifiedAt(new DateTime());
        }
    }
}
```

In case you want to update an entity that was not yet managed during the initial `onFlushEnhanced` event, you need to
manually recompute the entity changeset in order to include the entity changes in the same transaction:

```php
use DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs as EnhancedFlushEventArgs;

class MyEventListener
{
    public function onFlushEnhanced(EnhancedFlushEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        $entityRepository = $entityManager->getRepository('App\Entity\MyEntity');

        $entity = $entityRepository->find(1);
        $entity->setModifiedAt(new DateTime());

        $entityManager->persist($entity);

        $classMetaData = $entityManager->getClassMetadata('App\Entity\MyEntity');
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->recomputeSingleEntityChangeSet($classMetaData, $entity);
    }
}
```
