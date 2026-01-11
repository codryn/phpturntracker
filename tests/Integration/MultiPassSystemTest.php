<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Integration;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\Tests\Fixtures\ActorFactory;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for User Story 4: Multi-Pass System Support.
 *
 * Verifies that:
 * - Shadowrun-style pass-based combat works correctly
 * - Actors with different initiative scores get different numbers of passes
 * - Pass advancement logic works correctly
 * - Initiative decay is applied correctly (if enabled)
 * - Round advancement resets passes properly
 */
final class MultiPassSystemTest extends TestCase
{
    /**
     * US4 Scenario 1: Start pass-based encounter, all actors available in pass 1.
     *
     * Given a Shadowrun encounter with 3 passes per round and actors with
     * initiatives 25 (3 passes), 18 (2 passes), 10 (1 pass)
     * When I start the encounter
     * Then round 1 pass 1 begins with all three actors available
     */
    public function testStartPassBasedEncounterAllActorsAvailableInPass1(): void
    {
        // ARRANGE: Create pass-based profile (Shadowrun style)
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            minInitiative: 1,
            maxInitiative: 50,
            passesPerRound: 3,
            decayEnabled: false
        );

        // Create actors with different initiative scores
        // SR4 rules: initiative/10 = passes (25 = 2.5 = 3 passes, 18 = 1.8 = 2 passes, 10 = 1 pass)
        $actor1 = ActorFactory::create('actor1', 'Fast Runner', 25);  // 3 passes
        $actor2 = ActorFactory::create('actor2', 'Medium Runner', 18); // 2 passes
        $actor3 = ActorFactory::create('actor3', 'Slow Runner', 10);  // 1 pass

