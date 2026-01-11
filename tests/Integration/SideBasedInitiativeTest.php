<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Integration;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for User Story 6: Side-Based Initiative.
 *
 * Tests scenarios where entire teams (Players vs. Monsters) act together
 * rather than individual actors taking turns.
 */
final class SideBasedInitiativeTest extends TestCase
{
    /**
     * User Story 6 - Scenario 1:
     * Side-based encounter with Players (initiative 15) and Monsters (initiative 12).
     * When encounter starts, Players side is current and all player actors available.
     */
    public function testSideBasedEncounterStartsWithWinningSide(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_SIDE);

        // Create actors with side attributes
        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $wizard = new Actor('wizard', 'Wizard', 15, ['side' => 'Players']);
        $goblin1 = new Actor('goblin1', 'Goblin', 12, ['side' => 'Monsters']);
        $goblin2 = new Actor('goblin2', 'Goblin', 12, ['side' => 'Monsters']);

        $encounter = new Encounter($profile);
        $encounter->addActor($fighter);
        $encounter->addActor($wizard);
        $encounter->addActor($goblin1);
        $encounter->addActor($goblin2);

        $encounter->start();

        // Players side (initiative 15) should be current
        $this->assertTrue($encounter->isActive());
        $this->assertSame(1, $encounter->getCurrentRound());

        // All player actors should be available (not acted yet)
        $unacted = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($actor) => $actor->getId(), $unacted);
        $this->assertContains('fighter', $unactedIds);
        $this->assertContains('wizard', $unactedIds);

        // Monster actors should not be in current side yet
        // (implementation detail: may or may not be in unacted list depending on strategy)
    }

    /**
     * User Story 6 - Scenario 2:
     * Players side is current with 3 player actors.
     * When all players have acted, Monsters side becomes current.
     */
    public function testAllFirstSideActedSecondSideBecomesCurrent(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_SIDE);

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $wizard = new Actor('wizard', 'Wizard', 15, ['side' => 'Players']);
        $rogue = new Actor('rogue', 'Rogue', 15, ['side' => 'Players']);
        $goblin1 = new Actor('goblin1', 'Goblin', 12, ['side' => 'Monsters']);
        $goblin2 = new Actor('goblin2', 'Goblin', 12, ['side' => 'Monsters']);

        $encounter = new Encounter($profile);
        $encounter->addActor($fighter);
        $encounter->addActor($wizard);
        $encounter->addActor($rogue);
        $encounter->addActor($goblin1);
        $encounter->addActor($goblin2);

        $encounter->start();

        // All players act
        $encounter->advanceTurn(); // Fighter acts
        $encounter->advanceTurn(); // Wizard acts
        $encounter->advanceTurn(); // Rogue acts

        // Now Monsters side should be current
        $unacted = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($actor) => $actor->getId(), $unacted);
        $this->assertContains('goblin1', $unactedIds);
        $this->assertContains('goblin2', $unactedIds);

        // Players should have acted
        $this->assertNotContains('fighter', $unactedIds);
        $this->assertNotContains('wizard', $unactedIds);
        $this->assertNotContains('rogue', $unactedIds);
    }

    /**
     * User Story 6 - Scenario 3:
     * Monsters side completes all actions.
     * Round advances and Players side becomes current again with reset.
     */
    public function testRoundCompletionResetsToFirstSide(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_SIDE);

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $encounter = new Encounter($profile);
        $encounter->addActor($fighter);
        $encounter->addActor($goblin);

        $encounter->start();

        // Complete round 1
        $encounter->advanceTurn(); // Fighter acts
        $encounter->advanceTurn(); // Goblin acts

        // Should advance to round 2
        $this->assertSame(2, $encounter->getCurrentRound());

        // Players side should be current again (first side)
        // Only players should be in unacted for current side
        $unacted = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($actor) => $actor->getId(), $unacted);
        $this->assertContains('fighter', $unactedIds);

        // Goblin from Monsters side should not be in current unacted (different side)
        $this->assertNotContains('goblin', $unactedIds);
    }

    /**
     * User Story 6 - Scenario 4:
     * Round 1 with Players side current, 1 of 3 players has acted.
     * New player actor added can act immediately this round.
     */
    public function testAddActorToActiveSideCanActImmediately(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_SIDE);

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $wizard = new Actor('wizard', 'Wizard', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $encounter = new Encounter($profile);
        $encounter->addActor($fighter);
        $encounter->addActor($wizard);
        $encounter->addActor($goblin);

        $encounter->start();

        // Fighter acts
        $encounter->advanceTurn();

        // Add new player to active Players side
        $rogue = new Actor('rogue', 'Rogue', 15, ['side' => 'Players']);
        $encounter->addActor($rogue);

        // Rogue should be available to act (in unacted list)
        $unacted = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($actor) => $actor->getId(), $unacted);
        $this->assertContains('rogue', $unactedIds);
        $this->assertContains('wizard', $unactedIds); // Wizard still hasn't acted

        // Wizard acts
        $encounter->advanceTurn();

        // Rogue should still be available
        $unacted = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($actor) => $actor->getId(), $unacted);
        $this->assertContains('rogue', $unactedIds);
    }
}
