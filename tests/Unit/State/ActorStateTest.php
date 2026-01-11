<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Unit\State;

use Codryn\PHPTurnTracker\State\ActorState;
use PHPUnit\Framework\TestCase;

class ActorStateTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $state = new ActorState('actor1', false, 3, 25);

        $this->assertSame('actor1', $state->getActorId());
        $this->assertFalse($state->hasActed());
        $this->assertSame(3, $state->getPassesRemaining());
        $this->assertSame(25, $state->getCurrentInitiative());
    }

    public function testDefaultValues(): void
    {
        $state = new ActorState('actor1');

        $this->assertFalse($state->hasActed());
        $this->assertSame(0, $state->getPassesRemaining());
        $this->assertSame(0, $state->getCurrentInitiative());
    }

    public function testMarkActed(): void
    {
        $state = new ActorState('actor1');

        $this->assertFalse($state->hasActed());

        $state->markActed();
        $this->assertTrue($state->hasActed());
    }

    public function testResetActed(): void
    {
        $state = new ActorState('actor1', true);

        $this->assertTrue($state->hasActed());

        $state->resetActed();
        $this->assertFalse($state->hasActed());
    }

    public function testSetHasActed(): void
    {
        $state = new ActorState('actor1');

        $state->setHasActed(true);
        $this->assertTrue($state->hasActed());

        $state->setHasActed(false);
        $this->assertFalse($state->hasActed());
    }

    public function testSetPassesRemaining(): void
    {
        $state = new ActorState('actor1');

        $state->setPassesRemaining(5);
        $this->assertSame(5, $state->getPassesRemaining());
    }

    public function testDecrementPassesRemaining(): void
    {
        $state = new ActorState('actor1', false, 3);

        $state->decrementPassesRemaining();
        $this->assertSame(2, $state->getPassesRemaining());

        $state->decrementPassesRemaining();
        $this->assertSame(1, $state->getPassesRemaining());

        $state->decrementPassesRemaining();
        $this->assertSame(0, $state->getPassesRemaining());
    }

    public function testDecrementPassesRemainingDoesNotGoBelowZero(): void
    {
        $state = new ActorState('actor1', false, 0);

        $state->decrementPassesRemaining();
        $this->assertSame(0, $state->getPassesRemaining());

        $state->decrementPassesRemaining();
        $this->assertSame(0, $state->getPassesRemaining());
    }

    public function testSetCurrentInitiative(): void
    {
        $state = new ActorState('actor1');

        $state->setCurrentInitiative(20);
        $this->assertSame(20, $state->getCurrentInitiative());
    }

    public function testApplyDecay(): void
    {
        $state = new ActorState('actor1', false, 0, 30);

        $state->applyDecay(10);
        $this->assertSame(20, $state->getCurrentInitiative());

        $state->applyDecay(5);
        $this->assertSame(15, $state->getCurrentInitiative());
    }

    public function testApplyDecayCanGoNegative(): void
    {
        $state = new ActorState('actor1', false, 0, 5);

        $state->applyDecay(10);
        $this->assertSame(-5, $state->getCurrentInitiative());
    }

    public function testCompleteStateLifecycle(): void
    {
        // Simulate Shadowrun-style pass system
        $state = new ActorState('actor1', false, 3, 25);

        // First pass: actor acts
        $this->assertFalse($state->hasActed());
        $this->assertSame(3, $state->getPassesRemaining());
        $this->assertSame(25, $state->getCurrentInitiative());

        $state->markActed();
        $state->decrementPassesRemaining();
        $state->applyDecay(10);

        $this->assertTrue($state->hasActed());
        $this->assertSame(2, $state->getPassesRemaining());
        $this->assertSame(15, $state->getCurrentInitiative());

        // Reset for next pass
        $state->resetActed();
        $this->assertFalse($state->hasActed());
        $this->assertSame(2, $state->getPassesRemaining());
    }
}
