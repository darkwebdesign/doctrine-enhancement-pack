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

use DarkWebDesign\DoctrineEnhanced\Event\UpdateEventArgs;

class UpdateEventArgsTest extends \PHPUnit_Framework_TestCase
{
    /** @var \DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson|\PHPUnit_Framework_MockObject_MockObject */
    private $entity;

    /** @var \DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson|\PHPUnit_Framework_MockObject_MockObject */
    private $originalEntity;

    /** @var \Doctrine\Common\Persistence\ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $objectManager;

    protected function setUp()
    {
        $this->entity = $this->getMock('DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson');
        $this->originalEntity = $this->getMock('DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson');
        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
    }

    public function testGetters()
    {
        $updateEventArgs = new UpdateEventArgs($this->entity, $this->originalEntity, $this->objectManager);

        $this->assertSame($this->originalEntity, $updateEventArgs->getOriginalEntity());
        $this->assertSame($this->originalEntity, $updateEventArgs->getOriginalObject());
    }
}
