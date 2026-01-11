<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Integration;

use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\Tests\Fixtures\ActorFactory;
use Codryn\PHPTurnTracker\Tests\Fixtures\TimelineProfiles;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for User Story 2: Dynamic Actor Management.
 *
 * Tests adding reinforcements and removing defeated actors mid-combat
 * while maintaining correct turn order.
 */
class DynamicActorManagementTest extends TestCase
{
    /**
     * Scenario 1: Given an active encounter in round 2 with current actor at
     * initiative 15, When I add a new actor with initiative 20, Then the new actor
     * is added to the turn order but doesn't act until next round.
     */
    public function testAddActorWithHighInitiativeDoesNotActUntilNextRound(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
            ActorFactory::create('actor3', 'Rogue', 10),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance to round 2, actor2 is current (initiative 15)
        $encounter->advanceTurn(); // actor2
        $encounter->advanceTurn(); // actor3
        $encounter->advanceTurn(); // Round 2, back to actor1

        $this->assertSame(2, $encounter->getCurrentRound());
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Add new actor with initiative 18 (close to current actor1 at 20)
        $newActor = ActorFactory::create('reinforcement', 'Paladin', 18);
        $encounter->addActor($newActor);

        // Current actor should still be actor1
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Advance through rest of round
        $encounter->advanceTurn(); // actor2
        $encounter->advanceTurn(); // actor3
        $encounter->advanceTurn(); // Round 3, actor1 (highest initiative)

        // Round 3: actor1 goes first (highest initiative)
        $this->assertSame(3, $encounter->getCurrentRound());
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Reinforcement should go next (second highest initiative)
        $encounter->advanceTurn();
        $this->assertSame('reinforcement', $encounter->getCurrentActor()->getId());
    }

    /**
     * Scenario 2: Given an active encounter with current actor at initiative 15,
     * When I add a new actor with initiative 12, Then the new actor is inserted
     * in correct position and will act later this round if initiative 12 hasn't
     * occurred yet.
     */
    public function testAddActorWithLowInitiativeActsLaterThisRoundIfNotPassed(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
            ActorFactory::create('actor3', 'Rogue', 5),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Current is actor1 (20)
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Advance to actor2 (15)
        $encounter->advanceTurn();
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // Add new actor with initiative 10 (between actor2=15 and actor3=5)
        $newActor = ActorFactory::create('reinforcement', 'Cleric', 10);
        $encounter->addActor($newActor);

        // Current should still be actor2
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // Advance turn - should NOT go to reinforcement yet
        $encounter->advanceTurn();

        // Next should be actor3 (5) since reinforcement won't act until next round
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());

        // Complete round
        $encounter->advanceTurn(); // Round 2, actor1

        // Verify round 2 started
        $this->assertSame(2, $encounter->getCurrentRound());

        // Advance through round 2 to verify reinforcement is in turn order
        $encounter->advanceTurn(); // actor2
        $encounter->advanceTurn(); // reinforcement should be here
        $this->assertSame('reinforcement', $encounter->getCurrentActor()->getId());
    }

    /**
     * Scenario 3: Given an active encounter with 5 actors, When I remove an actor
     * who has already acted this round, Then the actor is removed and turn order
     * continues without disruption.
     */
    public function testRemoveActedActorContinuesWithoutDisruption(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
            ActorFactory::create('actor3', 'Rogue', 10),
            ActorFactory::create('actor4', 'Cleric', 5),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // actor1 acts
        $encounter->advanceTurn();

        // Current is actor2, actor1 has acted
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());
        $this->assertCount(1, $encounter->getActedActors());

        // Remove actor1 (who already acted)
        $encounter->removeActor('actor1');

        // Current should still be actor2
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // Only actor2, actor3, actor4 remain
        $this->assertCount(3, $encounter->getActedActors() + $encounter->getUnactedActors());

        // Continue turn progression normally
        $encounter->advanceTurn(); // actor3
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());

        $encounter->advanceTurn(); // actor4
        $this->assertSame('actor4', $encounter->getCurrentActor()->getId());
    }

    /**
     * Scenario 4: Given an active encounter with current actor being "Goblin A",
     * When I remove "Goblin A", Then the turn immediately advances to the next
     * actor in sequence.
     */
    public function testRemoveCurrentActorAdvancesTurnImmediately(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('goblinA', 'Goblin A', 15),
            ActorFactory::create('actor3', 'Rogue', 10),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance to goblinA
        $encounter->advanceTurn();
        $this->assertSame('goblinA', $encounter->getCurrentActor()->getId());

        // Remove current actor (goblinA)
        $encounter->removeActor('goblinA');

        // Turn should immediately advance to actor3
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());

        // Only 2 actors remain
        $this->assertCount(2, array_merge($encounter->getActedActors(), $encounter->getUnactedActors()));
    }

    /**
     * Scenario 5: Given an active encounter, When I remove an actor who hasn't
     * acted yet this round, Then that actor's turn is skipped and they are removed
     * from all future turns.
     */
    public function testRemoveUnactedActorSkipsTurnAndRemovedFromFuture(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
            ActorFactory::create('actor3', 'Rogue', 10),
            ActorFactory::create('actor4', 'Cleric', 5),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // actor1 is current
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Remove actor3 (hasn't acted yet)
        $encounter->removeActor('actor3');

        // Current should still be actor1
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Verify actor3 is not in unacted list
        $unacted = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($a) => $a->getId(), $unacted);
        $this->assertNotContains('actor3', $unactedIds);

        // Advance turns: actor1 -> actor2 -> actor4 (skipping actor3)
        $encounter->advanceTurn(); // actor2
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        $encounter->advanceTurn(); // actor4 (actor3 was removed)
        $this->assertSame('actor4', $encounter->getCurrentActor()->getId());

        // Advance to next round
        $encounter->advanceTurn(); // Round 2, actor1
        $this->assertSame(2, $encounter->getCurrentRound());

        // Verify actor3 doesn't appear in round 2
        $encounter->advanceTurn(); // actor2
        $encounter->advanceTurn(); // actor4

        // After actor4, back to actor1 (actor3 never appears)
        $encounter->advanceTurn();
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
    }
}
