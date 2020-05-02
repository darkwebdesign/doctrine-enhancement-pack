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

namespace DarkWebDesign\DoctrineEnhancedEvents;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * @author Raymond Schouten
 *
 * @since 2.4
 */
class UpdateEventArgs extends LifecycleEventArgs
{
    /** @var object */
    private $originalObject;

    /**
     * @param object $object
     * @param object $originalObject
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     */
    public function __construct($object, $originalObject, ObjectManager $objectManager)
    {
        parent::__construct($object, $objectManager);

        $this->originalObject = $originalObject;
    }

    /**
     * @return object
     */
    public function getOriginalEntity()
    {
        return $this->originalObject;
    }

    /**
     * @return object
     */
    public function getOriginalObject()
    {
        return $this->originalObject;
    }
}
