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
use DarkWebDesign\DoctrineEnhancedEvents\Tests\Fixtures\PersonDataLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;

class OrmFunctionalTestCase extends TestCase
{
    private const ENTITY_CLASSNAMES = [
        Person::class,
    ];

    private const FIXTURE_CLASSNAMES = [
        PersonDataLoader::class,
    ];

    /** @var EntityManager */
    protected $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = EntityManager::create(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            Setup::createAnnotationMetadataConfiguration([__DIR__ . '/Entities'], true)
        );

        $classes = [];
        foreach (self::ENTITY_CLASSNAMES as $entityClassname) {
            $classes[] = $this->entityManager->getClassMetadata($entityClassname);
        }

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($classes);

        $loader = new Loader();
        foreach (self::FIXTURE_CLASSNAMES as $fixtureClassname) {
            $loader->addFixture(new $fixtureClassname);
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures(), true);
    }
}
