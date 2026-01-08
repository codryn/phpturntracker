<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Unit\TurnOrder;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\State\ActorState;
use Codryn\PhpTurnTracker\State\EncounterState;
use Codryn\PhpTurnTracker\TurnOrder\RoundBasedSide;
use Codryn\PhpTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RoundBasedSide turn order strategy.
 */
final class RoundBasedSideTest extends TestCase
{
    /**
     * Test grouping actors by side attribute.
     */
    public function testGroupActorsBySide(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $wizard = new Actor('wizard', 'Wizard', 15, ['side' => 'Players']);
        $goblin1 = new Actor('goblin1', 'Goblin', 12, ['side' => 'Monsters']);
        $goblin2 = new Actor('goblin2', 'Goblin', 12, ['side' => 'Monsters']);

        $actors = [$fighter, $wizard, $goblin1, $goblin2];
        $strategy->calculateInitialOrder($actors);

        // Players side (15) should be current (higher initiative)
        $this->assertSame('Players', $strategy->getCurrentSide());
    }

    /**
     * Test sides are ordered by initiative descending.
     */
    public function testSideOrderingByInitiative(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $dragon = new Actor('dragon', 'Dragon', 20, ['side' => 'Dragons']);
        $goblin = new Actor('goblin', 'Goblin', 10, ['side' => 'Monsters']);

        $actors = [$fighter, $goblin, $dragon]; // Intentionally unsorted
        $strategy->calculateInitialOrder($actors);

        // Dragons (20) should be current (highest initiative)
        $this->assertSame('Dragons', $strategy->getCurrentSide());
    }

