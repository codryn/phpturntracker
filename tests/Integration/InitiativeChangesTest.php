<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Integration;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\Tests\Fixtures\ActorFactory;
use Codryn\PHPTurnTracker\Tests\Fixtures\TimelineProfiles;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for User Story 3: Initiative Changes and Delays.
 *
 * Verifies that:
 * - Actors can delay their action to lower initiative
 * - Initiative changes preserve acted/unacted status
 * - Delayed actors reset properly in new rounds
 * - Initiative increases skip current round correctly
 */
final class InitiativeChangesTest extends TestCase
{
    /**
     * US3 Scenario 1: Delay actor to lower initiative.
     *
     * Given round 1 with actors at initiatives [20, 18, 15, 12]
     * where actor at 20 has acted and current is 18
     * When actor at 18 delays their action to initiative 10
     * Then actor moves to initiative 10 but remains marked as "not acted"
     * and turn advances to actor at 15
     */
    public function testDelayActorMovesPositionAndRemainsUnacted(): void
    {
        // ARRANGE: Create encounter with 4 actors
        $actor1 = ActorFactory::create('actor1', 'Actor 1', 20);
        $actor2 = ActorFactory::create('actor2', 'Actor 2', 18);
        $actor3 = ActorFactory::create('actor3', 'Actor 3', 15);
        $actor4 = ActorFactory::create('actor4', 'Actor 4', 12);

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->addActor($actor4);
        $encounter->start();

        // Advance past first actor (actor1 at 20)
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn();

        // Current actor is now actor2 at initiative 18
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // ACT: Delay actor2 from 18 to 10
        $encounter->delayActor('actor2', 10);

        // ASSERT: Turn should advance to actor3 (initiative 15)
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());

        // actor2 should NOT be in acted list (still unacted)
        $actedActors = $encounter->getActedActors();
        $actedIds = array_map(fn ($a) => $a->getId(), $actedActors);
        $this->assertNotContains('actor2', $actedIds, 'Delayed actor should remain unacted');
        $this->assertContains('actor1', $actedIds, 'Actor1 should be acted');

