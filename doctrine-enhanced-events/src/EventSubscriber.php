<?php
/**
 * Copyright (c) 2017-present DarkWeb Design
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

declare(strict_types=1);

namespace DarkWebDesign\DoctrineEnhancedEvents;

use Doctrine\Common\EventSubscriber as DoctrineEventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events as DoctrineEvents;
use Doctrine\ORM\UnitOfWork;

/**
 * @author Raymond Schouten
 *
 * @since 2.4
 */
class EventSubscriber implements DoctrineEventSubscriber
{
    /** @var array */
    private $entityInsertions = [];

    /** @var array */
    private $entityUpdates = [];

    /** @var array */
    private $entityDeletions = [];

    /**
     * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $eventManager = $entityManager->getEventManager();
        $connection = $entityManager->getConnection();
        $transactionNestingLevel = $connection->getTransactionNestingLevel() + 1;

        $this->cacheContext($entityManager);

        $eventArgs = new FlushEventArgs(
            $this->entityInsertions[$transactionNestingLevel],
            $this->entityUpdates[$transactionNestingLevel],
            $this->entityDeletions[$transactionNestingLevel],
            $entityManager
        );

        $eventManager->dispatchEvent(Events::onFlushEnhanced, $eventArgs);

        $originalEntityDeletions = $this->entityDeletions[$transactionNestingLevel] ?? [];

        $this->cacheContext($entityManager);

        $entityDeletions = array_merge($originalEntityDeletions, $this->entityDeletions[$transactionNestingLevel]);

        foreach ($this->entityInsertions[$transactionNestingLevel] as $entity) {
            $this->computeChangeSet($entityManager, $entity);
        }

        foreach ($this->entityUpdates[$transactionNestingLevel] as $entityUpdate) {
            $this->computeChangeSet($entityManager, $entityUpdate[1]);
        }

        foreach ($entityDeletions as $entity) {
            if ($this->computeChangeSet($entityManager, $entity)) {
                $this->addEntityUpdate($entityManager, $entity);
            }
        }
    }

    /**
     * @param \Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        $entityManager = $eventArgs->getEntityManager();
        $eventManager = $entityManager->getEventManager();
        $connection = $entityManager->getConnection();
        $transactionNestingLevel = $connection->getTransactionNestingLevel();

        $objectHash = spl_object_hash($entity);
        $originalEntity = $this->entityUpdates[$transactionNestingLevel][$objectHash][0];

        $eventArgs = new UpdateEventArgs($entity, $originalEntity, $entityManager);

        $eventManager->dispatchEvent(Events::preUpdateEnhanced, $eventArgs);
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $eventArgs
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        $entityManager = $eventArgs->getEntityManager();
        $eventManager = $entityManager->getEventManager();
        $connection = $entityManager->getConnection();
        $transactionNestingLevel = $connection->getTransactionNestingLevel();

        $objectHash = spl_object_hash($entity);
        $originalEntity = $this->entityUpdates[$transactionNestingLevel][$objectHash][0];

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
        $connection = $entityManager->getConnection();
        $transactionNestingLevel = $connection->getTransactionNestingLevel() + 1;

        $eventArgs = new FlushEventArgs(
            $this->entityInsertions[$transactionNestingLevel],
            $this->entityUpdates[$transactionNestingLevel],
            $this->entityDeletions[$transactionNestingLevel],
            $entityManager
        );

        $eventManager->dispatchEvent(Events::postFlushEnhanced, $eventArgs);

        unset($this->entityInsertions[$transactionNestingLevel]);
        unset($this->entityUpdates[$transactionNestingLevel]);
        unset($this->entityDeletions[$transactionNestingLevel]);
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    private function cacheContext(EntityManager $entityManager)
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $connection = $entityManager->getConnection();
        $transactionNestingLevel = $connection->getTransactionNestingLevel() + 1;

        $scheduledEntityInsertions = $unitOfWork->getScheduledEntityInsertions();
        $scheduledEntityUpdates = $unitOfWork->getScheduledEntityUpdates();
        $scheduledEntityDeletions = $unitOfWork->getScheduledEntityDeletions();

        $this->entityInsertions[$transactionNestingLevel] = $scheduledEntityInsertions;
        $this->entityUpdates[$transactionNestingLevel] = [];
        $this->entityDeletions[$transactionNestingLevel] = $scheduledEntityDeletions;

        foreach ($scheduledEntityUpdates as $entity) {
            $this->addEntityUpdate($entityManager, $entity);
        }
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param object $entity
     */
    private function addEntityUpdate(EntityManager $entityManager, $entity)
    {
        $connection = $entityManager->getConnection();
        $transactionNestingLevel = $connection->getTransactionNestingLevel() + 1;

        $objectHash = spl_object_hash($entity);
        $originalEntity = $this->getOriginalEntity($entityManager, $entity);
        $this->entityUpdates[$transactionNestingLevel][$objectHash] = [$originalEntity, $entity];
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param object $entity
     *
     * @return object
     */
    private function getOriginalEntity(EntityManager $entityManager, $entity)
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $entityChangeSet = $unitOfWork->getEntityChangeSet($entity);

        $className = get_class($entity);
        $classMetaData = $entityManager->getClassMetadata($className);

        $originalEntity = clone $entity;

        foreach ($entityChangeSet as $field => $values) {
            $classMetaData->reflFields[$field]->setValue($originalEntity, $values[0]);
        }

        return $originalEntity;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param object $entity
     *
     * @return bool
     */
    private function computeChangeSet(EntityManager $entityManager, $entity)
    {
        $unitOfWork = $entityManager->getUnitOfWork();

        if ($unitOfWork->getEntityState($entity) !== UnitOfWork::STATE_MANAGED) {
            return false;
        }

        $className = get_class($entity);
        $classMetaData = $entityManager->getClassMetadata($className);

        if ($unitOfWork->getOriginalEntityData($entity)) {
            $unitOfWork->recomputeSingleEntityChangeSet($classMetaData, $entity);
        } else {
            $unitOfWork->computeChangeSet($classMetaData, $entity);
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            DoctrineEvents::onFlush,
            DoctrineEvents::preUpdate,
            DoctrineEvents::postUpdate,
            DoctrineEvents::postFlush,
        ];
    }
}
