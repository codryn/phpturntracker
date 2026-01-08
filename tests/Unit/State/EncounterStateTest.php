<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Unit\State;

use Codryn\PhpTurnTracker\State\EncounterState;
use PHPUnit\Framework\TestCase;

class EncounterStateTest extends TestCase
{
    public function testDefaultState(): void
    {
        $state = new EncounterState();

        $this->assertFalse($state->isActive());
        $this->assertSame(0, $state->getCurrentRound());
        $this->assertNull($state->getCurrentPass());
        $this->assertNull($state->getCurrentActorId());
    }

    public function testSetAndGetActive(): void
    {
        $state = new EncounterState();

        $state->setActive(true);
        $this->assertTrue($state->isActive());

        $state->setActive(false);
        $this->assertFalse($state->isActive());
    }

    public function testSetAndGetCurrentRound(): void
    {
        $state = new EncounterState();

        $state->setCurrentRound(3);
        $this->assertSame(3, $state->getCurrentRound());
    }

    public function testIncrementRound(): void
    {
        $state = new EncounterState();

        $this->assertSame(0, $state->getCurrentRound());

        $state->incrementRound();
        $this->assertSame(1, $state->getCurrentRound());

        $state->incrementRound();
        $this->assertSame(2, $state->getCurrentRound());
    }

    public function testSetAndGetCurrentPass(): void
    {
        $state = new EncounterState();

        $state->setCurrentPass(2);
        $this->assertSame(2, $state->getCurrentPass());

        $state->setCurrentPass(null);
        $this->assertNull($state->getCurrentPass());
    }

    public function testIncrementPass(): void
    {
        $state = new EncounterState();
        $state->setCurrentPass(1);

        $state->incrementPass();
        $this->assertSame(2, $state->getCurrentPass());

        $state->incrementPass();
        $this->assertSame(3, $state->getCurrentPass());
    }

    public function testIncrementPassDoesNothingWhenNull(): void
    {
        $state = new EncounterState();

        $this->assertNull($state->getCurrentPass());

        $state->incrementPass();
        $this->assertNull($state->getCurrentPass());
    }

    public function testSetAndGetCurrentActorId(): void
    {
        $state = new EncounterState();

        $state->setCurrentActorId('actor1');
        $this->assertSame('actor1', $state->getCurrentActorId());

        $state->setCurrentActorId('actor2');
        $this->assertSame('actor2', $state->getCurrentActorId());

        $state->setCurrentActorId(null);
        $this->assertNull($state->getCurrentActorId());
    }

    public function testCompleteEncounterLifecycle(): void
    {
        $state = new EncounterState();

        // Before start
        $this->assertFalse($state->isActive());
        $this->assertSame(0, $state->getCurrentRound());

        // Start encounter
        $state->setActive(true);
        $state->setCurrentRound(1);
        $state->setCurrentActorId('actor1');

        $this->assertTrue($state->isActive());
        $this->assertSame(1, $state->getCurrentRound());
        $this->assertSame('actor1', $state->getCurrentActorId());

        // Advance through turns
        $state->setCurrentActorId('actor2');
        $this->assertSame('actor2', $state->getCurrentActorId());

        // Advance to next round
        $state->incrementRound();
        $state->setCurrentActorId('actor1');

        $this->assertSame(2, $state->getCurrentRound());
        $this->assertSame('actor1', $state->getCurrentActorId());

        // End encounter
        $state->setActive(false);
        $this->assertFalse($state->isActive());
    }

    public function testPassBasedSystemLifecycle(): void
    {
        $state = new EncounterState();

        // Start pass-based encounter (e.g., Shadowrun)
        $state->setActive(true);
        $state->setCurrentRound(1);
        $state->setCurrentPass(1);
        $state->setCurrentActorId('actor1');

        $this->assertSame(1, $state->getCurrentRound());
        $this->assertSame(1, $state->getCurrentPass());

        // Advance to next pass
        $state->incrementPass();
        $state->setCurrentActorId('actor2');

        $this->assertSame(1, $state->getCurrentRound());
        $this->assertSame(2, $state->getCurrentPass());

        // Advance to next round
        $state->incrementRound();
        $state->setCurrentPass(1);

        $this->assertSame(2, $state->getCurrentRound());
        $this->assertSame(1, $state->getCurrentPass());
    }
}
