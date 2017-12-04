<?php
/**
 * Copyright (c) 2017 DarkWeb Design
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace DarkWebDesign\DoctrineEnhanced\Event;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber as DoctrineEventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events as DoctrineEvents;

/**
 * @author Raymond Schouten
 *
 * @since 2.2
 */
class EventSubscriber implements DoctrineEventSubscriber
{
    /** @var \Doctrine\Common\EventManager */
    private $eventManager;

    /** @var object[] */
    private $originalEntities = array();

    /**
     * @param \Doctrine\Common\EventManager $eventManager
     */
    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @param \Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        $entityChangeSet = $eventArgs->getEntityChangeSet();
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $className = get_class($entity);
        $classMetaData = $entityManager->getClassMetadata($className);

        $originalObject = clone $entity;

        foreach ($entityChangeSet as $field => $values) {
            $classMetaData->reflFields[$field]->setValue($originalObject, $values[0]);
        }

        $this->originalEntities[spl_object_hash($entity)] = $originalObject;

        $eventArgs = new UpdateEventArgs($entity, $originalObject, $entityManager);

        $this->eventManager->dispatchEvent(Events::PRE_UPDATE_ENHANCED, $eventArgs);

        $unitOfWork->recomputeSingleEntityChangeSet($classMetaData, $entity);
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        $originalEntity = $this->originalEntities[spl_object_hash($entity)];
        $entityManager = $eventArgs->getEntityManager();

        unset($this->originalEntities[spl_object_hash($entity)]);

        $eventArgs = new UpdateEventArgs($entity, $originalEntity, $entityManager);

        $this->eventManager->dispatchEvent(Events::POST_UPDATE_ENHANCED, $eventArgs);
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            DoctrineEvents::preUpdate,
            DoctrineEvents::postUpdate,
        );
    }
}
