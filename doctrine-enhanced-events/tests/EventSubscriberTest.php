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

namespace DarkWebDesign\DoctrineEnhancedEvents\Tests;

use DarkWebDesign\DoctrineEnhancedEvents\Events;
use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber;
use DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs;
use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs;
use DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson;

class EventSubscriberTest extends OrmFunctionalTestCase
{
    /** @var \Doctrine\ORM\EntityRepository */
    private $repository;

    /** @var \Doctrine\Common\EventSubscriber|\PHPUnit_Framework_MockObject_MockObject */
    private $eventSubscriberMock;

    protected function setUp()
    {
        $this->useModelSet('company');
        $this->useFixtureSet('company');

        parent::setUp();

        $this->repository = $this->_em->getRepository('DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson');

        $this->eventSubscriberMock = $this->getMock('Doctrine\Common\EventSubscriber', array(
            'onFlushEnhanced',
            'preUpdateEnhanced',
            'postUpdateEnhanced',
            'postFlushEnhanced',
            'getSubscribedEvents'
        ));

        $this->eventSubscriberMock
            ->expects($this->any())
            ->method('getSubscribedEvents')
            ->will($this->returnValue(array(
                Events::onFlushEnhanced,
                Events::preUpdateEnhanced,
                Events::postUpdateEnhanced,
                Events::postFlushEnhanced,
            )));

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

    public function testModifyEntityOnFlush()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $modifyEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
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
            $_this->assertSame('Danielle Sanders', $entityUpdates[$objectHash][1]->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('onFlushEnhanced')
            ->with($this->callback($modifyEntity));

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
        $this->_em->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSandersMurphy);
        $this->assertNotNull($danielleSanders);
    }

    public function testModifyEntityPreUpdate()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $modifyEntity = function (UpdateEventArgs $args) {
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
            $_this->assertSame('Danielle Sanders', $entityUpdates[$objectHash][1]->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($modifyEntity));

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

    public function testModifyEntityPostUpdateIgnored()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $modifyEntity = function (UpdateEventArgs $args) {
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
            $_this->assertSame('Danielle Sanders', $entityUpdates[$objectHash][1]->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($modifyEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($assertFlushEventArgs));

        $this->_em->persist($danielleMurphy);
        $this->_em->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSanders);
        $this->assertNotNull($danielleSandersMurphy);
    }

    public function testModifyEntityPostFlushIgnored()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $modifyEntity = function (FlushEventArgs $args) use ($danielleMurphy) {
            $entityUpdates = $args->getEntityUpdates();
            $objectHash = spl_object_hash($danielleMurphy);
            $entityUpdates[$objectHash][1]->setName('Danielle Sanders');

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postFlushEnhanced')
            ->with($this->callback($modifyEntity));

        $this->_em->persist($danielleMurphy);
        $this->_em->flush($danielleMurphy);

        $this->assertSame('Danielle Sanders', $danielleMurphy->getName());

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSanders = $this->repository->findOneByName('Danielle Sanders');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNull($danielleMurphy);
        $this->assertNull($danielleSanders);
        $this->assertNotNull($danielleSandersMurphy);
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
