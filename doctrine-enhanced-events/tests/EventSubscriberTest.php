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
 *
 * @uses \DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs
 * @uses \DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs
 */
class EventSubscriberTest extends OrmFunctionalTestCase
{
    /** @var EntityRepository<Person> */
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

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $mikeKennedy = $this->repository->findOneBy(['name' => 'Mike Kennedy']);

        $this->assertNotNull($danielleMurphy);
        $this->assertNotNull($mikeKennedy);

        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter, $danielleMurphy, $mikeKennedy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectId = spl_object_id($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectId]);

            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(0, $entityUpdates[$objectId]);
            $this->assertInstanceOf(Person::class, $entityUpdates[$objectId][0]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectId][0]->getName());
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectId][1]);

            $entityDeletions = $args->getEntityDeletions();
            $objectId = spl_object_id($mikeKennedy);
            $this->assertCount(1, $entityDeletions);
            $this->assertArrayHasKey($objectId, $entityDeletions);
            $this->assertSame($mikeKennedy, $entityDeletions[$objectId]);

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

        $zoeyPorter = $this->repository->findOneBy(['name' => 'Zoey Porter']);
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);
        $mikeKennedy = $this->repository->findOneBy(['name' => 'Mike Kennedy']);

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($mikeKennedy);
    }

    public function testUpdateEventArgs(): void
    {
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $assertUpdateEventArgs = function (UpdateEventArgs $args) use ($danielleMurphy) {
            $this->assertSame($danielleMurphy, $args->getEntity());
            $this->assertInstanceOf(Person::class, $args->getOriginalEntity());
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

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }

    public function testUpdateEntityInsertionOnFlush(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $updateEntity = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectId = spl_object_id($zoeyPorter);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $this->assertInstanceOf(Person::class, $entityInsertions[$objectId]);
            $entityInsertions[$objectId]->setName('Zoey Dawson-Porter');

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectId = spl_object_id($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectId]);

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

        $zoeyPorter = $this->repository->findOneBy(['name' => 'Zoey Porter']);
        $zoeyDawsonPorter = $this->repository->findOneBy(['name' => 'Zoey Dawson-Porter']);

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
            $objectId = spl_object_id($zoeyPorter);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $entityManager->remove($entityInsertions[$objectId]);

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

        $zoeyPorter = $this->repository->findOneBy(['name' => 'Zoey Porter']);

        $this->assertNull($zoeyPorter);
    }

    public function testUpdateEntityUpdateOnFlush(): void
    {
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $this->assertInstanceOf(Person::class, $entityUpdates[$objectId][1]);
            $entityUpdates[$objectId][1]->setName('Danielle Sanders');

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) {
            $this->assertInstanceOf(Person::class, $args->getEntity());
            $this->assertSame('Danielle Sanders', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(0, $entityUpdates[$objectId]);
            $this->assertInstanceOf(Person::class, $entityUpdates[$objectId][0]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectId][0]->getName());
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectId][1]);

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

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);
        $danielleSanders = $this->repository->findOneBy(['name' => 'Danielle Sanders']);

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
        $this->assertNotNull($danielleSanders);
    }

    public function testRemoveEntityUpdateOnFlush(): void
    {
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $removeEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityManager = $args->getEntityManager();
            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $entityManager->remove($entityUpdates[$objectId][1]);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $this->assertEmpty($entityUpdates);

            $entityDeletions = $args->getEntityDeletions();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertCount(1, $entityDeletions);
            $this->assertArrayHasKey($objectId, $entityDeletions);
            $this->assertSame($danielleMurphy, $entityDeletions[$objectId]);

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

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
    }

    public function testPersistEntityDeletionOnFlush(): void
    {
        $mikeKennedy = $this->repository->findOneBy(['name' => 'Mike Kennedy']);

        $this->assertNotNull($mikeKennedy);

        $persistEntity = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityManager = $args->getEntityManager();
            $entityDeletions = $args->getEntityDeletions();
            $objectId = spl_object_id($mikeKennedy);
            $this->assertArrayHasKey($objectId, $entityDeletions);
            $entityManager->persist($entityDeletions[$objectId]);

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

        $mikeKennedy = $this->repository->findOneBy(['name' => 'Mike Kennedy']);

        $this->assertNotNull($mikeKennedy);
    }

    public function testUpdateEntityDeletionOnFlush(): void
    {
        $mikeKennedy = $this->repository->findOneBy(['name' => 'Mike Kennedy']);

        $this->assertNotNull($mikeKennedy);

        $updateEntity = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityManager = $args->getEntityManager();
            $entityDeletions = $args->getEntityDeletions();
            $objectId = spl_object_id($mikeKennedy);
            $this->assertArrayHasKey($objectId, $entityDeletions);
            $this->assertInstanceOf(Person::class, $entityDeletions[$objectId]);
            $entityDeletions[$objectId]->setName('Mike Jones');
            $entityManager->persist($entityDeletions[$objectId]);

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) {
            $this->assertInstanceOf(Person::class, $args->getEntity());
            $this->assertSame('Mike Jones', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($mikeKennedy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(0, $entityUpdates[$objectId]);
            $this->assertInstanceOf(Person::class, $entityUpdates[$objectId][0]);
            $this->assertSame('Mike Kennedy', $entityUpdates[$objectId][0]->getName());
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $this->assertSame($mikeKennedy, $entityUpdates[$objectId][1]);

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

        $mikeKennedy = $this->repository->findOneBy(['name' => 'Mike Kennedy']);
        $mikeJones = $this->repository->findOneBy(['name' => 'Mike Jones']);

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
            $objectId = spl_object_id($zoeyPorter);
            $this->assertCount(2, $entityInsertions);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectId]);
            $objectId = spl_object_id($rebeccaAnderson);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $this->assertSame($rebeccaAnderson, $entityInsertions[$objectId]);

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

        $zoeyPorter = $this->repository->findOneBy(['name' => 'Zoey Porter']);
        $rebeccaAnderson = $this->repository->findOneBy(['name' => 'Rebecca Anderson']);

        $this->assertNotNull($zoeyPorter);
        $this->assertNotNull($rebeccaAnderson);
    }

    public function testUpdateNewEntityOnFlushIgnored(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $persistNewEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityManager = $args->getEntityManager();
            $danielleMurphy->setName('Danielle Sanders-Murphy');
            $entityManager->persist($danielleMurphy);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectId = spl_object_id($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectId]);

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

        $zoeyPorter = $this->repository->findOneBy(['name' => 'Zoey Porter']);
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);

        $this->assertNotNull($zoeyPorter);
        $this->assertNotNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
    }

    public function testUpdateNewEntityOnFlushManualRecompute(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $persistNewEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityManager = $args->getEntityManager();
            $danielleMurphy->setName('Danielle Sanders-Murphy');
            $entityManager->persist($danielleMurphy);

            $classMetaData = $entityManager->getClassMetadata(Person::class);
            $unitOfWork = $entityManager->getUnitOfWork();
            $unitOfWork->recomputeSingleEntityChangeSet($classMetaData, $danielleMurphy);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter, $danielleMurphy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectId = spl_object_id($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectId]);

            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(0, $entityUpdates[$objectId]);
            $this->assertInstanceOf(Person::class, $entityUpdates[$objectId][0]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectId][0]->getName());
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectId][1]);

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

        $zoeyPorter = $this->repository->findOneBy(['name' => 'Zoey Porter']);
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }

    public function testRemoveNewEntityOnFlush(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $mikeKennedy = $this->repository->findOneBy(['name' => 'Mike Kennedy']);

        $this->assertNotNull($mikeKennedy);

        $persistNewEntity = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityManager = $args->getEntityManager();
            $entityManager->remove($mikeKennedy);

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($zoeyPorter, $mikeKennedy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectId = spl_object_id($zoeyPorter);
            $this->assertCount(1, $entityInsertions);
            $this->assertArrayHasKey($objectId, $entityInsertions);
            $this->assertSame($zoeyPorter, $entityInsertions[$objectId]);

            $entityDeletions = $args->getEntityDeletions();
            $objectId = spl_object_id($mikeKennedy);
            $this->assertCount(1, $entityDeletions);
            $this->assertArrayHasKey($objectId, $entityDeletions);
            $this->assertSame($mikeKennedy, $entityDeletions[$objectId]);

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

        $zoeyPorter = $this->repository->findOneBy(['name' => 'Zoey Porter']);
        $mikeKennedy = $this->repository->findOneBy(['name' => 'Mike Kennedy']);

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($mikeKennedy);
    }

    public function testUpdateEntityPreUpdate(): void
    {
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (UpdateEventArgs $args) {
            $this->assertInstanceOf(Person::class, $args->getEntity());
            $args->getEntity()->setName('Danielle Sanders');

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) {
            $this->assertInstanceOf(Person::class, $args->getEntity());
            $this->assertSame('Danielle Sanders', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(0, $entityUpdates[$objectId]);
            $this->assertInstanceOf(Person::class, $entityUpdates[$objectId][0]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectId][0]->getName());
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectId][1]);

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

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);
        $danielleSanders = $this->repository->findOneBy(['name' => 'Danielle Sanders']);

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
        $this->assertNotNull($danielleSanders);
    }

    public function testUpdateEntityPostUpdateIgnored(): void
    {
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (UpdateEventArgs $args) {
            $this->assertInstanceOf(Person::class, $args->getEntity());
            $args->getEntity()->setName('Danielle Sanders');

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertCount(1, $entityUpdates);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(0, $entityUpdates[$objectId]);
            $this->assertInstanceOf(Person::class, $entityUpdates[$objectId][0]);
            $this->assertSame('Danielle Murphy', $entityUpdates[$objectId][0]->getName());
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $this->assertSame($danielleMurphy, $entityUpdates[$objectId][1]);

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

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);
        $danielleSanders = $this->repository->findOneBy(['name' => 'Danielle Sanders']);

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($danielleSanders);
    }

    public function testUpdateEntityPostFlushIgnored(): void
    {
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectId = spl_object_id($danielleMurphy);
            $this->assertArrayHasKey($objectId, $entityUpdates);
            $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
            $this->assertInstanceOf(Person::class, $entityUpdates[$objectId][1]);
            $entityUpdates[$objectId][1]->setName('Danielle Sanders');

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($updateEntity));

        $this->entityManager->persist($danielleMurphy);
        $this->entityManager->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);
        $danielleSanders = $this->repository->findOneBy(['name' => 'Danielle Sanders']);

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($danielleSanders);
    }

    public function testNestedFlushes(): void
    {
        $zoeyPorter = new Person();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);

        $this->assertNotNull($danielleMurphy);

        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $assertFlushEventArgsOnFlush = function (FlushEventArgs $args) use ($zoeyPorter, $danielleMurphy) {
            static $callCounter = 0;

            ++$callCounter;

            if ($callCounter === 1) {
                $entityUpdates = $args->getEntityUpdates();
                $objectId = spl_object_id($danielleMurphy);
                $this->assertCount(1, $entityUpdates);
                $this->assertArrayHasKey($objectId, $entityUpdates);
                $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
                $this->assertSame($danielleMurphy, $entityUpdates[$objectId][1]);
                $this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectId][1]->getName());
            }

            if ($callCounter === 2) {
                $entityInsertions = $args->getEntityInsertions();
                $objectId = spl_object_id($zoeyPorter);
                $this->assertCount(1, $entityInsertions);
                $this->assertArrayHasKey($objectId, $entityInsertions);
                $this->assertSame($zoeyPorter, $entityInsertions[$objectId]);

                $entityUpdates = $args->getEntityUpdates();
                $objectId = spl_object_id($danielleMurphy);
                $this->assertCount(1, $entityUpdates);
                $this->assertArrayHasKey($objectId, $entityUpdates);
                $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
                $this->assertSame($danielleMurphy, $entityUpdates[$objectId][1]);
                $this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectId][1]->getName());
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
            $nestedFlush();

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) {
            $this->assertInstanceOf(Person::class, $args->getEntity());
            $this->assertSame('Danielle Sanders-Murphy', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgsPostFlush = function (FlushEventArgs $args) use ($zoeyPorter, $danielleMurphy) {
            static $callCounter = 0;

            ++$callCounter;

            if ($callCounter === 1) {
                $entityInsertions = $args->getEntityInsertions();
                $objectId = spl_object_id($zoeyPorter);
                $this->assertCount(1, $entityInsertions);
                $this->assertArrayHasKey($objectId, $entityInsertions);
                $this->assertSame($zoeyPorter, $entityInsertions[$objectId]);

                $entityUpdates = $args->getEntityUpdates();
                $objectId = spl_object_id($danielleMurphy);
                $this->assertCount(1, $entityUpdates);
                $this->assertArrayHasKey($objectId, $entityUpdates);
                $this->assertArrayHasKey(1, $entityUpdates[$objectId]);
                $this->assertSame($danielleMurphy, $entityUpdates[$objectId][1]);
                $this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectId][1]->getName());
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

        $zoeyPorter = $this->repository->findOneBy(['name' => 'Zoey Porter']);
        $danielleMurphy = $this->repository->findOneBy(['name' => 'Danielle Murphy']);
        $danielleSandersMurphy = $this->repository->findOneBy(['name' => 'Danielle Sanders-Murphy']);

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }
}
