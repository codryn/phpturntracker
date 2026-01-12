<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Integration;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\Encounter;
use Codryn\PHPTurnTracker\Exceptions\EncounterAlreadyActiveException;
use Codryn\PHPTurnTracker\State\EncounterSnapshot;
use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

class StateManagementTest extends TestCase
{
    public function testGetStateReturnsSnapshotOfInactiveEncounter(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter = new Encounter($profile);
        $encounter->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter->addActor(new Actor('actor2', 'Wizard', 15));

        $snapshot = $encounter->getState();

        $this->assertInstanceOf(EncounterSnapshot::class, $snapshot);
        $this->assertFalse($snapshot->getEncounterState()->isActive());
        $this->assertCount(2, $snapshot->getActors());
        $this->assertSame($profile->getType(), $snapshot->getProfile()->getType());
    }

    public function testGetStateReturnsSnapshotOfActiveEncounter(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter = new Encounter($profile);
        $encounter->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter->addActor(new Actor('actor2', 'Wizard', 15));
        $encounter->start();

        $snapshot = $encounter->getState();

        $this->assertTrue($snapshot->getEncounterState()->isActive());
        $this->assertSame(1, $snapshot->getEncounterState()->getCurrentRound());
        $this->assertSame('actor1', $snapshot->getEncounterState()->getCurrentActorId());
    }

    public function testGetStateReturnsSnapshotAfterSeveralTurns(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter = new Encounter($profile);
        $encounter->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter->addActor(new Actor('actor2', 'Wizard', 15));
        $encounter->addActor(new Actor('actor3', 'Rogue', 12));
        $encounter->start();

        // Advance a few turns
        $encounter->advanceTurn(); // actor2's turn
        $encounter->advanceTurn(); // actor3's turn

        $snapshot = $encounter->getState();

        $this->assertTrue($snapshot->getEncounterState()->isActive());
        $this->assertSame(1, $snapshot->getEncounterState()->getCurrentRound());
        $this->assertSame('actor3', $snapshot->getEncounterState()->getCurrentActorId());

        // Check actor states
        $actorStates = $snapshot->getActorStates();
        $this->assertTrue($actorStates['actor1']->hasActed());
        $this->assertTrue($actorStates['actor2']->hasActed());
        $this->assertFalse($actorStates['actor3']->hasActed());
    }

    public function testRestoreStateRecreatesExactEncounterState(): void
    {
        // Create and advance an encounter
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter1 = new Encounter($profile);
        $encounter1->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter1->addActor(new Actor('actor2', 'Wizard', 15));
        $encounter1->start();
        $encounter1->advanceTurn(); // actor2's turn

        // Capture state
        $snapshot = $encounter1->getState();

        // Create new encounter and restore state
        $encounter2 = new Encounter(new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL));
        $encounter2->restoreState($snapshot);

