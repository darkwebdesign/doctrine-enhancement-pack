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

declare(strict_types=1);

namespace DarkWebDesign\DoctrineEnhancedEvents\Tests;

use DarkWebDesign\DoctrineEnhancedEvents\Events;
use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber;
use DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs;
use DarkWebDesign\DoctrineEnhancedEvents\Tests\Mocks\EventSubscriberMock;
use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs;
use DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson;

class EventSubscriberTest extends OrmFunctionalTestCase
{
    /** @var \Doctrine\ORM\EntityRepository */
    private $repository;

    /** @var \Doctrine\Common\EventSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $eventSubscriberMock;

    protected function setUp(): void
    {
        $this->useModelSet('company');
        $this->useFixtureSet('company');

        parent::setUp();

        $this->repository = $this->_em->getRepository(CompanyPerson::class);

        $this->eventSubscriberMock = $this->createMock(EventSubscriberMock::class, [
            'onFlushEnhanced',
            'preUpdateEnhanced',
            'postUpdateEnhanced',
            'postFlushEnhanced',
            'getSubscribedEvents'
        ]);

        $this->eventSubscriberMock
            ->expects($this->any())
            ->method('getSubscribedEvents')
            ->will($this->returnValue([
                Events::onFlushEnhanced,
                Events::preUpdateEnhanced,
                Events::postUpdateEnhanced,
                Events::postFlushEnhanced,
            ]));

        $eventManager = static::$_sharedConn->getEventManager();
        $eventManager->addEventSubscriber(new EventSubscriber());
        $eventManager->addEventSubscriber($this->eventSubscriberMock);
    }

    public function testFlushEventArgs()
    {
        $zoeyPorter = new CompanyPerson();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $zoeyPorter, $danielleMurphy, $mikeKennedy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $_this->assertCount(1, $entityInsertions);
            $_this->assertArrayHasKey($objectHash, $entityInsertions);
            $_this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $_this->assertCount(1, $entityUpdates);
            $_this->assertArrayHasKey($objectHash, $entityUpdates);
            $_this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $_this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($mikeKennedy);
            $_this->assertCount(1, $entityDeletions);
            $_this->assertArrayHasKey($objectHash, $entityDeletions);
            $_this->assertSame($mikeKennedy, $entityDeletions[$objectHash]);

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

        $this->_em->persist($zoeyPorter);
        $this->_em->persist($danielleMurphy);
        $this->_em->remove($mikeKennedy);
        $this->_em->flush();

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($mikeKennedy);
    }

    public function testUpdateEventArgs()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $_this = $this;

        $assertUpdateEventArgs = function (UpdateEventArgs $args) use ($_this, $danielleMurphy) {
            $_this->assertSame($danielleMurphy, $args->getEntity());
            $_this->assertSame('Danielle Murphy', $args->getOriginalEntity()->getName());

            $_this->assertSame($args->getEntity(), $args->getObject());
            $_this->assertSame($args->getOriginalEntity(), $args->getOriginalObject());

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

        $this->_em->persist($danielleMurphy);
        $this->_em->flush();

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }

    public function testUpdateEntityInsertionOnFlush()
    {
        $zoeyPorter = new CompanyPerson();
        $zoeyPorter->setName('Zoey Porter');

        $updateEntity = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $entityInsertions[$objectHash]->setName('Zoey Dawson-Porter');

            return true;
        };

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $_this->assertCount(1, $entityInsertions);
            $_this->assertArrayHasKey($objectHash, $entityInsertions);
            $_this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

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

        $this->_em->persist($zoeyPorter);
        $this->_em->flush();

        $this->assertSame('Zoey Dawson-Porter', $zoeyPorter->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $zoeyDawsonPorter = $this->repository->findOneByName('Zoey Dawson-Porter');

        $this->assertNull($zoeyPorter);
        $this->assertNotNull($zoeyDawsonPorter);
    }

    public function testRemoveEntityInsertionOnFlush()
    {
        $zoeyPorter = new CompanyPerson();
        $zoeyPorter->setName('Zoey Porter');

        $removeEntity = function (FlushEventArgs $args) use ($zoeyPorter) {
            $entityManager = $args->getEntityManager();
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $entityManager->remove($entityInsertions[$objectHash]);

            return true;
        };

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this) {
            $entityInsertions = $args->getEntityInsertions();
            $_this->assertEmpty($entityInsertions);

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

        $this->_em->persist($zoeyPorter);
        $this->_em->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');

        $this->assertNull($zoeyPorter);
    }

    public function testUpdateEntityUpdateOnFlush()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $entityUpdates[$objectHash][1]->setName('Danielle Sanders');

            return true;
        };

        $_this = $this;

        $assertUpdateEventArgs = function (UpdateEventArgs $args) use ($_this) {
            $_this->assertSame('Danielle Sanders', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $_this->assertCount(1, $entityUpdates);
            $_this->assertArrayHasKey($objectHash, $entityUpdates);
            $_this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $_this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

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

        $this->_em->persist($danielleMurphy);
        $this->_em->flush();

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
        $this->assertNotNull($danielleSanders);
    }

    public function testRemoveEntityUpdateOnFlush()
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

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $_this->assertEmpty($entityUpdates);

            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($danielleMurphy);
            $_this->assertCount(1, $entityDeletions);
            $_this->assertArrayHasKey($objectHash, $entityDeletions);
            $_this->assertSame($danielleMurphy, $entityDeletions[$objectHash]);

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

        $this->_em->persist($danielleMurphy);
        $this->_em->flush();

        $this->assertSame('Danielle Sanders-Murphy', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
    }

    public function testPersistEntityDeletionOnFlush()
    {
        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $persistEntity = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityManager = $args->getEntityManager();
            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($mikeKennedy);
            $entityManager->persist($entityDeletions[$objectHash]);

            return true;
        };

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $mikeKennedy) {
            $entityDeletions = $args->getEntityDeletions();
            $_this->assertEmpty($entityDeletions);

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

        $this->_em->remove($mikeKennedy);
        $this->_em->flush();

        $this->assertSame('Mike Kennedy', $mikeKennedy->getName());

        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $this->assertNotNull($mikeKennedy);
    }

    public function testUpdateEntityDeletionOnFlush()
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

        $_this = $this;

        $assertUpdateEventArgs = function (UpdateEventArgs $args) use ($_this) {
            $_this->assertSame('Mike Jones', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $mikeKennedy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($mikeKennedy);
            $_this->assertCount(1, $entityUpdates);
            $_this->assertArrayHasKey($objectHash, $entityUpdates);
            $_this->assertSame($mikeKennedy, $entityUpdates[$objectHash][1]);
            $_this->assertSame('Mike Kennedy', $entityUpdates[$objectHash][0]->getName());

            $entityDeletions = $args->getEntityDeletions();
            $_this->assertEmpty($entityDeletions);

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

        $this->_em->remove($mikeKennedy);
        $this->_em->flush();

        $this->assertSame('Mike Jones', $mikeKennedy->getName());

        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');
        $mikeJones = $this->repository->findOneByName('Mike Jones');

        $this->assertNull($mikeKennedy);
        $this->assertNotNull($mikeJones);
    }

    public function testPersistNewEntityOnFlush()
    {
        $zoeyPorter = new CompanyPerson();
        $zoeyPorter->setName('Zoey Porter');

        $rebeccaAnderson = new CompanyPerson();

        $persistNewEntity = function (FlushEventArgs $args) use ($rebeccaAnderson) {
            $entityManager = $args->getEntityManager();
            $rebeccaAnderson->setName('Rebecca Anderson');
            $entityManager->persist($rebeccaAnderson);

            return true;
        };

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $zoeyPorter, $rebeccaAnderson) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $_this->assertCount(2, $entityInsertions);
            $_this->assertArrayHasKey($objectHash, $entityInsertions);
            $_this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);
            $objectHash = spl_object_hash($rebeccaAnderson);
            $_this->assertArrayHasKey($objectHash, $entityInsertions);
            $_this->assertSame($rebeccaAnderson, $entityInsertions[$objectHash]);

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

        $this->_em->persist($zoeyPorter);
        $this->_em->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());
        $this->assertSame('Rebecca Anderson', $rebeccaAnderson->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $rebeccaAnderson = $this->repository->findOneByName('Rebecca Anderson');

        $this->assertNotNull($zoeyPorter);
        $this->assertNotNull($rebeccaAnderson);
    }

    public function testUpdateNewEntityOnFlushIgnored()
    {
        $zoeyPorter = new CompanyPerson();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');

        $persistNewEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityManager = $args->getEntityManager();
            $danielleMurphy->setName('Danielle Sanders-Murphy');
            $entityManager->persist($danielleMurphy);

            return true;
        };

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $zoeyPorter) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $_this->assertCount(1, $entityInsertions);
            $_this->assertArrayHasKey($objectHash, $entityInsertions);
            $_this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            $entityUpdates = $args->getEntityUpdates();
            $_this->assertEmpty($entityUpdates);

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

        $this->_em->persist($zoeyPorter);
        $this->_em->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());
        $this->assertSame('Danielle Sanders-Murphy', $danielleMurphy->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNotNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
    }

    public function testUpdateNewEntityOnFlushManualRecompute()
    {
        $zoeyPorter = new CompanyPerson();
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

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $zoeyPorter, $danielleMurphy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $_this->assertCount(1, $entityInsertions);
            $_this->assertArrayHasKey($objectHash, $entityInsertions);
            $_this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $_this->assertCount(1, $entityUpdates);
            $_this->assertArrayHasKey($objectHash, $entityUpdates);
            $_this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $_this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

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

        $this->_em->persist($zoeyPorter);
        $this->_em->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());
        $this->assertSame('Danielle Sanders-Murphy', $danielleMurphy->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }

    public function testRemoveNewEntityOnFlush()
    {
        $zoeyPorter = new CompanyPerson();
        $zoeyPorter->setName('Zoey Porter');

        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $persistNewEntity = function (FlushEventArgs $args) use ($mikeKennedy) {
            $entityManager = $args->getEntityManager();
            $entityManager->remove($mikeKennedy);

            return true;
        };

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $zoeyPorter, $mikeKennedy) {
            $entityInsertions = $args->getEntityInsertions();
            $objectHash = spl_object_hash($zoeyPorter);
            $_this->assertCount(1, $entityInsertions);
            $_this->assertArrayHasKey($objectHash, $entityInsertions);
            $_this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

            $entityDeletions = $args->getEntityDeletions();
            $objectHash = spl_object_hash($mikeKennedy);
            $_this->assertCount(1, $entityDeletions);
            $_this->assertArrayHasKey($objectHash, $entityDeletions);
            $_this->assertSame($mikeKennedy, $entityDeletions[$objectHash]);

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

        $this->_em->persist($zoeyPorter);
        $this->_em->flush();

        $this->assertSame('Zoey Porter', $zoeyPorter->getName());
        $this->assertSame('Mike Kennedy', $mikeKennedy->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($mikeKennedy);
    }

    public function testUpdateEntityPreUpdate()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (UpdateEventArgs $args) {
            $args->getEntity()->setName('Danielle Sanders');

            return true;
        };

        $_this = $this;

        $assertUpdateEventArgs = function (UpdateEventArgs $args) use ($_this) {
            $_this->assertSame('Danielle Sanders', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $_this->assertCount(1, $entityUpdates);
            $_this->assertArrayHasKey($objectHash, $entityUpdates);
            $_this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $_this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

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

        $this->_em->persist($danielleMurphy);
        $this->_em->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
        $this->assertNotNull($danielleSanders);
    }

    public function testUpdateEntityPostUpdateIgnored()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $updateEntity = function (UpdateEventArgs $args) {
            $args->getEntity()->setName('Danielle Sanders');

            return true;
        };

        $_this = $this;

        $assertFlushEventArgs = function (FlushEventArgs $args) use ($_this, $danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $_this->assertCount(1, $entityUpdates);
            $_this->assertArrayHasKey($objectHash, $entityUpdates);
            $_this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
            $_this->assertSame('Danielle Murphy', $entityUpdates[$objectHash][0]->getName());

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

        $this->_em->persist($danielleMurphy);
        $this->_em->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($danielleSanders);
    }

    public function testUpdateEntityPostFlushIgnored()
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

        $this->_em->persist($danielleMurphy);
        $this->_em->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
        $this->assertNull($danielleSanders);
    }

    public function testNestedFlushes()
    {
        $zoeyPorter = new CompanyPerson();
        $zoeyPorter->setName('Zoey Porter');

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $_this = $this;

        $assertFlushEventArgsOnFlush = function (FlushEventArgs $args) use ($_this, $zoeyPorter, $danielleMurphy) {
            static $callCounter = 0;

            $callCounter++;

            if ($callCounter === 1) {
                $entityUpdates = $args->getEntityUpdates();
                $objectHash = spl_object_hash($danielleMurphy);
                $_this->assertCount(1, $entityUpdates);
                $_this->assertArrayHasKey($objectHash, $entityUpdates);
                $_this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
                $_this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectHash][1]->getName());
            }

            if ($callCounter === 2) {
                $entityInsertions = $args->getEntityInsertions();
                $objectHash = spl_object_hash($zoeyPorter);
                $_this->assertCount(1, $entityInsertions);
                $_this->assertArrayHasKey($objectHash, $entityInsertions);
                $_this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

                $entityUpdates = $args->getEntityUpdates();
                $objectHash = spl_object_hash($danielleMurphy);
                $_this->assertCount(1, $entityUpdates);
                $_this->assertArrayHasKey($objectHash, $entityUpdates);
                $_this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
                $_this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectHash][1]->getName());
            }

            return true;
        };

        $nestedFlush = function () use ($_this, $zoeyPorter) {
            static $callCounter = 0;

            $callCounter++;

            if ($callCounter === 1) {
                $_this->_em->persist($zoeyPorter);
                $_this->_em->flush($zoeyPorter);
            }

            return true;
        };

        $onFlushEnhancedCallback = function (FlushEventArgs $args) use ($assertFlushEventArgsOnFlush, $nestedFlush) {
            $assertFlushEventArgsOnFlush($args);
            $nestedFlush($args);

            return true;
        };

        $assertUpdateEventArgs = function (UpdateEventArgs $args) use ($_this) {
            $_this->assertSame('Danielle Sanders-Murphy', $args->getEntity()->getName());

            return true;
        };

        $assertFlushEventArgsPostFlush = function (FlushEventArgs $args) use ($_this, $zoeyPorter, $danielleMurphy) {
            static $callCounter = 0;

            $callCounter++;

            if ($callCounter === 1) {
                $entityInsertions = $args->getEntityInsertions();
                $objectHash = spl_object_hash($zoeyPorter);
                $_this->assertCount(1, $entityInsertions);
                $_this->assertArrayHasKey($objectHash, $entityInsertions);
                $_this->assertSame($zoeyPorter, $entityInsertions[$objectHash]);

                $entityUpdates = $args->getEntityUpdates();
                $objectHash = spl_object_hash($danielleMurphy);
                $_this->assertCount(1, $entityUpdates);
                $_this->assertArrayHasKey($objectHash, $entityUpdates);
                $_this->assertSame($danielleMurphy, $entityUpdates[$objectHash][1]);
                $_this->assertSame('Danielle Sanders-Murphy', $entityUpdates[$objectHash][1]->getName());
            }

            if ($callCounter === 2) {
                $_this->assertEmpty($args->getEntityInsertions());
                $_this->assertEmpty($args->getEntityUpdates());
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

        $this->_em->persist($danielleMurphy);
        $this->_em->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders-Murphy', $danielleMurphy->getName());

        $zoeyPorter = $this->repository->findOneByName('Zoey Porter');
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNotNull($zoeyPorter);
        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }
}
