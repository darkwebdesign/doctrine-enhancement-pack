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

namespace DarkWebDesign\DoctrineEnhancedEvents\Tests;

use DarkWebDesign\DoctrineEnhancedEvents\Tests\Entities\Person;
use DarkWebDesign\DoctrineEnhancedEvents\UpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateEventArgsTest extends TestCase
{
    /** @var Person|MockObject */
    private $entity;

    /** @var Person|MockObject */
    private $originalEntity;

    /** @var ObjectManager|MockObject */
    private $objectManager;

    protected function setUp(): void
    {
        $this->entity = $this->createMock(Person::class);
        $this->originalEntity = $this->createMock(Person::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
    }

    public function testGetters(): void
    {
        $updateEventArgs = new UpdateEventArgs($this->entity, $this->originalEntity, $this->objectManager);

        $this->assertSame($this->originalEntity, $updateEventArgs->getOriginalEntity());
        $this->assertSame($this->originalEntity, $updateEventArgs->getOriginalObject());
    }
}
