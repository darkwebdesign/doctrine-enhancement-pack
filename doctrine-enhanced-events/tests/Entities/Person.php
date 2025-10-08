<?php
/**
 * Copyright (c) 2025-present DarkWeb Design
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

namespace DarkWebDesign\DoctrineEnhancedEvents\Tests\Entities;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="person")
 */
class Person
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @Column
     */
    private $name;

    /**
     * @OneToOne(targetEntity="Person")
     * @JoinColumn(name="spouse_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $spouse;

    /**
     * @ManyToMany(targetEntity="Person")
     * @JoinTable(
     *     name="person_friend",
     *     joinColumns={
     *         @JoinColumn(name="person_id", referencedColumnName="id", onDelete="CASCADE")
     *     },
     *     inverseJoinColumns={
     *         @JoinColumn(name="friend_id", referencedColumnName="id", onDelete="CASCADE")
     *     }
     * )
     */
    private $friends;

    public function __construct()
    {
        $this->friends = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getSpouse()
    {
        return $this->spouse;
    }

    public function getFriends()
    {
        return $this->friends;
    }

    public function addFriend(Person $friend)
    {
        if (!$this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->addFriend($this);
        }
    }

    public function setSpouse(Person $spouse)
    {
        if ($spouse !== $this->spouse) {
            $this->spouse = $spouse;
            $this->spouse->setSpouse($this);
        }
    }
}
