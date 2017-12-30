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

use DarkWebDesign\DoctrineEnhancedEvents\EventSubscriber;
use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs;

class EventSubscriberTest extends OrmFunctionalTestCase
{
    /** @var \Doctrine\ORM\EntityRepository */
    private $repository;

    /** @var \DarkWebDesign\DoctrineEnhancedEvents\Tests\Mocks\EventSubscriberMock|\PHPUnit_Framework_MockObject_MockObject */
    private $eventSubscriberMock;

    protected function setUp()
    {
        $this->useModelSet('company');
        $this->useFixtureSet('company');

        parent::setUp();

        $this->repository = $this->_em->getRepository('DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson');

        $this->eventSubscriberMock = $this->getMock('DarkWebDesign\DoctrineEnhancedEvents\Tests\Mocks\EventSubscriberMock', array('preUpdateEnhanced', 'postUpdateEnhanced'));

        $eventManager = static::$_sharedConn->getEventManager();
        $eventManager->addEventSubscriber(new EventSubscriber());
        $eventManager->addEventSubscriber($this->eventSubscriberMock);
    }

    public function testUpdateProperty()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $_this = $this;

        $assertEntityProperty = function (UpdateEventArgs $args) use ($_this) {
            $_this->assertSame('Danielle Murphy', $args->getOriginalEntity()->getName());
            $_this->assertSame('Danielle Sanders-Murphy', $args->getEntity()->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($assertEntityProperty));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertEntityProperty));

        $this->_em->persist($danielleMurphy);
        $this->_em->flush($danielleMurphy);

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleSandersMurphy = $this->repository->findOneByName('Danielle Sanders-Murphy');

        $this->assertNull($danielleMurphy);
        $this->assertNotNull($danielleSandersMurphy);
    }

    public function testUpdateRelation()
    {
        $mikeKennedy = $this->repository->findOneByName('Mike Kennedy');

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setSpouse($mikeKennedy);

        $_this = $this;

        $assertEntityRelation = function (UpdateEventArgs $args) use ($_this) {
            $_this->assertSame('Mitchell Sanders', $args->getOriginalEntity()->getSpouse()->getName());
            $_this->assertSame('Mike Kennedy', $args->getEntity()->getSpouse()->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($assertEntityRelation));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertEntityRelation));

        $this->_em->persist($danielleMurphy);
        $this->_em->flush($danielleMurphy);

        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');

        $this->assertSame('Mike Kennedy', $danielleMurphy->getSpouse()->getName());
    }

    public function testModifyEntityPreUpdate()
    {
        $danielleMurphy = $this->repository->findOneByName('Danielle Murphy');
        $danielleMurphy->setName('Danielle Sanders-Murphy');

        $callbackModifyEntity = function (UpdateEventArgs $args) {
            $args->getEntity()->setName('Danielle Sanders');

            return true;
        };

        $_this = $this;

        $assertEntityProperty = function (UpdateEventArgs $args) use ($_this) {
            $_this->assertSame('Danielle Murphy', $args->getOriginalEntity()->getName());
            $_this->assertSame('Danielle Sanders', $args->getEntity()->getName());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($callbackModifyEntity));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertEntityProperty));

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

        $callbackModifyEntity = function (UpdateEventArgs $args) {
            $args->getEntity()->setName('Danielle Sanders');

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($callbackModifyEntity));

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
}
