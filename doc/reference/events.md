[Home](../index.md) /
[Reference Documents](index.md) /
Events

# Events

## Enabling enhanced events

In order to use the enhanced events, you have to register the Doctrine Enhanced Events event subscriber to the
EventManager that is passed to the EntityManager factory.

```php
use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber as EnhancedEventsSubscriber;

$eventManager = new EventManager();
$eventManager->addEventSubscriber(new EnhancedEventsSubscriber());

$entityManager = EntityManager::create($connection, $config, $eventManager);
```

You can also retrieve the event manager instance after the EntityManager was created.

```php
use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber as EnhancedEventsSubscriber;

$eventManager = $entityManager->getEventManager();
$eventManager->addEventSubscriber(new EnhancedEventsSubscriber());
```

## Listening and subscribing to enhanced events

A basic lifecycle event listener can be defined and registered as follows.

```php
class MyEventListener
{
    public function preUpdateEnhanced()
    {
        // ...
    }

    public function postUpdateEnhanced()
    {
        // ...
    }
}
```

```php
use DarkWebDesign\DoctrineEnhancedEvents\Events as EnhancedEvents;

$eventManager->addEventListener(
    array(
        EnhancedEvents::preUpdateEnhanced,
        EnhancedEvents::postUpdateEnhanced,
    ),
    new MyEventListener()
);
```

A basic lifecycle event subscriber can be defined and registered as follows.

```php
use DarkWebDesign\DoctrineEnhancedEvents\Events as EnhancedEvents;
use Doctrine\Common\EventSubscriber;

class MyEventSubscriber implements EventSubscriber
{
    public function preUpdateEnhanced()
    {
        // ...
    }

    public function postUpdateEnhanced()
    {
        // ...
    }

    public function getSubscribedEvents()
    {
        return array(
            EnhancedEvents::preUpdateEnhanced,
            EnhancedEvents::postUpdateEnhanced,
        );
    }
}
```

```php
$eventManager->addEventSubscriber(new MyEventSubscriber());
```

## preUpdateEnhanced, postUpdateEnhanced

Via the `EnhancedUpdateEventArgs` you have access to the original entity, which can be used to compare changes.

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

The updated entity can be changed via the `preUpdateEnhanced` event. The changes will be automatically persisted after the
event. Only properties of the updated entity itself can be changed, changed properties of relations are ignored.

```php
use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs as EnhancedUpdateEventArgs;

class MyEventListener
{
    public function preUpdateEnhanced(EnhancedUpdateEventArgs $event)
    {
        $entity = $event->getEntity();
        $entity->setModifiedAt(new \DateTime());
    }
}
```
