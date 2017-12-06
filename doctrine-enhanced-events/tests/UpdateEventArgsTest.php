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

namespace DarkWebDesign\DoctrineEnhanced\Event\Tests;

use DarkWebDesign\DoctrineEnhanced\Event\EventSubscriber;
use DarkWebDesign\DoctrineEnhanced\Event\UpdateEventArgs;

class UpdateEventArgsTest extends OrmFunctionalTestCase
{
    /** @var \Doctrine\ORM\EntityRepository */
    private $repository;

    /** @var \DarkWebDesign\DoctrineEnhanced\Event\Tests\Mocks\EventSubscriberMock|\PHPUnit_Framework_MockObject_MockObject */
    private $eventSubscriberMock;

    protected function setUp()
    {
        $this->useModelSet('company');
        $this->useFixtureSet('company');

        parent::setUp();

        $this->repository = $this->_em->getRepository('DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson');

        $this->eventSubscriberMock = $this->getMock('DarkWebDesign\DoctrineEnhanced\Event\Tests\Mocks\EventSubscriberMock', array('preUpdateEnhanced', 'postUpdateEnhanced'));

        $eventManager = static::$_sharedConn->getEventManager();
        $eventManager->addEventSubscriber(new EventSubscriber($eventManager));
        $eventManager->addEventSubscriber($this->eventSubscriberMock);
    }

    public function testGetters()
    {
        $entity = $this->repository->findOneByName('Danielle Murphy');
        $entity->setName('Danielle Sanders-Murphy');

        $_this = $this;

        $assertGetters = function (UpdateEventArgs $args) use ($_this) {
            $_this->assertSame('Danielle Murphy', $args->getOriginalEntity()->getName());
            $_this->assertSame('Danielle Sanders-Murphy', $args->getEntity()->getName());
            $_this->assertSame($args->getOriginalEntity(), $args->getOriginalObject());

            return true;
        };

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('preUpdateEnhanced')
            ->with($this->callback($assertGetters));

        $this->eventSubscriberMock
            ->expects($this->once())
            ->method('postUpdateEnhanced')
            ->with($this->callback($assertGetters));

        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }
}
