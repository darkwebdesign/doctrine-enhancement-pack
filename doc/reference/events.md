[Home](../index.md) /
[Reference Documents](index.md) /
Events

# Events

In order to be able to use the enhanced events, you have to register the Doctrine Enhanced Events event subcriber to the
event manager.

```php
use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber as EnhancedEventsSubscriber;
use Doctrine\Common\EventManager;

$eventManager = new EventManager();
$eventManager->addEventSubscriber(new EnhancedEventsSubscriber());
```

A basic event listener can be defined by listening to the `preUpdateEnhanced` and `postUpdateEnhanced` events.

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

A basic event subscriber can be defined by subscribing to the `preUpdateEnhanced` and `postUpdateEnhanced` events.

```php
$eventManager->addEventSubscriber(new MyEventSubscriber());
```

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
