<?php
/**
 * Copyright (c) 2025-present DarkWeb Design.
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

use DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DarkWebDesign\DoctrineEnhancedEvents\FlushEventArgs
 */
class FlushEventArgsTest extends TestCase
{
    /** @var array */
    private $entityInsertions;

    /** @var array */
    private $entityUpdates;

    /** @var array */
    private $entityDeletions;

    /** @var EntityManager|MockObject */
    private $entityManager;

    protected function setUp(): void
    {
        $this->entityInsertions = [];
        $this->entityUpdates = [];
        $this->entityDeletions = [];
        $this->entityManager = $this->createMock(EntityManager::class);
    }

    public function testGetters(): void
    {
        $updateEventArgs = new FlushEventArgs($this->entityInsertions, $this->entityUpdates, $this->entityDeletions, $this->entityManager);

        $this->assertSame($this->entityInsertions, $updateEventArgs->getEntityInsertions());
        $this->assertSame($this->entityUpdates, $updateEventArgs->getEntityUpdates());
        $this->assertSame($this->entityDeletions, $updateEventArgs->getEntityDeletions());
    }
}
