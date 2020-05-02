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

use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs;
use DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class UpdateEventArgsTest extends TestCase
{
    /** @var \DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson|\PHPUnit\Framework\MockObject\MockObject */
    private $entity;

    /** @var \DarkWebDesign\DoctrineUnitTesting\Models\Company\CompanyPerson|\PHPUnit\Framework\MockObject\MockObject */
    private $originalEntity;

    /** @var \Doctrine\Common\Persistence\ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    protected function setUp()
    {
        $this->entity = $this->createMock(CompanyPerson::class);
        $this->originalEntity = $this->createMock(CompanyPerson::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
    }

    public function testGetters()
    {
        $updateEventArgs = new UpdateEventArgs($this->entity, $this->originalEntity, $this->objectManager);

        $this->assertSame($this->originalEntity, $updateEventArgs->getOriginalEntity());
        $this->assertSame($this->originalEntity, $updateEventArgs->getOriginalObject());
    }
}
