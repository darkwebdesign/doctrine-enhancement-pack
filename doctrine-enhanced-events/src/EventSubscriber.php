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

namespace DarkWebDesign\DoctrineEnhancedEvents;

use Doctrine\Common\EventSubscriber as DoctrineEventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events as DoctrineEvents;

/**
 * @author Raymond Schouten
 *
 * @since 2.4
 */
class EventSubscriber implements DoctrineEventSubscriber
{
    /** @var object[] */
    private $originalEntities = array();

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
        $eventManager = $entityManager->getEventManager();

        $originalObject = clone $entity;

        foreach ($entityChangeSet as $field => $values) {
            $classMetaData->reflFields[$field]->setValue($originalObject, $values[0]);
        }

        $this->originalEntities[spl_object_hash($entity)] = $originalObject;

        $eventArgs = new UpdateEventArgs($entity, $originalObject, $entityManager);

        $eventManager->dispatchEvent(Events::preUpdateEnhanced, $eventArgs);

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
        $eventManager = $entityManager->getEventManager();

        unset($this->originalEntities[spl_object_hash($entity)]);

        $eventArgs = new UpdateEventArgs($entity, $originalEntity, $entityManager);

        $eventManager->dispatchEvent(Events::postUpdateEnhanced, $eventArgs);
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
