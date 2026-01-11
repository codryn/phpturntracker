<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Unit\TurnOrder;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Exceptions\InvalidDesignationException;
use Codryn\PHPTurnTracker\State\ActorState;
use Codryn\PHPTurnTracker\State\EncounterState;
use Codryn\PHPTurnTracker\TurnOrder\Popcorn;
use Codryn\PHPTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Popcorn turn order strategy.
 */
final class PopcornTest extends TestCase
{
    /**
     * Test initial order based on initiative.
     */
    public function testInitialOrderByInitiative(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);
        $actor3 = new Actor('actor3', 'Rogue', 14);

        $actors = [$actor1, $actor2, $actor3];
        $order = $strategy->calculateInitialOrder($actors);

        // Should be sorted by initiative descending: actor2, actor1, actor3
        $this->assertSame(['actor2', 'actor1', 'actor3'], $order);
    }

    /**
     * Test designation of next actor.
     */
    public function testDesignateNextActor(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);

        $strategy->calculateInitialOrder([$actor1, $actor2]);

        $state1 = new ActorState('actor1');
        $state2 = new ActorState('actor2');

        $actorStates = ['actor1' => $state1, 'actor2' => $state2];
        $actors = ['actor1' => $actor1, 'actor2' => $actor2];
        $encounterState = new EncounterState(TurnOrderType::POPCORN);

        // Designate actor1 to act next
        $strategy->designateNext('actor1', $actorStates);

        // getNextActor should return actor1
        $this->assertSame('actor1', $strategy->getNextActor($actors, $actorStates, $encounterState));
    }

    /**
     * Test auto-selection of last unacted actor.
     */
    public function testAutoSelectLastUnactedActor(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);
        $actor3 = new Actor('actor3', 'Rogue', 14);

        $strategy->calculateInitialOrder([$actor1, $actor2, $actor3]);

        $state1 = new ActorState('actor1');
        $state1->markActed();

        $state2 = new ActorState('actor2');
        $state2->markActed();

        $state3 = new ActorState('actor3');

        $actorStates = ['actor1' => $state1, 'actor2' => $state2, 'actor3' => $state3];
        $actors = ['actor1' => $actor1, 'actor2' => $actor2, 'actor3' => $actor3];
        $encounterState = new EncounterState(TurnOrderType::POPCORN);
        $encounterState->setCurrentActorId('actor1'); // Set current actor so auto-select logic works

        // Only actor3 hasn't acted - should be auto-selected
        $this->assertSame('actor3', $strategy->getNextActor($actors, $actorStates, $encounterState));
    }

    /**
     * Test preventing designation of already-acted actors when allowRepeatPopcorn is false.
     */
    public function testPreventDesignationOfActedActor(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);

        $strategy->calculateInitialOrder([$actor1, $actor2]);

        $state1 = new ActorState('actor1');
        $state1->markActed();

        $state2 = new ActorState('actor2');

        $actorStates = ['actor1' => $state1, 'actor2' => $state2];

        // Try to designate actor1 who has already acted
        $this->expectException(InvalidDesignationException::class);
        $strategy->designateNext('actor1', $actorStates);
    }

    /**
     * Test allowing designation of already-acted actors when allowRepeatPopcorn is true.
     */
    public function testAllowDesignationOfActedActorWithRepeatFlag(): void
    {
        $strategy = new Popcorn(true); // Allow repeat designation

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);

        $strategy->calculateInitialOrder([$actor1, $actor2]);

        $state1 = new ActorState('actor1');
        $state1->markActed();

        $state2 = new ActorState('actor2');

        $actorStates = ['actor1' => $state1, 'actor2' => $state2];
        $actors = ['actor1' => $actor1, 'actor2' => $actor2];
        $encounterState = new EncounterState(TurnOrderType::POPCORN);

        // Designate actor1 who has already acted - should succeed
        $strategy->designateNext('actor1', $actorStates);
        $this->assertSame('actor1', $strategy->getNextActor($actors, $actorStates, $encounterState));
    }

    /**
     * Test checking if an actor can be designated.
     */
    public function testCanDesignate(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);

        $strategy->calculateInitialOrder([$actor1, $actor2]);

        $state1 = new ActorState('actor1');
        $state1->markActed();

        $state2 = new ActorState('actor2');

        $actorStates = ['actor1' => $state1, 'actor2' => $state2];

        // actor1 has acted - cannot be designated
        $this->assertFalse($strategy->canDesignate('actor1', $actorStates));

        // actor2 hasn't acted - can be designated
        $this->assertTrue($strategy->canDesignate('actor2', $actorStates));
    }

    /**
     * Test round advancement when all actors have acted.
     */
    public function testShouldAdvanceRoundWhenAllActed(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);

        $strategy->calculateInitialOrder([$actor1, $actor2]);

        $state1 = new ActorState('actor1');
        $state2 = new ActorState('actor2');

        $actorStates = ['actor1' => $state1, 'actor2' => $state2];
        $encounterState = new EncounterState(TurnOrderType::POPCORN);

        // No actors have acted yet - should not advance
        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));

        // One actor acted - should not advance
        $state1->markActed();
        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));

        // All actors acted - should advance
        $state2->markActed();
        $this->assertTrue($strategy->shouldAdvanceRound($actorStates, $encounterState));
    }

    /**
     * Test that popcorn systems don't use passes.
     */
    public function testShouldNotAdvancePass(): void
    {
        $strategy = new Popcorn(false);
        $actorStates = [];

        $this->assertFalse($strategy->shouldAdvancePass($actorStates));
    }

    /**
     * Test actor removal clears designation if removed actor was designated.
     */
    public function testActorRemovalClearsDesignation(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);

        $strategy->calculateInitialOrder([$actor1, $actor2]);

        $state1 = new ActorState('actor1');
        $state2 = new ActorState('actor2');

        $actorStates = ['actor1' => $state1, 'actor2' => $state2];

        // Designate actor1
        $strategy->designateNext('actor1', $actorStates);

        $encounterState = new EncounterState(TurnOrderType::POPCORN);

        // Remove actor1
        $strategy->removeActor('actor1', $encounterState);

        $actors = ['actor2' => $actor2];
        $encounterState->setCurrentActorId('actor2');

        // Designation should be cleared - getNextActor should return null (no other unacted actors)
        $this->assertNull($strategy->getNextActor($actors, ['actor2' => $state2], $encounterState));
    }

    /**
     * Test designation of nonexistent actor throws exception.
     */
    public function testDesignateNonexistentActor(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $strategy->calculateInitialOrder([$actor1]);

        $state1 = new ActorState('actor1');
        $actorStates = ['actor1' => $state1];

        $this->expectException(InvalidDesignationException::class);
        $strategy->designateNext('nonexistent', $actorStates);
    }

    /**
     * Test designating self (edge case).
     */
    public function testDesignateSelf(): void
    {
        $strategy = new Popcorn(false);

        $actor1 = new Actor('actor1', 'Fighter', 18);
        $actor2 = new Actor('actor2', 'Wizard', 22);

        $strategy->calculateInitialOrder([$actor1, $actor2]);

        $state1 = new ActorState('actor1');
        $state2 = new ActorState('actor2');

        $actorStates = ['actor1' => $state1, 'actor2' => $state2];
        $actors = ['actor1' => $actor1, 'actor2' => $actor2];
        $encounterState = new EncounterState(TurnOrderType::POPCORN);

        // actor1 can designate themselves if they haven't acted yet
        $strategy->designateNext('actor1', $actorStates);
        $this->assertSame('actor1', $strategy->getNextActor($actors, $actorStates, $encounterState));
    }
}