        // actor2 should be in unacted list
        $unactedActors = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($a) => $a->getId(), $unactedActors);
        $this->assertContains('actor2', $unactedIds, 'Delayed actor should be in unacted list');

        // Continue through round to verify new order: actor3 (15), actor4 (12), actor2 (10)
        $encounter->advanceTurn(); // actor3 acts
        $this->assertSame('actor4', $encounter->getCurrentActor()->getId());

        $encounter->advanceTurn(); // actor4 acts
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId(), 'Delayed actor should act last');
    }

    /**
     * US3 Scenario 2: Change initiative of actor who already acted.
     *
     * Given round 2 with actor at initiative 18 marked as "has acted"
     * When a magical effect reduces their initiative to 12
     * Then their position in turn order changes but they remain marked as "has acted" for this round
     */
    public function testChangeInitiativePreservesActedStatus(): void
    {
        // ARRANGE: Create encounter and advance to round 2
        $actor1 = ActorFactory::create('actor1', 'Actor 1', 20);
        $actor2 = ActorFactory::create('actor2', 'Actor 2', 18);
        $actor3 = ActorFactory::create('actor3', 'Actor 3', 15);

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->start();

        // Complete round 1
        $encounter->advanceTurn(); // actor1 acts
        $encounter->advanceTurn(); // actor2 acts
        $encounter->advanceTurn(); // actor3 acts

        // Now in round 2, actor1 is current
        $this->assertSame(2, $encounter->getCurrentRound());
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // actor1 acts
        $encounter->advanceTurn();

        // actor2 is current at initiative 18
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // actor2 acts
        $encounter->advanceTurn();

        // ACT: Reduce actor2's initiative from 18 to 12 (magical effect)
        $encounter->changeInitiative('actor2', 12);

        // ASSERT: actor2 should still be marked as acted
        $actedActors = $encounter->getActedActors();
        $actedIds = array_map(fn ($a) => $a->getId(), $actedActors);
        $this->assertContains('actor2', $actedIds, 'Actor2 should remain acted after initiative change');
        $this->assertContains('actor1', $actedIds, 'Actor1 should be acted');

        // actor2 should NOT be in unacted list
        $unactedActors = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($a) => $a->getId(), $unactedActors);
        $this->assertNotContains('actor2', $unactedIds, 'Acted actor should not be in unacted list');

        // Current actor should still be actor3
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());
    }

    /**
     * US3 Scenario 3: Delayed actor status resets in next round.
     *
     * Given an actor delays to initiative 10 and hasn't acted yet
     * When the round advances to round 2
     * Then the delayed actor's "not acted" status resets like all other actors
     */
    public function testDelayedActorStatusResetsInNextRound(): void
    {
        // ARRANGE: Create encounter with 3 actors
        $actor1 = ActorFactory::create('actor1', 'Actor 1', 20);
        $actor2 = ActorFactory::create('actor2', 'Actor 2', 18);
        $actor3 = ActorFactory::create('actor3', 'Actor 3', 15);

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->start();

        // actor1 is current
        $encounter->advanceTurn();

        // actor2 is current - delay to 10
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());
        $encounter->delayActor('actor2', 10);

        // actor3 acts
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn();

        // actor2 acts at delayed initiative
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn();

        // ACT: Advance to round 2
        $this->assertSame(2, $encounter->getCurrentRound());

        // ASSERT: All actors should be unacted at start of round 2
        $unactedActors = $encounter->getUnactedActors();
        $this->assertCount(3, $unactedActors, 'Should have 3 unacted actors (all actors at start of round)');

        $actedActors = $encounter->getActedActors();
        $this->assertCount(0, $actedActors, 'Should have 0 acted actors at start of round');

        // Turn order should be based on original/changed initiatives
        // actor1 (20), actor3 (15), actor2 (10)
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn();
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn();
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());
    }

    /**
     * US3 Scenario 4: Increase initiative past current position skips round.
     *
     * Given round 1 with current actor at initiative 15
     * When I increase an upcoming actor's initiative from 12 to 19
     * Then that actor's turn is skipped this round (since initiative 19 already passed)
     * but they go first in round 2
     */
    public function testIncreaseInitiativePastCurrentSkipsThisRound(): void
    {
        // ARRANGE: Create encounter with 4 actors
        $actor1 = ActorFactory::create('actor1', 'Actor 1', 20);
        $actor2 = ActorFactory::create('actor2', 'Actor 2', 18);
        $actor3 = ActorFactory::create('actor3', 'Actor 3', 15);
        $actor4 = ActorFactory::create('actor4', 'Actor 4', 12);

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->addActor($actor4);
        $encounter->start();

        // Advance to actor3 at initiative 15
        $encounter->advanceTurn(); // actor1 acts
        $encounter->advanceTurn(); // actor2 acts

        // Current is actor3 at initiative 15
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());

        // ACT: Increase actor4's initiative from 12 to 19 (magical buff)
        $encounter->changeInitiative('actor4', 19);

        // ASSERT: actor4 should be marked as acted (skipped this round)
        $actedActors = $encounter->getActedActors();
        $actedIds = array_map(fn ($a) => $a->getId(), $actedActors);
        $this->assertContains('actor4', $actedIds, 'Actor4 should be marked acted when initiative increased past current');

        // Continue to end of round - should only be actor3 left
        $encounter->advanceTurn(); // actor3 acts

        // Should advance to round 2 since actor4 already "acted" (was skipped)
        $this->assertSame(2, $encounter->getCurrentRound());

        // In round 2, turn order is: actor1 (20), actor4 (19), actor2 (18), actor3 (15)
        // actor1 should go first since 20 > 19
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // All actors should be unacted in round 2
        $unactedActors = $encounter->getUnactedActors();
        $this->assertCount(4, $unactedActors, 'Should have 4 unacted actors (all actors at start of round)');
    }
}
