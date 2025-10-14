<?php
/**
 * Copyright (c) 2018-present DarkWeb Design.
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

namespace DarkWebDesign\DoctrineEnhancedEvents;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * @author Raymond Schouten
 *
 * @since 2.4
 */
class FlushEventArgs extends OnFlushEventArgs
{
    /** @var array<string, object> */
    private $entityInsertions;

    /** @var array<string, array{object, object}> */
    private $entityUpdates;

    /** @var array<string, object> */
    private $entityDeletions;

    /**
     * @param array<string, object> $entityInsertions
     * @param array<string, array{object, object}> $entityUpdates
     * @param array<string, object> $entityDeletions
     */
    public function __construct(array $entityInsertions, array $entityUpdates, array $entityDeletions, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);

        $this->entityInsertions = $entityInsertions;
        $this->entityUpdates = $entityUpdates;
        $this->entityDeletions = $entityDeletions;
    }

    /**
     * @return array<string, object>
     */
    public function getEntityInsertions(): array
    {
        return $this->entityInsertions;
    }

    /**
     * @return array<string, array{object, object}>
     */
    public function getEntityUpdates(): array
    {
        return $this->entityUpdates;
    }

    /**
     * @return array<string, object>
     */
    public function getEntityDeletions(): array
    {
        return $this->entityDeletions;
    }
}