    /**
     * Test getting next actor from current side.
     */
    public function testGetNextActorFromCurrentSide(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $wizard = new Actor('wizard', 'Wizard', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $actors = ['fighter' => $fighter, 'wizard' => $wizard, 'goblin' => $goblin];
        $strategy->calculateInitialOrder(array_values($actors));

        $state1 = new ActorState('fighter');
        $state2 = new ActorState('wizard');
        $state3 = new ActorState('goblin');

        $actorStates = ['fighter' => $state1, 'wizard' => $state2, 'goblin' => $state3];
        $encounterState = new EncounterState(TurnOrderType::ROUND_SIDE);

        // Should return an actor from Players side (first side)
        $nextActor = $strategy->getNextActor($actors, $actorStates, $encounterState);
        $this->assertContains($nextActor, ['fighter', 'wizard']);
    }

    /**
     * Test checking if all actors in current side have acted.
     */
    public function testAllSideActorsHaveActed(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $wizard = new Actor('wizard', 'Wizard', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $actors = [$fighter, $wizard, $goblin];
        $strategy->calculateInitialOrder($actors);

        $state1 = new ActorState('fighter');
        $state2 = new ActorState('wizard');
        $state3 = new ActorState('goblin');

        $actorStates = ['fighter' => $state1, 'wizard' => $state2, 'goblin' => $state3];

        // Initially, not all have acted
        $this->assertFalse($strategy->allSideActorsHaveActed($actorStates));

        // Mark fighter as acted
        $state1->markActed();
        $this->assertFalse($strategy->allSideActorsHaveActed($actorStates));

        // Mark wizard as acted
        $state2->markActed();
        $this->assertTrue($strategy->allSideActorsHaveActed($actorStates));
    }

    /**
     * Test advancing to next side.
     */
    public function testAdvanceToNextSide(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $actors = [$fighter, $goblin];
        $strategy->calculateInitialOrder($actors);

        // Initially Players
        $this->assertSame('Players', $strategy->getCurrentSide());

        // Advance to Monsters
        $strategy->advanceToNextSide();
        $this->assertSame('Monsters', $strategy->getCurrentSide());
    }

    /**
     * Test resetting for new round.
     */
    public function testResetForNewRound(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $actors = [$fighter, $goblin];
        $strategy->calculateInitialOrder($actors);

        // Advance to second side
        $strategy->advanceToNextSide();
        $this->assertSame('Monsters', $strategy->getCurrentSide());

        // Reset should go back to first side
        $strategy->resetForNewRound();
        $this->assertSame('Players', $strategy->getCurrentSide());
    }

    /**
     * Test adding actor to existing side.
     */
    public function testAddActorToExistingSide(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $actors = ['fighter' => $fighter, 'goblin' => $goblin];
        $strategy->calculateInitialOrder(array_values($actors));

        $encounterState = new EncounterState(TurnOrderType::ROUND_SIDE);

        // Add new player to Players side
        $wizard = new Actor('wizard', 'Wizard', 15, ['side' => 'Players']);
        $actors['wizard'] = $wizard;
        $strategy->addActor($wizard, $actors, $encounterState);

        // Players should still be current side
        $this->assertSame('Players', $strategy->getCurrentSide());
    }

    /**
     * Test adding actor creates new side.
     */
    public function testAddActorCreatesNewSide(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $actors = ['fighter' => $fighter];
        $strategy->calculateInitialOrder(array_values($actors));

        $encounterState = new EncounterState(TurnOrderType::ROUND_SIDE);

        // Add actor from new side with higher initiative
        $dragon = new Actor('dragon', 'Dragon', 25, ['side' => 'Dragons']);
        $actors['dragon'] = $dragon;
        $strategy->addActor($dragon, $actors, $encounterState);

        // Dragons should now be current side (highest initiative)
        $this->assertSame('Dragons', $strategy->getCurrentSide());
    }

    /**
     * Test removing actor from side.
     */
    public function testRemoveActorFromSide(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $wizard = new Actor('wizard', 'Wizard', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $actors = [$fighter, $wizard, $goblin];
        $strategy->calculateInitialOrder($actors);

        $encounterState = new EncounterState(TurnOrderType::ROUND_SIDE);

        // Remove fighter
        $strategy->removeActor('fighter', $encounterState);

        // Players side should still exist (wizard remains)
        $this->assertSame('Players', $strategy->getCurrentSide());
    }

    /**
     * Test round advancement condition.
     */
    public function testShouldAdvanceRound(): void
    {
        $strategy = new RoundBasedSide();

        $fighter = new Actor('fighter', 'Fighter', 15, ['side' => 'Players']);
        $goblin = new Actor('goblin', 'Goblin', 12, ['side' => 'Monsters']);

        $actors = [$fighter, $goblin];
        $strategy->calculateInitialOrder($actors);

        $state1 = new ActorState('fighter');
        $state2 = new ActorState('goblin');

        $actorStates = ['fighter' => $state1, 'goblin' => $state2];
        $encounterState = new EncounterState(TurnOrderType::ROUND_SIDE);

        // Should not advance - still on first side
        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));

        // Mark fighter as acted and advance to next side
        $state1->markActed();
        $strategy->advanceToNextSide();

        // Still should not advance - on second side but goblin hasn't acted
        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));

        // Mark goblin as acted
        $state2->markActed();

        // Now should advance - on last side and all acted
        $this->assertTrue($strategy->shouldAdvanceRound($actorStates, $encounterState));
    }

    /**
     * Test that side-based doesn't use passes.
     */
    public function testShouldNotAdvancePass(): void
    {
        $strategy = new RoundBasedSide();
        $actorStates = [];

        $this->assertFalse($strategy->shouldAdvancePass($actorStates));
    }

    /**
     * Test actors without side attribute get default side.
     */
    public function testActorsWithoutSideGetDefault(): void
    {
        $strategy = new RoundBasedSide();

        $actor1 = new Actor('actor1', 'Generic', 15); // No side attribute
        $actor2 = new Actor('actor2', 'Generic2', 15); // No side attribute

        $actors = [$actor1, $actor2];
        $strategy->calculateInitialOrder($actors);

        // Both should be grouped into 'default' side
        $this->assertSame('default', $strategy->getCurrentSide());
    }
}