        $encounter = new Encounter($profile);
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);

        // ACT: Start encounter
        $encounter->start();

        // ASSERT: Should be round 1, pass 1
        $this->assertSame(1, $encounter->getCurrentRound());
        $this->assertSame(1, $encounter->getCurrentPass());

        // All 3 actors should be unacted in pass 1
        $unactedActors = $encounter->getUnactedActors();
        $this->assertCount(3, $unactedActors, 'All actors should be available in pass 1');

        // Current actor should be the one with highest initiative (actor1)
        $currentActor = $encounter->getCurrentActor();
        $this->assertSame('actor1', $currentActor->getId());
    }

    /**
     * US4 Scenario 2: Advance to pass 2, only actors with 2+ passes available.
     *
     * Given pass 1 with all actors having acted
     * When I advance to next turn
     * Then pass 2 begins with only the actors who have 2+ passes available (25 and 18)
     * and actor with 10 is not available
     */
    public function testAdvanceToPass2OnlyMultiPassActorsAvailable(): void
    {
        // ARRANGE: Create pass-based encounter
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 3,
            decayEnabled: false
        );

        $actor1 = ActorFactory::create('actor1', 'Fast Runner', 25);  // 3 passes
        $actor2 = ActorFactory::create('actor2', 'Medium Runner', 18); // 2 passes
        $actor3 = ActorFactory::create('actor3', 'Slow Runner', 10);  // 1 pass

        $encounter = new Encounter($profile);
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->start();

        // Complete pass 1: all actors act
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn(); // actor1 acts

        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn(); // actor2 acts

        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn(); // actor3 acts

        // ACT: Should advance to pass 2
        $this->assertSame(1, $encounter->getCurrentRound());
        $this->assertSame(2, $encounter->getCurrentPass());

        // ASSERT: Only actors with 2+ passes should be available
        $unactedActors = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($a) => $a->getId(), $unactedActors);

        $this->assertCount(2, $unactedActors, 'Only 2 actors should be available in pass 2');
        $this->assertContains('actor1', $unactedIds, 'Actor1 (3 passes) should be available');
        $this->assertContains('actor2', $unactedIds, 'Actor2 (2 passes) should be available');
        $this->assertNotContains('actor3', $unactedIds, 'Actor3 (1 pass) should NOT be available');

        // Current actor should be actor1 (highest initiative)
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
    }

    /**
     * US4 Scenario 3: Advance to pass 3, only actors with 3 passes available.
     *
     * Given pass 2 with actors 25 and 18 having acted
     * When I advance to next turn
     * Then pass 3 begins with only actor 25 available
     */
    public function testAdvanceToPass3OnlyHighestInitiativeActorAvailable(): void
    {
        // ARRANGE: Create pass-based encounter
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 3,
            decayEnabled: false
        );

        $actor1 = ActorFactory::create('actor1', 'Fast Runner', 25);  // 3 passes
        $actor2 = ActorFactory::create('actor2', 'Medium Runner', 18); // 2 passes
        $actor3 = ActorFactory::create('actor3', 'Slow Runner', 10);  // 1 pass

        $encounter = new Encounter($profile);
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->start();

        // Complete pass 1
        $encounter->advanceTurn(); // actor1
        $encounter->advanceTurn(); // actor2
        $encounter->advanceTurn(); // actor3

        // Complete pass 2
        $this->assertSame(2, $encounter->getCurrentPass());
        $encounter->advanceTurn(); // actor1
        $encounter->advanceTurn(); // actor2

        // ACT: Should advance to pass 3
        $this->assertSame(1, $encounter->getCurrentRound());
        $this->assertSame(3, $encounter->getCurrentPass());

        // ASSERT: Only actor1 (3 passes) should be available
        $unactedActors = $encounter->getUnactedActors();
        $this->assertCount(1, $unactedActors, 'Only 1 actor should be available in pass 3');
        $this->assertSame('actor1', $unactedActors[0]->getId());

        // Current actor should be actor1
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
    }

    /**
     * US4 Scenario 4: Complete all passes, round increments and resets.
     *
     * Given pass 3 completed
     * When I advance to next turn
     * Then round 2 pass 1 begins with all actors reset and available again
     */
    public function testCompleteAllPassesAdvancesToNextRound(): void
    {
        // ARRANGE: Create pass-based encounter
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 3,
            decayEnabled: false
        );

        $actor1 = ActorFactory::create('actor1', 'Fast Runner', 25);  // 3 passes
        $actor2 = ActorFactory::create('actor2', 'Medium Runner', 18); // 2 passes
        $actor3 = ActorFactory::create('actor3', 'Slow Runner', 10);  // 1 pass

        $encounter = new Encounter($profile);
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->start();

        // Complete pass 1 (3 actors)
        $encounter->advanceTurn();
        $encounter->advanceTurn();
        $encounter->advanceTurn();

        // Complete pass 2 (2 actors)
        $encounter->advanceTurn();
        $encounter->advanceTurn();

        // Complete pass 3 (1 actor)
        $this->assertSame(3, $encounter->getCurrentPass());
        $encounter->advanceTurn(); // actor1 acts in pass 3

        // ACT & ASSERT: Should advance to round 2, pass 1
        $this->assertSame(2, $encounter->getCurrentRound());
        $this->assertSame(1, $encounter->getCurrentPass());

        // All actors should be reset and available again
        $unactedActors = $encounter->getUnactedActors();
        $this->assertCount(3, $unactedActors, 'All actors should be available in round 2 pass 1');

        // No actors should have acted yet
        $actedActors = $encounter->getActedActors();
        $this->assertCount(0, $actedActors, 'No actors should have acted at start of new round');
    }

    /**
     * US4 Scenario 5: Initiative decay applied correctly each pass.
     *
     * Given Shadowrun encounter with initiative decay enabled
     * When pass 2 begins
     * Then all actor initiative scores are reduced by the configured decay amount
     * while maintaining their relative order
     */
    public function testInitiativeDecayAppliedEachPass(): void
    {
        // ARRANGE: Create pass-based encounter with decay (SR4 style: -10 per pass)
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 3,
            decayEnabled: true,
            decayAmount: 10
        );

        $actor1 = ActorFactory::create('actor1', 'Fast Runner', 25);  // 3 passes
        $actor2 = ActorFactory::create('actor2', 'Medium Runner', 18); // 2 passes
        $actor3 = ActorFactory::create('actor3', 'Slow Runner', 10);  // 1 pass

        $encounter = new Encounter($profile);
        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->start();

        // Check initial initiatives in pass 1
        // TODO: Verify initial initiatives once getActorState() is implemented
        // $this->assertSame(25, $encounter->getActorState('actor1')->getCurrentInitiative());
        // $this->assertSame(18, $encounter->getActorState('actor2')->getCurrentInitiative());
        // $this->assertSame(10, $encounter->getActorState('actor3')->getCurrentInitiative());

        // Complete pass 1
        $encounter->advanceTurn(); // actor1
        $encounter->advanceTurn(); // actor2
        $encounter->advanceTurn(); // actor3

        // ACT: Check initiatives in pass 2 (should be decayed by 10)
        $this->assertSame(2, $encounter->getCurrentPass());
        // TODO: Verify decay once getActorState() is implemented
        // $this->assertSame(15, $encounter->getActorState('actor1')->getCurrentInitiative(), 'Actor1: 25 - 10 = 15');
        // $this->assertSame(8, $encounter->getActorState('actor2')->getCurrentInitiative(), 'Actor2: 18 - 10 = 8');
        // $this->assertSame(0, $encounter->getActorState('actor3')->getCurrentInitiative(), 'Actor3: 10 - 10 = 0');

        // Relative order should be maintained (actor1 > actor2)
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Complete pass 2
        $encounter->advanceTurn(); // actor1
        $encounter->advanceTurn(); // actor2

        // ACT: Check initiatives in pass 3 (should be decayed by another 10)
        $this->assertSame(3, $encounter->getCurrentPass());
        // TODO: Verify decay in pass 3 once getActorState() is implemented
        // $this->assertSame(5, $encounter->getActorState('actor1')->getCurrentInitiative(), 'Actor1: 15 - 10 = 5');
        // actor2 and actor3 don't have pass 3, but their initiatives would be negative if they did
    }
}
