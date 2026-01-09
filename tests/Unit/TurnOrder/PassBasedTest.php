<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Unit\TurnOrder;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\State\ActorState;
use Codryn\PhpTurnTracker\State\EncounterState;
use Codryn\PhpTurnTracker\TurnOrder\PassBased;
use Codryn\PhpTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PassBased turn order strategy.
 *
 * Tests pass calculation, eligibility checking, decay application, and round advancement.
 */
final class PassBasedTest extends TestCase
{
    /**
     * Test that calculatePasses correctly calculates passes from initiative.
     *
     * Shadowrun 4e/5e rules: initiative / 10, rounded up, minimum 1
     */
    public function testCalculatePassesFromInitiative(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: false);

        // Test standard calculations
        $this->assertSame(1, $strategy->calculatePasses(5));
        $this->assertSame(1, $strategy->calculatePasses(9));
        $this->assertSame(1, $strategy->calculatePasses(10));
        $this->assertSame(2, $strategy->calculatePasses(11));
        $this->assertSame(2, $strategy->calculatePasses(20));
        $this->assertSame(3, $strategy->calculatePasses(25));
        $this->assertSame(3, $strategy->calculatePasses(30));
        $this->assertSame(4, $strategy->calculatePasses(40));

        // Test edge cases
        $this->assertSame(1, $strategy->calculatePasses(1));
        $this->assertSame(1, $strategy->calculatePasses(0));
        $this->assertSame(1, $strategy->calculatePasses(-5)); // Negative should return minimum 1
    }

    /**
     * Test that pass eligibility is checked correctly.
     *
     * An actor can act in pass N if they have N or more total passes and haven't acted yet.
     */
    public function testPassEligibilityChecking(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: false);

        $actor1 = new Actor('actor1', 'Actor 1', 25); // 3 passes
        $actor2 = new Actor('actor2', 'Actor 2', 18); // 2 passes
        $actor3 = new Actor('actor3', 'Actor 3', 7);  // 1 pass

        $actors = [$actor1, $actor2, $actor3];

        $state1 = new ActorState('actor1');
        $state1->setPassesRemaining(3);
        $state2 = new ActorState('actor2');
        $state2->setPassesRemaining(2);
        $state3 = new ActorState('actor3');
        $state3->setPassesRemaining(1);

        $actorStates = [
            'actor1' => $state1,
            'actor2' => $state2,
            'actor3' => $state3,
        ];

        $encounterState = new EncounterState(TurnOrderType::PASS);
        $encounterState->setCurrentPass(1);

        // Pass 1: All actors eligible
        $strategy->calculateInitialOrder($actors);
        $this->assertSame('actor1', $strategy->getNextActor($actors, $actorStates, $encounterState));

        // Mark actor1 as acted, should get actor2
        $state1->markActed();
        $this->assertSame('actor2', $strategy->getNextActor($actors, $actorStates, $encounterState));

        // Mark actor2 as acted, should get actor3
        $state2->markActed();
        $this->assertSame('actor3', $strategy->getNextActor($actors, $actorStates, $encounterState));

        // All acted in pass 1
        $state3->markActed();

        // Move to pass 2, reset acted status for eligible actors
        $encounterState->setCurrentPass(2);
        $state1->resetActed();
        $state2->resetActed();

        // Pass 2: Only actor1 and actor2 eligible (actor3 has only 1 pass)
        $this->assertSame('actor1', $strategy->getNextActor($actors, $actorStates, $encounterState));

        $state1->markActed();
        $this->assertSame('actor2', $strategy->getNextActor($actors, $actorStates, $encounterState));

        // Move to pass 3
        $encounterState->setCurrentPass(3);
        $state1->resetActed();

        // Pass 3: Only actor1 eligible (only one with 3 passes)
        $this->assertSame('actor1', $strategy->getNextActor($actors, $actorStates, $encounterState));
    }

    /**
     * Test that initiative decay is applied correctly each pass.
     */
    public function testInitiativeDecayApplication(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: true, decayAmount: 10);

        $state = new ActorState('actor1');
        $state->setCurrentInitiative(25);
        $state->setPassesRemaining(3);

        // Initial initiative
        $this->assertSame(25, $state->getCurrentInitiative());

        // Apply decay once
        $strategy->applyDecay($state);
        $this->assertSame(15, $state->getCurrentInitiative());

        // Apply decay again
        $strategy->applyDecay($state);
        $this->assertSame(5, $state->getCurrentInitiative());

        // Apply decay third time
        $strategy->applyDecay($state);
        $this->assertSame(-5, $state->getCurrentInitiative());
    }

    /**
     * Test that decay is not applied when disabled.
     */
    public function testDecayDisabled(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: false);

        $state = new ActorState('actor1');
        $state->setCurrentInitiative(25);
        $state->setPassesRemaining(3);

        $this->assertSame(25, $state->getCurrentInitiative());

        // Decay should not be applied
        $strategy->applyDecay($state);
        $this->assertSame(25, $state->getCurrentInitiative());
    }

    /**
     * Test that shouldAdvanceRound correctly identifies round completion.
     */
    public function testShouldAdvanceRound(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: false);

        $actor1 = new Actor('actor1', 'Actor 1', 25);
        $actor2 = new Actor('actor2', 'Actor 2', 18);

        $actors = [$actor1, $actor2];
        $strategy->calculateInitialOrder($actors);

        $state1 = new ActorState('actor1');
        $state1->setPassesRemaining(3);
        $state2 = new ActorState('actor2');
        $state2->setPassesRemaining(2);

        $actorStates = [
            'actor1' => $state1,
            'actor2' => $state2,
        ];

        $encounterState = new EncounterState(TurnOrderType::PASS);
        $encounterState->setCurrentPass(1);

        // Pass 1: Not ready to advance round
        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));

        // Pass 2: Still not ready
        $encounterState->setCurrentPass(2);
        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));

        // Pass 3: Ready to advance after all actors acted
        $encounterState->setCurrentPass(3);
        $state1->markActed(); // actor1 acted in pass 3
        $this->assertTrue($strategy->shouldAdvanceRound($actorStates, $encounterState));
    }

    /**
     * Test turn order is correctly sorted by initiative descending.
     */
    public function testTurnOrderSortedByInitiative(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: false);

        $actor1 = new Actor('actor1', 'Actor 1', 15);
        $actor2 = new Actor('actor2', 'Actor 2', 25);
        $actor3 = new Actor('actor3', 'Actor 3', 10);

        $actors = [$actor1, $actor2, $actor3];
        $turnOrder = $strategy->calculateInitialOrder($actors);

        $this->assertSame(['actor2', 'actor1', 'actor3'], $turnOrder);
    }

    /**
     * Test adding an actor recalculates the turn order.
     */
    public function testAddActorRecalculatesTurnOrder(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: false);

        $actor1 = new Actor('actor1', 'Actor 1', 15);
        $actor2 = new Actor('actor2', 'Actor 2', 25);

        $actors = [$actor1, $actor2];
        $strategy->calculateInitialOrder($actors);

        // Add new actor with middle initiative
        $actor3 = new Actor('actor3', 'Actor 3', 20);
        $actors[] = $actor3;

        $encounterState = new EncounterState(TurnOrderType::PASS);
        $strategy->addActor($actor3, $actors, $encounterState);

        // Turn order should now be: actor2 (25), actor3 (20), actor1 (15)
        $turnOrder = $strategy->calculateInitialOrder($actors);
        $this->assertSame(['actor2', 'actor3', 'actor1'], $turnOrder);
    }

    /**
     * Test decay edge case: initiative decaying to negative values.
     */
    public function testDecayToNegativeInitiative(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: true, decayAmount: 15);

        $state = new ActorState('actor1');
        $state->setCurrentInitiative(10);
        $state->setPassesRemaining(2);

        // Initial: 10
        $this->assertSame(10, $state->getCurrentInitiative());

        // After first decay: 10 - 15 = -5
        $strategy->applyDecay($state);
        $this->assertSame(-5, $state->getCurrentInitiative());

        // After second decay: -5 - 15 = -20
        $strategy->applyDecay($state);
        $this->assertSame(-20, $state->getCurrentInitiative());
    }

    /**
     * Test decay edge case: zero decay amount.
     */
    public function testZeroDecayAmount(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: true, decayAmount: 0);

        $state = new ActorState('actor1');
        $state->setCurrentInitiative(25);

        $strategy->applyDecay($state);
        $this->assertSame(25, $state->getCurrentInitiative());
    }

    /**
     * Test decay edge case: extremely high decay amount.
     */
    public function testExtremeDecayAmount(): void
    {
        $strategy = new PassBased(passesPerRound: 3, decayEnabled: true, decayAmount: 1000);

        $state = new ActorState('actor1');
        $state->setCurrentInitiative(25);

        $strategy->applyDecay($state);
        $this->assertSame(-975, $state->getCurrentInitiative());
    }
}
