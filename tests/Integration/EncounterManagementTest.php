<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Integration;

use Codryn\PhpTurnTracker\Encounter;
use Codryn\PhpTurnTracker\Exceptions\NoActorsException;
use Codryn\PhpTurnTracker\Tests\Fixtures\ActorFactory;
use Codryn\PhpTurnTracker\Tests\Fixtures\TimelineProfiles;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Encounter Management operations.
 *
 * Tests reset, restart, and rewindTurn functionality to ensure proper
 * state management and turn control.
 */
class EncounterManagementTest extends TestCase
{
    /**
     * Test restart() restarts encounter from round 1 with same actors.
     */
    public function testRestartResetsToRoundOneWithSameActors(): void
    {
        // ARRANGE: Create encounter and advance through some rounds
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
            ActorFactory::create('actor3', 'Rogue', 10),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance to round 2
        $encounter->advanceTurn(); // actor1 acts
        $encounter->advanceTurn(); // actor2 acts
        $encounter->advanceTurn(); // actor3 acts, advances to round 2

        $this->assertSame(2, $encounter->getCurrentRound());

        // Act through round 2 partially
        $encounter->advanceTurn(); // actor1 acts
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // ACT: Restart the encounter
        $encounter->restart();

        // ASSERT: Encounter should be back at round 1, first actor
        $this->assertTrue($encounter->isActive());
        $this->assertSame(1, $encounter->getCurrentRound());
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // All actors should be unacted
        $unacted = $encounter->getUnactedActors();
        $this->assertCount(3, $unacted);

        $acted = $encounter->getActedActors();
        $this->assertCount(0, $acted);
    }

    /**
     * Test restart() preserves actor states and initiatives.
     */
    public function testRestartPreservesActorInitiatives(): void
    {
        // ARRANGE: Create encounter with actors
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 10),
            ActorFactory::create('actor3', 'Rogue', 5),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Change initiative during play
        $encounter->advanceTurn();
        $encounter->changeInitiative('actor2', 18); // Changed from 10 to 18

        // Advance to round 2
        $encounter->advanceTurn();
        $encounter->advanceTurn();
        $this->assertSame(2, $encounter->getCurrentRound());

        // ACT: Restart
        $encounter->restart();

