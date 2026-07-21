<?php
/**
 * Copyright (c) 2026-present DarkWeb Design.
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

use DarkWebDesign\DoctrineEnhancedEvents\PostRemoveEventArgs;
use DarkWebDesign\DoctrineEnhancedEvents\Tests\Entities\Person;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMInvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DarkWebDesign\DoctrineEnhancedEvents\PostRemoveEventArgs
 */
class PostRemoveEventArgsTest extends TestCase
{
    /** @var Person|MockObject */
    private $entity;

    /** @var array<string, mixed> */
    private $identifierValues;

    /** @var EntityManager|MockObject */
    private $entityManager;

    protected function setUp(): void
    {
        $this->entity = $this->createMock(Person::class);
        $this->identifierValues = ['id' => 1];
        $this->entityManager = $this->createMock(EntityManager::class);
    }

    public function testGetters(): void
    {
        $updateEventArgs = new PostRemoveEventArgs($this->entity, $this->identifierValues, $this->entityManager);

        $this->assertSame($this->identifierValues, $updateEventArgs->getDeletedIdentifierValues());
        $this->assertSame($this->identifierValues['id'], $updateEventArgs->getDeletedSingleIdentifierValue());
    }

    public function testCompositeIdentifierException(): void
    {
        $this->expectException(ORMInvalidArgumentException::class);

        $updateEventArgs = new PostRemoveEventArgs($this->entity, ['id1' => 1, 'id2' => 2], $this->entityManager);

        $updateEventArgs->getDeletedSingleIdentifierValue();
    }
}
