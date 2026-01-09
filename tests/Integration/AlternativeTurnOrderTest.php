<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Tests\Integration;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\Encounter;
use Codryn\PhpTurnTracker\TimelineProfile;
use Codryn\PhpTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for User Story 5: Alternative Turn Order Systems.
 *
 * Tests slot-based initiative (Genesys) and popcorn initiative (Marvel Heroic).
 */
final class AlternativeTurnOrderTest extends TestCase
{
    /**
     * US5 Scenario 1: Slot-based encounter with typed slots.
     *
     * Given a Genesys encounter with 2 PC slots and 3 NPC slots generated
     * When I start the encounter
     * Then the first slot (type: PC or NPC based on roll results) is current
     * And any actor of that type can be chosen to act
     */
    public function testSlotBasedEncounterWithTypedSlots(): void
    {
        // ARRANGE: Configure slot-based timeline with specific slot pattern
        $slotConfiguration = [
            ['type' => 'PC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
            ['type' => 'PC', 'initiative' => 10],
            ['type' => 'NPC', 'initiative' => 8],
            ['type' => 'NPC', 'initiative' => 5],
        ];

        $profile = new TimelineProfile(
            type: TurnOrderType::SLOT,
            slotConfiguration: $slotConfiguration
        );

        $encounter = new Encounter($profile);

        // Add actors with type attribute
        $pc1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $pc2 = new Actor('pc2', 'Wizard', 14, ['type' => 'PC']);
        $npc1 = new Actor('npc1', 'Goblin 1', 12, ['type' => 'NPC']);
        $npc2 = new Actor('npc2', 'Goblin 2', 9, ['type' => 'NPC']);
        $npc3 = new Actor('npc3', 'Goblin 3', 7, ['type' => 'NPC']);

        $encounter->addActor($pc1);
        $encounter->addActor($pc2);
        $encounter->addActor($npc1);
        $encounter->addActor($npc2);
        $encounter->addActor($npc3);

        // ACT: Start encounter
        $encounter->start();

        // ASSERT: First slot should be current (PC slot at initiative 20)
        $this->assertSame(1, $encounter->getCurrentRound());
        $currentSlot = $encounter->getCurrentSlot();
        $this->assertNotNull($currentSlot);
        $this->assertSame('PC', $currentSlot['type']);
        $this->assertSame(20, $currentSlot['initiative']);

        // Any PC actor can fill this slot
        $unactedActors = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($a) => $a->getId(), $unactedActors);

        // Should have both PCs available for this slot
        $this->assertContains('pc1', $unactedIds);
        $this->assertContains('pc2', $unactedIds);
    }

    /**
     * US5 Scenario 2: Popcorn encounter with designation.
     *
     * Given a popcorn initiative encounter with 5 actors
     * When current actor completes their turn
     * Then they can designate any other actor who hasn't acted this round as the next actor
     */
    public function testPopcornEncounterWithDesignation(): void
    {
        // ARRANGE: Configure popcorn initiative
        $profile = new TimelineProfile(
            type: TurnOrderType::POPCORN
        );

        $encounter = new Encounter($profile);

        // Add actors (initiative determines first actor)
        $actor1 = new Actor('actor1', 'Hero 1', 20);
        $actor2 = new Actor('actor2', 'Hero 2', 15);
        $actor3 = new Actor('actor3', 'Villain 1', 18);
        $actor4 = new Actor('actor4', 'Villain 2', 12);
        $actor5 = new Actor('actor5', 'Sidekick', 10);

        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->addActor($actor4);
        $encounter->addActor($actor5);

        $encounter->start();

        // First actor is highest initiative (actor1)
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());

        // ACT: Actor1 designates actor3 as next
        $encounter->designateNext('actor3');

        // ASSERT: Actor3 should now be current
        $this->assertSame('actor3', $encounter->getCurrentActor()->getId());

