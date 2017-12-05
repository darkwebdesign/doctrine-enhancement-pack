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

namespace DarkWebDesign\DoctrineEnhanced\Event\Tests\Mocks;

use DarkWebDesign\DoctrineEnhanced\Event\Events;
use DarkWebDesign\DoctrineEnhanced\Event\UpdateEventArgs;
use Doctrine\Common\EventSubscriber;

class EventSubscriberMock implements EventSubscriber
{
    /**
     * @param \DarkWebDesign\DoctrineEnhanced\Event\UpdateEventArgs $args
     */
    public function preUpdateEnhanced(UpdateEventArgs $args)
    {
    }

    /**
     * @param \DarkWebDesign\DoctrineEnhanced\Event\UpdateEventArgs $args
     */
    public function postUpdateEnhanced(UpdateEventArgs $args)
    {
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::preUpdateEnhanced,
            Events::postUpdateEnhanced,
        );
    }
}