        // ASSERT: Initiative change should be preserved (changeInitiative is permanent)
        // Order should be actor1 (20), actor2 (18), actor3 (5)
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn();
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn();
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());
    }

    /**
     * Test restart() throws exception when no actors present.
     */
    public function testRestartThrowsExceptionWithNoActors(): void
    {
        $encounter = new Encounter(TimelineProfiles::dnd5e());

        $this->expectException(NoActorsException::class);
        $encounter->restart();
    }

    /**
     * Test restart() clears temporary delays from previous rounds.
     */
    public function testRestartClearsTemporaryDelays(): void
    {
        // ARRANGE: Create encounter with actors
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 18),
            ActorFactory::create('actor3', 'Rogue', 10),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance and delay actor2
        $encounter->advanceTurn(); // actor1 acts
        $encounter->delayActor('actor2', 5); // actor2 delays to 5

        // Complete the round
        $encounter->advanceTurn(); // actor3 acts
        $encounter->advanceTurn(); // actor2 acts at delayed initiative

        // ACT: Restart
        $encounter->restart();

        // ASSERT: Actor2 should be back at original initiative 18
        $state = $encounter->getActorState('actor2');
        $this->assertSame(18, $state->getCurrentInitiative());

        // Turn order should reflect original initiatives
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
        $encounter->advanceTurn();
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());
    }

    /**
     * Test reset() removes all actors and clears state.
     */
    public function testResetRemovesAllActorsAndClearsState(): void
    {
        // ARRANGE: Create active encounter with actors
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
            ActorFactory::create('actor3', 'Rogue', 10),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance through some turns
        $encounter->advanceTurn();
        $encounter->advanceTurn();

        $this->assertTrue($encounter->isActive());
        $this->assertSame(1, $encounter->getCurrentRound());

        // ACT: Reset the encounter
        $encounter->reset();

        // ASSERT: Encounter should be inactive
        $this->assertFalse($encounter->isActive());

        // Current round should be 0 (initial state)
        $this->assertSame(0, $encounter->getCurrentRound());

        // No current actor
        $this->assertNull($encounter->getCurrentActor());

        // No actors should remain
        $this->assertCount(0, $encounter->getActedActors());
        $this->assertCount(0, $encounter->getUnactedActors());

        // Actor states should be cleared
        $this->assertNull($encounter->getActorState('actor1'));
        $this->assertNull($encounter->getActorState('actor2'));
        $this->assertNull($encounter->getActorState('actor3'));
    }

    /**
     * Test reset() allows adding new actors and starting fresh.
     */
    public function testResetAllowsNewEncounterToStart(): void
    {
        // ARRANGE: Create and run an encounter
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();
        $encounter->advanceTurn();

        // ACT: Reset and add different actors
        $encounter->reset();

        $newActors = [
            ActorFactory::create('goblin1', 'Goblin', 12),
            ActorFactory::create('goblin2', 'Goblin', 10),
            ActorFactory::create('orc', 'Orc', 8),
        ];

        foreach ($newActors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // ASSERT: New encounter should work correctly
        $this->assertTrue($encounter->isActive());
        $this->assertSame(1, $encounter->getCurrentRound());
        $this->assertSame('goblin1', $encounter->getCurrentActor()->getId());

        // Should have 3 unacted actors
        $this->assertCount(3, $encounter->getUnactedActors());
    }

    /**
     * Test rewindTurn() restores previous actor and unmarks as acted.
     */
    public function testRewindTurnRestoresPreviousActor(): void
    {
        // ARRANGE: Create encounter and advance a turn
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
            ActorFactory::create('actor3', 'Rogue', 10),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Advance to actor2
        $encounter->advanceTurn();
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // Verify actor1 is marked as acted
        $acted = $encounter->getActedActors();
        $actedIds = array_map(fn ($a) => $a->getId(), $acted);
        $this->assertContains('actor1', $actedIds);

        // ACT: Rewind turn
        $encounter->rewindTurn();

        // ASSERT: Should be back to actor1
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Actor1 should no longer be marked as acted
        $acted = $encounter->getActedActors();
        $this->assertCount(0, $acted);

        $unacted = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($a) => $a->getId(), $unacted);
        $this->assertContains('actor1', $unactedIds);
    }

    /**
     * Test rewindTurn() undoes delay and restores original initiative.
     */
    public function testRewindTurnUndoesDelayAndRestoresInitiative(): void
    {
        // ARRANGE: Create encounter
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 18),
            ActorFactory::create('actor3', 'Rogue', 10),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance to actor2
        $encounter->advanceTurn();
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // Delay actor2 from 18 to 5
        $encounter->delayActor('actor2', 5);

        // Current should now be actor3
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());

        // Actor2 should have delayed initiative
        $state = $encounter->getActorState('actor2');
        $this->assertSame(5, $state->getCurrentInitiative());

        // ACT: Rewind turn (undo the delay)
        $encounter->rewindTurn();

        // ASSERT: Should be back to actor2
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // Actor2's initiative should be restored to original 18
        $state = $encounter->getActorState('actor2');
        $this->assertSame(18, $state->getCurrentInitiative());

        // Actor2 should not be marked as acted
        $this->assertFalse($state->hasActed());
    }

    /**
     * Test rewindTurn() throws exception when encounter not active.
     */
    public function testRewindTurnThrowsExceptionWhenNotActive(): void
    {
        $encounter = new Encounter(TimelineProfiles::dnd5e());
        $actor = ActorFactory::create('actor1', 'Fighter', 20);
        $encounter->addActor($actor);

        $this->expectException(\Codryn\PhpTurnTracker\Exceptions\EncounterNotActiveException::class);
        $encounter->rewindTurn();
    }

    /**
     * Test rewindTurn() throws exception when no previous actor.
     */
    public function testRewindTurnThrowsExceptionWhenNoPreviousActor(): void
    {
        // ARRANGE: Create encounter at start (no previous actor yet)
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // At start, there's no previous actor
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No previous actor to rewind to');
        $encounter->rewindTurn();
    }

    /**
     * Test rewindTurn() can be called multiple times in succession.
     */
    public function testRewindTurnCannotBeCalledMultipleTimes(): void
    {
        // ARRANGE: Create encounter and advance several turns
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
            ActorFactory::create('actor3', 'Rogue', 10),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Advance: actor1 -> actor2 -> actor3
        $encounter->advanceTurn();
        $encounter->advanceTurn();
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());

        // Rewind once: back to actor2
        $encounter->rewindTurn();
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // Try to rewind again - should throw exception (no previous actor stored after first rewind)
        $this->expectException(\RuntimeException::class);
        $encounter->rewindTurn();
    }

    /**
     * Test rewindTurn() works correctly after advancing rounds.
     */
    public function testRewindTurnWorksAfterRoundAdvance(): void
    {
        // ARRANGE: Create encounter and advance to new round
        $actors = [
            ActorFactory::create('actor1', 'Fighter', 20),
            ActorFactory::create('actor2', 'Wizard', 15),
        ];

        $encounter = new Encounter(TimelineProfiles::dnd5e());
        foreach ($actors as $actor) {
            $encounter->addActor($actor);
        }
        $encounter->start();

        // Complete round 1
        $encounter->advanceTurn(); // actor1 acts
        $encounter->advanceTurn(); // actor2 acts, advances to round 2

        $this->assertSame(2, $encounter->getCurrentRound());
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // Advance to actor2 in round 2
        $encounter->advanceTurn();
        $this->assertSame('actor2', $encounter->getCurrentActor()->getId());

        // ACT: Rewind
        $encounter->rewindTurn();

        // ASSERT: Should be back to actor1 in round 2
        $this->assertSame(2, $encounter->getCurrentRound());
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
        $this->assertFalse($encounter->getActorState('actor1')->hasActed());
    }
}