        // Verify restored state matches
        $this->assertTrue($encounter2->isActive());
        $this->assertSame(1, $encounter2->getCurrentRound());
        $this->assertSame('actor2', $encounter2->getCurrentActor()->getId());
        $this->assertCount(1, $encounter2->getActedActors());
        $this->assertCount(1, $encounter2->getUnactedActors());
    }

    public function testRestoreStateThrowsExceptionIfEncounterActive(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter1 = new Encounter($profile);
        $encounter1->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter1->start();

        $snapshot = $encounter1->getState();

        $encounter2 = new Encounter($profile);
        $encounter2->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter2->start(); // Make active

        $this->expectException(EncounterAlreadyActiveException::class);
        $this->expectExceptionMessage('currently active');

        $encounter2->restoreState($snapshot);
    }

    public function testRestoreStateAllowsResumingCombat(): void
    {
        // Create encounter and advance to round 2
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter1 = new Encounter($profile);
        $encounter1->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter1->addActor(new Actor('actor2', 'Wizard', 15));
        $encounter1->start();
        $encounter1->advanceTurn(); // actor2's turn
        $encounter1->advanceTurn(); // Round 2, actor1's turn

        $snapshot = $encounter1->getState();

        // Restore in new encounter
        $encounter2 = new Encounter(new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL));
        $encounter2->restoreState($snapshot);

        // Continue combat
        $this->assertSame(2, $encounter2->getCurrentRound());
        $this->assertSame('actor1', $encounter2->getCurrentActor()->getId());

        $encounter2->advanceTurn();
        $this->assertSame('actor2', $encounter2->getCurrentActor()->getId());
    }

    public function testGetStateAndRestoreWithPassBasedSystem(): void
    {
        $profile = new TimelineProfile(
            type: TurnOrderType::PASS,
            passesPerRound: 4,
            decayEnabled: true,
            decayAmount: 10
        );

        $encounter1 = new Encounter($profile);
        $encounter1->addActor(new Actor('sam', 'Street Samurai', 28)); // 3 passes
        $encounter1->addActor(new Actor('mage', 'Mage', 15)); // 2 passes
        $encounter1->start();

        // Advance through first pass
        $encounter1->advanceTurn();
        $encounter1->advanceTurn();
        $encounter1->advanceTurn(); // Now in pass 2

        $snapshot = $encounter1->getState();

        // Verify snapshot captures pass state
        $this->assertSame(2, $snapshot->getEncounterState()->getCurrentPass());

        // Restore in new encounter (profile will be replaced by snapshot)
        $encounter2 = new Encounter(new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL));
        $encounter2->restoreState($snapshot);

        $this->assertSame(2, $encounter2->getCurrentPass());
        $this->assertTrue($encounter2->isActive());
    }

    public function testJsonSerializationRoundTrip(): void
    {
        // Create and run an encounter
        $profile = new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 30,
            tieBreakerAttribute: 'dexterity'
        );

        $encounter1 = new Encounter($profile);
        $encounter1->addActor(new Actor('fighter', 'Conan', 18, ['dexterity' => 14]));
        $encounter1->addActor(new Actor('wizard', 'Gandalf', 15, ['dexterity' => 12]));
        $encounter1->start();
        $encounter1->advanceTurn();

        // Serialize to JSON
        $snapshot1 = $encounter1->getState();
        $json = $snapshot1->toJson();

        // Deserialize from JSON
        $snapshot2 = EncounterSnapshot::fromJsonString($json);

        // Restore in new encounter
        $encounter2 = new Encounter(new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL));
        $encounter2->restoreState($snapshot2);

        // Verify state matches
        $this->assertTrue($encounter2->isActive());
        $this->assertSame(1, $encounter2->getCurrentRound());
        $this->assertSame('wizard', $encounter2->getCurrentActor()->getId());
        $this->assertSame('Gandalf', $encounter2->getCurrentActor()->getName());
    }

    public function testRestoreStatePreservesDelayedInitiative(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter1 = new Encounter($profile);
        $encounter1->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter1->addActor(new Actor('actor2', 'Wizard', 15));
        $encounter1->start();

        // Delay actor1 to initiative 10
        $encounter1->delayActor('actor1', 10);

        $snapshot = $encounter1->getState();

        // Verify delayed initiative is in snapshot
        $actorStates = $snapshot->getActorStates();
        $this->assertSame(10, $actorStates['actor1']->getCurrentInitiative());

        // Restore
        $encounter2 = new Encounter(new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL));
        $encounter2->restoreState($snapshot);

        // Verify delayed state restored
        $state = $encounter2->getActorState('actor1');
        $this->assertSame(10, $state->getCurrentInitiative());
    }

    public function testRestoreStateWithMidCombatReinforcements(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter1 = new Encounter($profile);
        $encounter1->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter1->start();
        $encounter1->advanceTurn(); // Round 2

        // Add reinforcement mid-combat
        $encounter1->addActor(new Actor('actor2', 'Troll', 20));

        $snapshot = $encounter1->getState();

        // Verify addedInRound is tracked
        $actorStates = $snapshot->getActorStates();
        $this->assertNull($actorStates['actor1']->getAddedInRound());
        $this->assertSame(2, $actorStates['actor2']->getAddedInRound());

        // Restore
        $encounter2 = new Encounter(new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL));
        $encounter2->restoreState($snapshot);

        $state1 = $encounter2->getActorState('actor1');
        $state2 = $encounter2->getActorState('actor2');
        $this->assertNull($state1->getAddedInRound());
        $this->assertSame(2, $state2->getAddedInRound());
    }

    public function testGetStateCanBeCalledMultipleTimes(): void
    {
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter = new Encounter($profile);
        $encounter->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter->start();

        $snapshot1 = $encounter->getState();
        $snapshot2 = $encounter->getState();

        // Snapshots are independent objects
        $this->assertNotSame($snapshot1, $snapshot2);
        $this->assertEquals($snapshot1->toJson(), $snapshot2->toJson());
    }

    public function testRestoreStateReplacesProfile(): void
    {
        // Create encounter with ROUND_INDIVIDUAL
        $profile1 = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter1 = new Encounter($profile1);
        $encounter1->addActor(new Actor('actor1', 'Fighter', 18));
        $encounter1->start();

        $snapshot = $encounter1->getState();

        // Create encounter with ROUND_SIDE profile (will be replaced)
        $profile2 = new TimelineProfile(TurnOrderType::ROUND_SIDE);
        $encounter2 = new Encounter($profile2);

        // Restore ROUND_INDIVIDUAL state
        $encounter2->restoreState($snapshot);

        // Verify profile was replaced
        $restoredSnapshot = $encounter2->getState();
        $this->assertSame(TurnOrderType::ROUND_INDIVIDUAL, $restoredSnapshot->getProfile()->getType());
    }

    public function testRestoreStateWithEmptyEncounter(): void
    {
        // Create empty snapshot (before encounter started)
        $profile = new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL);
        $encounter1 = new Encounter($profile);
        $snapshot = $encounter1->getState();

        // Restore in new encounter
        $encounter2 = new Encounter(new TimelineProfile(TurnOrderType::ROUND_INDIVIDUAL));
        $encounter2->restoreState($snapshot);

        $this->assertFalse($encounter2->isActive());
        $this->assertSame(0, $encounter2->getCurrentRound());
        $this->assertNull($encounter2->getCurrentActor());
    }
}
