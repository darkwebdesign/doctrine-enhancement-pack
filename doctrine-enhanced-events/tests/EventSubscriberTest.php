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

namespace DarkWebDesign\DoctrineEnhancedEvents\Tests;

use DarkWebDesign\DoctrineEnhancedEvents\Events;
use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber;
use DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs;
use DarkWebDesign\DoctrineEnhancedEvents\Tests\Entities\Person;
use DarkWebDesign\DoctrineEnhancedEvents\Tests\Mocks\EventSubscriberMock;
use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber
 */
class EventSubscriberTest extends OrmFunctionalTestCase
{
    /** @var EntityRepository */
    private $repository;

    /** @var EventSubscriberMock|MockObject */
    private $eventSubscriberMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->entityManager->getRepository(Person::class);

        $this->eventSubscriberMock = $this->createMock(EventSubscriberMock::class);

        $this->eventSubscriberMock
            ->expects($this->any())
            ->method('getSubscribedEvents')
            ->will($this->returnValue([
                Events::onFlushEnhanced,
                Events::preUpdateEnhanced,
                Events::postUpdateEnhanced,
                Events::postFlushEnhanced,
            ]));

        $eventManager = $this->entityManager->getEventManager();
        $eventManager->addEventSubscriber(new EventSubscriber());
        $eventManager->addEventSubscriber($this->eventSubscriberMock);
    }

    public function testFlushEventArgs(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter, $danielleMurphy, $mikeKennedy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectHash, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectHash, $entityUpdates);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($mikeKennedy);
            $this->assertCount(1, $entityDeletions);
            $this->assertArrayHasKey($objectHash, $entityDeletions);
            $this->assertSame($mikeKennedy, $entityDeletions[$objectHash]);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($zoeyPorter);
        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->remove($mikeKennedy);
        $this->entityManager->flush();

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($mikeKennedy);
    }

    public function testUpdateEventArgs(): void
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $assertUpdateEventArgs = function (UpdateEventArgs $args) use ($danielleMurphy) {
            $this->assertSame($danielleMurphy, $args->getEntity());
            $this->assertSame('Danielle Murphy', $args->getOriginalEntity()->getName());

            $this->assertSame($args->getEntity(), $args->getObject());
            $this->assertSame($args->getOriginalEntity(), $args->getOriginalObject());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->flush();

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }

    public function testUpdateEntityInsertionOnFlush(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $updateEntity = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $entityInsertions[$objectHash]->setName('Zoey Dawson-Porter');

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectHash, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($updateEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($zoeyPorter);
        $this->entityManager->flush();

        $this->assertSame('Zoey Dawson-Porter', $zoeyPorter->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $zoeyDawsonPorter = $this->repository->findOneByName('Zoey Dawson-Porter');

        $this->assertNull($zoeyPorter);
        $this->assertNotNull($zoeyDawsonPorter);
    }

    public function testRemoveEntityInsertionOnFlush(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $removeEntity = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityManager = $args->getEntityManager();
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $entityManager->remove($entityInsertions[$objectHash]);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) {
            $entityInsertions = $args->getEntityInsertions();
            $this->assertEmpty($entityInsertions);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($removeEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($zoeyPorter);
        $this->entityManager->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');

        $this->assertNull($zoeyPorter);
    }

    public function testUpdateEntityUpdateOnFlush(): void
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $entityUpdates[$objectHash][1]->setName('Danielle Sanders');

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) {
            $this->assertSame('Danielle Sanders', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectHash, $entityUpdates);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($updateEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->flush();

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
        $this->assertNotNull($danielleSanders);
    }

    public function testRemoveEntityUpdateOnFlush(): void
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $removeEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityManager = $args->getEntityManager();
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $entityManager->remove($entityUpdates[$objectHash][1]);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $this->assertEmpty($entityUpdates);

            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($danielleMurphy);
            $this->assertCount(1, $entityDeletions);
            $this->assertArrayHasKey($objectHash, $entityDeletions);
            $this->assertSame($danielleMurphy, $entityDeletions[$objectHash]);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($removeEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->flush();

        $this->assertSame('Danielle Sanders-Murphy', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
    }

    public function testPersistEntityDeletionOnFlush(): void
    {
        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $persistEntity = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityManager = $args->getEntityManager();
            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($mikeKennedy);
            $entityManager->persist($entityDeletions[$objectHash]);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) {
            $entityDeletions = $args->getEntityDeletions();
            $this->assertEmpty($entityDeletions);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($persistEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->remove($mikeKennedy);
        $this->entityManager->flush();

        $this->assertSame('Mike Kennedy', $mikeKennedy->getName());

        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $this->assertNotNull($mikeKennedy);
    }

    public function testUpdateEntityDeletionOnFlush(): void
    {
        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $updateEntity = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityManager = $args->getEntityManager();
            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($mikeKennedy);
            $entityDeletions[$objectHash]->setName('Mike Jones');
            $entityManager->persist($entityDeletions[$objectHash]);

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) {
            $this->assertSame('Mike Jones', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($mikeKennedy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectHash, $entityUpdates);
            $this->assertSame($mikeKennedy, $entityUpdates[$objectHash][1]);
            $this->assertSame('Mike Kennedy', $entityUpdates[$objectHash][0]->getName());

            $entityDeletions = $args->getEntityDeletions();
            $this->assertEmpty($entityDeletions);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($updateEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->remove($mikeKennedy);
        $this->entityManager->flush();

        $this->assertSame('Mike Jones', $mikeKennedy->getName());

        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');
        $mikeJones = $this->repository->findOneByName('Mike Jones');

        $this->assertNull($mikeKennedy);
        $this->assertNotNull($mikeJones);
    }

    public function testPersistNewEntityOnFlush(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $rebeccaAnderson = new Person();

        $persistNewEntity = function (FlushEventArgs $args) use ($rebeccaAnderson) {
            $entityManager = $args->getEntityManager();
            $rebeccaAnderson->setName('Rebecca Anderson');
            $entityManager->persist($rebeccaAnderson);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter, $rebeccaAnderson) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $this->assertCount(2, $entityInsertions);
            $this->assertArrayHasKey($objectHash, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);
            $objectHash = spl_object_hash($rebeccaAnderson);
            $this->assertArrayHasKey($objectHash, $entityInsertions);
            $this->assertSame($rebeccaAnderson, $entityInsertions[$objectHash]);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($persistNewEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($zoeyPorter);
        $this->entityManager->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());
        $this->assertSame('Rebecca Anderson', $rebeccaAnderson->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $rebeccaAnderson = $this->repository->findOneByName('Rebecca Anderson');

        $this->assertNotNull($zoeyPorter);
        $this->assertNotNull($rebeccaAnderson);
    }

    public function testUpdateNewEntityOnFlushIgnored(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');

        $persistNewEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityManager = $args->getEntityManager();
            $danielleMurphy->setName('Danielle Sanders-Murphy');
            $entityManager->persist($danielleMurphy);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectHash, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            $entityUpdates = $args->getEntityUpdates();
            $this->assertEmpty($entityUpdates);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($persistNewEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($zoeyPorter);
        $this->entityManager->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());
        $this->assertSame('Danielle Sanders-Murphy', $danielleMurphy->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNotNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
    }

    public function testUpdateNewEntityOnFlushManualRecompute(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');

        $persistNewEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityManager = $args->getEntityManager();
            $danielleMurphy->setName('Danielle Sanders-Murphy');
            $entityManager->persist($danielleMurphy);

            $className = get_class($danielleMurphy);
            $classMetaData = $entityManager->getClassMetadata($className);
            $unitOfWork = $entityManager->getUnitOfWork();
            $unitOfWork->recomputeSingleEntityChangeSet($classMetaData, $danielleMurphy);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter, $danielleMurphy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectHash, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectHash, $entityUpdates);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($persistNewEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($zoeyPorter);
        $this->entityManager->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());
        $this->assertSame('Danielle Sanders-Murphy', $danielleMurphy->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }

    public function testRemoveNewEntityOnFlush(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $persistNewEntity = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityManager = $args->getEntityManager();
            $entityManager->remove($mikeKennedy);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter, $mikeKennedy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectHash, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($mikeKennedy);
            $this->assertCount(1, $entityDeletions);
            $this->assertArrayHasKey($objectHash, $entityDeletions);
            $this->assertSame($mikeKennedy, $entityDeletions[$objectHash]);

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($persistNewEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($zoeyPorter);
        $this->entityManager->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());
        $this->assertSame('Mike Kennedy', $mikeKennedy->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($mikeKennedy);
    }

    public function testUpdateEntityPreUpdate(): void
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (UpdateEventArgs $args) {
            $args->getEntity()->setName('Danielle Sanders');

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) {
            $this->assertSame('Danielle Sanders', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectHash, $entityUpdates);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($updateEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
        $this->assertNotNull($danielleSanders);
    }

    public function testUpdateEntityPostUpdateIgnored(): void
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (UpdateEventArgs $args) {
            $args->getEntity()->setName('Danielle Sanders');

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectHash, $entityUpdates);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($updateEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($danielleSanders);
    }

    public function testUpdateEntityPostFlushIgnored(): void
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $entityUpdates[$objectHash][1]->setName('Danielle Sanders');

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($updateEntity));

        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($danielleSanders);
    }

    public function testNestedFlushes(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $assertFlushEventArgsOnFlush = function (FlushEventArgs $args) use ($zoeyPorter, $danielleMurphy) {
            static $callCounter = 0;

            ++$callCounter;

            if ($callCounter === 1) {
                $entityUpdates = $args->getEntityUpdates();
                $objectHash = spl_object_hash($danielleMurphy);
                $this->assertCount(1, $entityUpdates);
                $this->assertArrayHasKey($objectHash, $entityUpdates);
                $this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
                $this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectHash][1]->getName());
            }

            if ($callCounter === 2) {
                $entityInsertions = $args->getEntityInsertions();
                $objectHash = spl_object_hash($zoeyPorter);
                $this->assertCount(1, $entityInsertions);
                $this->assertArrayHasKey($objectHash, $entityInsertions);
                $this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

                $entityUpdates = $args->getEntityUpdates();
                $objectHash = spl_object_hash($danielleMurphy);
                $this->assertCount(1, $entityUpdates);
                $this->assertArrayHasKey($objectHash, $entityUpdates);
                $this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
                $this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectHash][1]->getName());
            }

            return true;
        };

        $nestedFlush = function () use ($zoeyPorter) {
            static $callCounter = 0;

            ++$callCounter;

            if ($callCounter === 1) {
                $this->entityManager->persist($zoeyPorter);
                $this->entityManager->flush($zoeyPorter);
            }

            return true;
        };

        $onFlushEnhancedCallback = function (FlushEventArgs $args) use ($assertFlushEventArgsOnFlush, $nestedFlush) {
            $assertFlushEventArgsOnFlush($args);
            $nestedFlush($args);

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) {
            $this->assertSame('Danielle Sanders-Murphy', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgsPostFlush = function (FlushEventArgs $args) use ($zoeyPorter, $danielleMurphy) {
            static $callCounter = 0;

            ++$callCounter;

            if ($callCounter === 1) {
                $entityInsertions = $args->getEntityInsertions();
                $objectHash = spl_object_hash($zoeyPorter);
                $this->assertCount(1, $entityInsertions);
                $this->assertArrayHasKey($objectHash, $entityInsertions);
                $this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

                $entityUpdates = $args->getEntityUpdates();
                $objectHash = spl_object_hash($danielleMurphy);
                $this->assertCount(1, $entityUpdates);
                $this->assertArrayHasKey($objectHash, $entityUpdates);
                $this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
                $this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectHash][1]->getName());
            }

            if ($callCounter === 2) {
                $this->assertEmpty($args->getEntityInsertions());
                $this->assertEmpty($args->getEntityUpdates());
            }

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->exactly(2))
            ->method('onFlushEnhanced')
            ->with($this->callback($onFlushEnhancedCallback));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertUpdateEventArgs));

        $this->eventSubscriberMock
            ->expects($this->exactly(2))
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgsPostFlush));

        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders-Murphy', $danielleMurphy->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }
}
