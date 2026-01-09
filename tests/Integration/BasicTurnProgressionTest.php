<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Integration;

use Codryn\PhpTurnTracker\Tests\Fixtures\ActorFactory;
use Codryn\PhpTurnTracker\Tests\Fixtures\TimelineProfiles;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for User Story 1: Basic Turn Progression.
 *
 * Tests D&D 5e style combat with individual initiative, round-based turns,
 * and acted/unacted tracking through multiple rounds.
 */
class BasicTurnProgressionTest extends TestCase
{
    /**
     * Scenario 1: Given an encounter has not started, When I start the encounter
     * with 4 actors (initiative scores: 18, 15, 12, 12), Then the tracker activates
     * and the first actor (score 18) is marked as current.
     */
    public function testStartEncounterActivatesAndSetsFirstActor(): void
    {
        // Create actors with specified initiatives
        $actors = [
            ActorFactory::create('actor1', 'Rogue', 18, ['dexterity' => 18]),
            ActorFactory::create('actor2', 'Fighter', 15, ['dexterity' => 14]),
            ActorFactory::create('actor3', 'Wizard', 12, ['dexterity' => 16]),
            ActorFactory::create('actor4', 'Cleric', 12, ['dexterity' => 12]),
        ];

        $profile = TimelineProfiles::dnd5e();

        // Create encounter, add actors, start
        $encounter = new \Codryn\PhpTurnTracker\Encounter($profile);
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Assert encounter is active
        $this->assertTrue($encounter->isActive());

        // Assert current round is 1
        $this->assertSame(1, $encounter->getCurrentRound());

        // Assert first actor (initiative 18) is current
        $current = $encounter->getCurrentActor();
        $this->assertNotNull($current);
        $this->assertSame('actor1', $current->getId());
        $this->assertSame(18, $current->getInitiative());
    }

    /**
     * Scenario 2: Given an active encounter with current actor at initiative 18,
     * When I advance to next turn, Then the actor with initiative 15 becomes current
     * and the previous actor (18) is marked as having acted.
     */
    public function testAdvanceTurnMarksActorAsActedAndMovesToNext(): void
    {
        // Create encounter similar to Scenario 1
        $actors = [
            ActorFactory::create('actor1', 'Rogue', 18),
            ActorFactory::create('actor2', 'Fighter', 15),
            ActorFactory::create('actor3', 'Wizard', 12),
            ActorFactory::create('actor4', 'Cleric', 10),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new \Codryn\PhpTurnTracker\Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Verify first actor
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Advance turn
        $encounter->advanceTurn();

        // Assert current actor is now actor2 (initiative 15)
        $current = $encounter->getCurrentActor();
        $this->assertNotNull($current);
        $this->assertSame('actor2', $current->getId());

        // Assert actor1 is in acted list
        $acted = $encounter->getActedActors();
        $this->assertCount(1, $acted);
        $this->assertSame('actor1', $acted[0]->getId());

        // Assert actors 2, 3, 4 are in unacted list
        $unacted = $encounter->getUnactedActors();
        $this->assertCount(3, $unacted);
    }

    /**
     * Scenario 3: Given all actors have taken their turn in round 1, When I advance
     * to next turn, Then the round increments to 2 and the first actor (initiative 18)
     * becomes current again with all actors reset to "not acted".
     */
    public function testAdvanceTurnIncrementsRoundAndResetsActors(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Rogue', 18, ['dexterity' => 18]),
            ActorFactory::create('actor2', 'Fighter', 15, ['dexterity' => 14]),
            ActorFactory::create('actor3', 'Wizard', 12, ['dexterity' => 16]),
            ActorFactory::create('actor4', 'Cleric', 12, ['dexterity' => 12]),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new \Codryn\PhpTurnTracker\Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance through all 4 actors
        $encounter->advanceTurn(); // actor2
        $encounter->advanceTurn(); // actor3 (tied at 12, dex 16)
        $encounter->advanceTurn(); // actor4 (tied at 12, dex 12)
        $encounter->advanceTurn(); // back to actor1, round 2

        // Assert round is now 2
        $this->assertSame(2, $encounter->getCurrentRound());

        // Assert current actor is actor1 again
        $current = $encounter->getCurrentActor();
        $this->assertSame('actor1', $current->getId());

        // Assert all actors are unacted
        $unacted = $encounter->getUnactedActors();
        $this->assertCount(4, $unacted);

        // Assert no actors in acted list
        $acted = $encounter->getActedActors();
        $this->assertCount(0, $acted);
    }

    /**
     * Scenario 4: Given an active encounter, When I query the current actor,
     * Then I receive the actor whose turn it currently is.
     */
    public function testGetCurrentActorReturnsCorrectActor(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Rogue', 18),
            ActorFactory::create('actor2', 'Fighter', 15),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new \Codryn\PhpTurnTracker\Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Assert current actor is actor1
        $current = $encounter->getCurrentActor();
        $this->assertNotNull($current);
        $this->assertSame('actor1', $current->getId());
        $this->assertSame('Rogue', $current->getName());
        $this->assertSame(18, $current->getInitiative());

        // Advance turn
        $encounter->advanceTurn();
        $current = $encounter->getCurrentActor();

        // Assert current actor is now actor2
        $this->assertSame('actor2', $current->getId());
    }

    /**
     * Scenario 5: Given an active encounter in round 3, When I query the tracker state,
     * Then I receive confirmation that it's round 3 and which actors have/haven't acted
     * this round.
     */
    public function testQueryTrackerStateReturnsRoundAndActorStatus(): void
    {
        $actors = [
            ActorFactory::create('actor1', 'Rogue', 18),
            ActorFactory::create('actor2', 'Fighter', 15),
            ActorFactory::create('actor3', 'Wizard', 12),
            ActorFactory::create('actor4', 'Cleric', 10),
        ];

        $profile = TimelineProfiles::dnd5e();
        $encounter = new \Codryn\PhpTurnTracker\Encounter($profile);

        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance through 8 turns (2 complete rounds)
        for ($i = 0; $i < 8; $i++) {
            $encounter->advanceTurn();
        }

        // Assert round is 3
        $this->assertSame(3, $encounter->getCurrentRound());

        // Current actor is actor1, not acted yet
        $current = $encounter->getCurrentActor();
        $this->assertSame('actor1', $current->getId());

        // All actors unacted
        $unacted = $encounter->getUnactedActors();
        $this->assertCount(4, $unacted);

        // Advance one turn
        $encounter->advanceTurn();

        // Now actor1 has acted
        $acted = $encounter->getActedActors();
        $this->assertCount(1, $acted);
        $this->assertSame('actor1', $acted[0]->getId());

        // 3 actors remaining
        $unacted = $encounter->getUnactedActors();
        $this->assertCount(3, $unacted);
    }
}
