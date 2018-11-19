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
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events as DoctrineEvents;

/**
 * @author Raymond Schouten
 *
 * @since 2.4
 */
class EventSubscriber implements DoctrineEventSubscriber
{
    /** @var array */
    private $entityInsertions = array();

    /** @var array */
    private $entityUpdates = array();

    /** @var array */
    private $entityDeletions = array();

    /**
     * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $eventManager = $entityManager->getEventManager();

        $entityInsertions = $unitOfWork->getScheduledEntityInsertions();
        $entityUpdates = $unitOfWork->getScheduledEntityUpdates();
        $entityDeletions = $unitOfWork->getScheduledEntityDeletions();

        $this->entityInsertions = $entityInsertions;
        $this->entityUpdates = array();
        $this->entityDeletions = $entityDeletions;

        foreach ($entityUpdates as $objectHash => $entity) {
            $originalEntity = clone $entity;
            $entityChangeSet = $unitOfWork->getEntityChangeSet($entity);
            $className = get_class($entity);
            $classMetaData = $entityManager->getClassMetadata($className);

            foreach ($entityChangeSet as $field => $values) {
                $classMetaData->reflFields[$field]->setValue($originalEntity, $values[0]);
            }

            $this->entityUpdates[$objectHash] = array($originalEntity, $entity);
        }

        $eventArgs = new FlushEventArgs($this->entityInsertions, $this->entityUpdates, $this->entityDeletions, $entityManager);

        $eventManager->dispatchEvent(Events::onFlushEnhanced, $eventArgs);

        $unitOfWork->computeChangeSets();
    }

    /**
     * @param \Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $className = get_class($entity);
        $classMetaData = $entityManager->getClassMetadata($className);
        $eventManager = $entityManager->getEventManager();

        $originalEntity = $this->entityUpdates[spl_object_hash($entity)][0];

        $eventArgs = new UpdateEventArgs($entity, $originalEntity, $entityManager);

        $eventManager->dispatchEvent(Events::preUpdateEnhanced, $eventArgs);

        $unitOfWork->recomputeSingleEntityChangeSet($classMetaData, $entity);
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        $entityManager = $eventArgs->getEntityManager();
        $eventManager = $entityManager->getEventManager();

        $originalEntity = $this->entityUpdates[spl_object_hash($entity)][0];

        $eventArgs = new UpdateEventArgs($entity, $originalEntity, $entityManager);

        $eventManager->dispatchEvent(Events::postUpdateEnhanced, $eventArgs);
    }

    /**
     * @param \Doctrine\ORM\Event\PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $eventManager = $entityManager->getEventManager();

        $eventArgs = new FlushEventArgs($this->entityInsertions, $this->entityUpdates, $this->entityDeletions, $entityManager);

        $eventManager->dispatchEvent(Events::postFlushEnhanced, $eventArgs);

        $this->entityInsertions = array();
        $this->entityUpdates = array();
        $this->entityDeletions = array();
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            DoctrineEvents::onFlush,
            DoctrineEvents::preUpdate,
            DoctrineEvents::postUpdate,
            DoctrineEvents::postFlush,
        );
    }
}
