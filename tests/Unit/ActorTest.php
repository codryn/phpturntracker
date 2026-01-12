<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Unit;

use Codryn\PHPTurnTracker\Actor;
use PHPUnit\Framework\TestCase;

class ActorTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $actor = new Actor('actor1', 'Gandalf', 18, ['side' => 'heroes']);

        $this->assertSame('actor1', $actor->getId());
        $this->assertSame('Gandalf', $actor->getName());
        $this->assertSame(18, $actor->getInitiative());
    }

    public function testGetAttributeReturnsValue(): void
    {
        $actor = new Actor('actor1', 'Gandalf', 18, ['side' => 'heroes', 'dexterity' => 12]);

        $this->assertSame('heroes', $actor->getAttribute('side'));
        $this->assertSame(12, $actor->getAttribute('dexterity'));
    }

    public function testGetAttributeReturnsNullForMissingKey(): void
    {
        $actor = new Actor('actor1', 'Gandalf', 18);

        $this->assertNull($actor->getAttribute('nonexistent'));
    }

    public function testHasAttributeReturnsTrueForExisting(): void
    {
        $actor = new Actor('actor1', 'Gandalf', 18, ['side' => 'heroes']);

        $this->assertTrue($actor->hasAttribute('side'));
    }

    public function testHasAttributeReturnsFalseForMissing(): void
    {
        $actor = new Actor('actor1', 'Gandalf', 18);

        $this->assertFalse($actor->hasAttribute('nonexistent'));
    }

    public function testGetAttributesReturnsAllAttributes(): void
    {
        $attributes = ['side' => 'heroes', 'dexterity' => 12, 'passes' => 3];
        $actor = new Actor('actor1', 'Gandalf', 18, $attributes);

        $this->assertSame($attributes, $actor->getAttributes());
    }

    public function testConstructorWithNoAttributes(): void
    {
        $actor = new Actor('actor1', 'Gandalf', 18);

        $this->assertSame([], $actor->getAttributes());
        $this->assertFalse($actor->hasAttribute('side'));
    }

    public function testNegativeInitiative(): void
    {
        $actor = new Actor('actor1', 'Slow Zombie', -5);

        $this->assertSame(-5, $actor->getInitiative());
    }

    public function testZeroInitiative(): void
    {
        $actor = new Actor('actor1', 'Statue', 0);

        $this->assertSame(0, $actor->getInitiative());
    }
}