        // Actor1 should be marked as acted
        $unactedActors = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($a) => $a->getId(), $unactedActors);
        $this->assertNotContains('actor1', $unactedIds);
        $this->assertContains('actor2', $unactedIds);
        $this->assertContains('actor4', $unactedIds);
        $this->assertContains('actor5', $unactedIds);
    }

    /**
     * US5 Scenario 3: Slot-based with actor chosen for slot.
     *
     * Given a slot-based encounter with current slot designated "NPC"
     * When an NPC actor is chosen to fill that slot
     * Then that actor is marked as acted and the next slot becomes current
     */
    public function testSlotBasedActorFillsSlot(): void
    {
        // ARRANGE
        $slotConfiguration = [
            ['type' => 'PC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
            ['type' => 'PC', 'initiative' => 10],
        ];

        $profile = new TimelineProfile(
            type: TurnOrderType::SLOT,
            slotConfiguration: $slotConfiguration
        );

        $encounter = new Encounter($profile);

        $pc1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $npc1 = new Actor('npc1', 'Goblin', 12, ['type' => 'NPC']);

        $encounter->addActor($pc1);
        $encounter->addActor($npc1);
        $encounter->start();

        // Fill first slot (PC) with pc1
        $encounter->fillSlot('pc1');

        // ACT: Current slot should now be NPC slot
        $currentSlot = $encounter->getCurrentSlot();
        $this->assertSame('NPC', $currentSlot['type']);
        $this->assertSame(15, $currentSlot['initiative']);

        // Fill second slot (NPC) with npc1
        $encounter->fillSlot('npc1');

        // ASSERT: npc1 should be marked as acted
        $unactedActors = $encounter->getUnactedActors();
        $unactedIds = array_map(fn ($a) => $a->getId(), $unactedActors);
        $this->assertNotContains('npc1', $unactedIds);

        // Next slot (PC) should be current
        $currentSlot = $encounter->getCurrentSlot();
        $this->assertSame('PC', $currentSlot['type']);
        $this->assertSame(10, $currentSlot['initiative']);
    }

    /**
     * US5 Scenario 4: Popcorn with last unacted actor.
     *
     * Given a popcorn encounter where 4 of 5 actors have acted
     * When current actor completes their turn
     * Then the only remaining actor who hasn't acted automatically becomes current
     */
    public function testPopcornLastUnactedActorAutomatic(): void
    {
        // ARRANGE
        $profile = new TimelineProfile(
            type: TurnOrderType::POPCORN
        );

        $encounter = new Encounter($profile);

        $actor1 = new Actor('actor1', 'Actor 1', 20);
        $actor2 = new Actor('actor2', 'Actor 2', 15);
        $actor3 = new Actor('actor3', 'Actor 3', 10);
        $actor4 = new Actor('actor4', 'Actor 4', 8);
        $actor5 = new Actor('actor5', 'Actor 5', 5);

        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);
        $encounter->addActor($actor4);
        $encounter->addActor($actor5);

        $encounter->start();

        // Actor1 -> Actor3 -> Actor2 -> Actor4 (4 have acted, only actor5 left)
        $encounter->designateNext('actor3');
        $encounter->designateNext('actor2');
        $encounter->designateNext('actor4');

        // ACT: Actor4 has no choice, actor5 is the only one left
        $encounter->designateNext('actor5');

        // ASSERT: Actor5 should be current
        $this->assertSame('actor5', $encounter->getCurrentActor()->getId());

        // When actor5 finishes, round should advance (no one left to designate)
        $encounter->advanceTurn();

        $this->assertSame(2, $encounter->getCurrentRound());
    }

    /**
     * US5 Scenario 5: Round completion resets slots/actors.
     *
     * Given all slots filled or all actors having acted in popcorn mode
     * When next turn is requested
     * Then round increments and all slots/actors reset for a new round
     */
    public function testRoundCompletionResetsState(): void
    {
        // ARRANGE: Popcorn with 3 actors
        $profile = new TimelineProfile(
            type: TurnOrderType::POPCORN
        );

        $encounter = new Encounter($profile);

        $actor1 = new Actor('actor1', 'Actor 1', 20);
        $actor2 = new Actor('actor2', 'Actor 2', 15);
        $actor3 = new Actor('actor3', 'Actor 3', 10);

        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);

        $encounter->start();

        // ACT: All actors act: actor1 -> actor2 -> actor3
        $encounter->designateNext('actor2');
        $encounter->designateNext('actor3');

        // After actor3 acts, round should complete
        $encounter->advanceTurn();

        // ASSERT: Round 2 should begin
        $this->assertSame(2, $encounter->getCurrentRound());

        // All actors should be unacted again
        $unactedActors = $encounter->getUnactedActors();
        $this->assertCount(3, $unactedActors);

        $unactedIds = array_map(fn ($a) => $a->getId(), $unactedActors);
        $this->assertContains('actor1', $unactedIds);
        $this->assertContains('actor2', $unactedIds);
        $this->assertContains('actor3', $unactedIds);
    }

    /**
     * US5 Scenario 6: Popcorn with allowRepeatPopcorn.
     *
     * Given popcorn mode with allowRepeatPopcorn=true and all actors have acted
     * When current actor designates an actor who already acted
     * Then that actor becomes current again and can take another action within the same round
     */
    public function testPopcornAllowRepeatDesignation(): void
    {
        // ARRANGE: Popcorn with allowRepeatPopcorn enabled
        $profile = new TimelineProfile(
            type: TurnOrderType::POPCORN,
            allowRepeatPopcorn: true
        );

        $encounter = new Encounter($profile);

        $actor1 = new Actor('actor1', 'Actor 1', 20);
        $actor2 = new Actor('actor2', 'Actor 2', 15);
        $actor3 = new Actor('actor3', 'Actor 3', 10);

        $encounter->addActor($actor1);
        $encounter->addActor($actor2);
        $encounter->addActor($actor3);

        $encounter->start();

        // All actors act once: actor1 -> actor2 -> actor3
        $encounter->designateNext('actor2');
        $encounter->designateNext('actor3');

        // ACT: Actor3 re-designates actor1 (who already acted)
        $encounter->designateNext('actor1');

        // ASSERT: Actor1 should be current again
        $this->assertSame('actor1', $encounter->getCurrentActor()->getId());
        $this->assertSame(1, $encounter->getCurrentRound()); // Still round 1
    }

    /**
     * US5 Edge Case: Slot-based with insufficient actors.
     *
     * Given 3 slots configured but only 2 actors available
     * When slots need to be filled
     * Then verify behavior (skip slot, reuse actor, or error)
     */
    public function testSlotBasedInsufficientActors(): void
    {
        // ARRANGE: 3 NPC slots but only 2 NPC actors
        $slotConfiguration = [
            ['type' => 'NPC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
            ['type' => 'NPC', 'initiative' => 10],
        ];

        $profile = new TimelineProfile(
            type: TurnOrderType::SLOT,
            slotConfiguration: $slotConfiguration
        );

        $encounter = new Encounter($profile);

        $npc1 = new Actor('npc1', 'Goblin 1', 12, ['type' => 'NPC']);
        $npc2 = new Actor('npc2', 'Goblin 2', 9, ['type' => 'NPC']);

        $encounter->addActor($npc1);
        $encounter->addActor($npc2);
        $encounter->start();

        // Fill first two slots
        $encounter->fillSlot('npc1');
        $encounter->fillSlot('npc2');

        // ACT/ASSERT: Third slot should skip or allow reuse
        // For now, expect slot to be available but with no eligible actors
        $currentSlot = $encounter->getCurrentSlot();
        $this->assertSame('NPC', $currentSlot['type']);

        $unactedActors = $encounter->getUnactedActors();
        $this->assertCount(0, $unactedActors); // No unacted NPCs left
    }
}
