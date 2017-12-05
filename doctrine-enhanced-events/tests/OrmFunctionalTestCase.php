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

use DarkWebDesign\DoctrineUnitTesting\OrmFunctionalTestCase as BaseOrmFunctionalTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

class OrmFunctionalTestCase extends BaseOrmFunctionalTestCase
{
    /** @var array */
    protected $usedFixtureSets = array();

    /** @var array */
    protected $fixtureSets = array(
        'company' => array(
            'DarkWebDesign\DoctrineEnhanced\Event\Tests\Fixtures\CompanyPersonLoader',
        ),
    );

    /**
     * @param string $setName
     */
    protected function useFixtureSet($setName)
    {
        $this->usedFixtureSets[$setName] = true;
    }

    protected function setUp()
    {
        parent::setUp();

        $loader = new Loader();

        foreach ($this->usedFixtureSets as $setName => $bool) {
            foreach ($this->fixtureSets[$setName] as $className) {
                $loader->addFixture(new $className);
            }
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->_em, $purger);
        $executor->execute($loader->getFixtures(), true);
    }
}
