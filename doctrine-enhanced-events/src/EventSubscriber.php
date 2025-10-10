<?php
/**
 * Copyright (c) 2017-present DarkWeb Design.
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
    /** @var array<int, array<string, object>> */
    private $entityInsertions = [];

    /** @var array<int, array<string, array{object, object}>> */
    private $entityUpdates = [];

    /** @var array<int, array<string, object>> */
    private $entityDeletions = [];

    public function onFlush(OnFlushEventArgs $eventArgs): void
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

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
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

    public function postUpdate(LifecycleEventArgs $eventArgs): void
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

    public function postFlush(PostFlushEventArgs $eventArgs): void
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

        unset(
            $this->entityInsertions[$transactionNestingLevel],
            $this->entityUpdates[$transactionNestingLevel],
            $this->entityDeletions[$transactionNestingLevel]
        );
    }

    private function cacheContext(EntityManager $entityManager): void
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
     * @param object $entity
     */
    private function addEntityUpdate(EntityManager $entityManager, $entity): void
    {
        $connection = $entityManager->getConnection();
        $transactionNestingLevel = $connection->getTransactionNestingLevel() + 1;

        $objectHash = spl_object_hash($entity);
        $originalEntity = $this->getOriginalEntity($entityManager, $entity);
        $this->entityUpdates[$transactionNestingLevel][$objectHash] = [$originalEntity, $entity];
    }

    /**
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private function getOriginalEntity(EntityManager $entityManager, object $entity): object
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $entityChangeSet = $unitOfWork->getEntityChangeSet($entity);

        $className = get_class($entity);
        $classMetaData = $entityManager->getClassMetadata($className);

        $originalEntity = clone $entity;

        foreach ($entityChangeSet as $field => $values) {
            $classMetaData->setFieldValue($originalEntity, $field, $values[0]);
        }

        return $originalEntity;
    }

    private function computeChangeSet(EntityManager $entityManager, object $entity): bool
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

    public function getSubscribedEvents(): array
    {
        return [
            DoctrineEvents::onFlush,
            DoctrineEvents::preUpdate,
            DoctrineEvents::postUpdate,
            DoctrineEvents::postFlush,
        ];
    }
}
