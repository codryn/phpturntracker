<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Unit\TurnOrder;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\State\ActorState;
use Codryn\PHPTurnTracker\State\EncounterState;
use Codryn\PHPTurnTracker\TurnOrder\SlotBased;
use Codryn\PHPTurnTracker\TurnOrderType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SlotBased turn order strategy.
 */
final class SlotBasedTest extends TestCase
{
    /**
     * Test slot generation from configuration.
     */
    public function testSlotGenerationFromConfiguration(): void
    {
        $slotConfig = [
            ['type' => 'PC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
            ['type' => 'PC', 'initiative' => 10],
        ];

        $strategy = new SlotBased($slotConfig);

        $actor1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $actors = [$actor1];

        $strategy->calculateInitialOrder($actors);

        $currentSlot = $strategy->getCurrentSlot();
        $this->assertNotNull($currentSlot);
        $this->assertSame('PC', $currentSlot['type']);
        $this->assertSame(20, $currentSlot['initiative']);
        $this->assertSame(0, $currentSlot['index']);
    }

    /**
     * Test actor-to-slot type matching.
     */
    public function testActorToSlotTypeMatching(): void
    {
        $slotConfig = [
            ['type' => 'PC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
        ];

        $strategy = new SlotBased($slotConfig);

        $pc1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $npc1 = new Actor('npc1', 'Goblin', 12, ['type' => 'NPC']);
        $actors = ['pc1' => $pc1, 'npc1' => $npc1];

        $strategy->calculateInitialOrder(array_values($actors));

        // Try to fill PC slot with NPC - should fail
        $this->assertFalse($strategy->fillSlot('npc1', $actors));

        // Fill PC slot with PC - should succeed
        $this->assertTrue($strategy->fillSlot('pc1', $actors));

        // Next slot should be NPC
        $currentSlot = $strategy->getCurrentSlot();
        $this->assertSame('NPC', $currentSlot['type']);
    }

    /**
     * Test slot filling logic.
     */
    public function testSlotFillingLogic(): void
    {
        $slotConfig = [
            ['type' => 'PC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
            ['type' => 'PC', 'initiative' => 10],
        ];

        $strategy = new SlotBased($slotConfig);

        $pc1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $pc2 = new Actor('pc2', 'Wizard', 14, ['type' => 'PC']);
        $npc1 = new Actor('npc1', 'Goblin', 12, ['type' => 'NPC']);

        $actors = ['pc1' => $pc1, 'pc2' => $pc2, 'npc1' => $npc1];
        $strategy->calculateInitialOrder(array_values($actors));

        // Fill first slot (PC)
        $this->assertTrue($strategy->fillSlot('pc1', $actors));

        // Current slot should advance to index 1 (NPC)
        $currentSlot = $strategy->getCurrentSlot();
        $this->assertSame(1, $currentSlot['index']);
        $this->assertSame('NPC', $currentSlot['type']);

        // Fill second slot (NPC)
        $this->assertTrue($strategy->fillSlot('npc1', $actors));

        // Current slot should advance to index 2 (PC)
        $currentSlot = $strategy->getCurrentSlot();
        $this->assertSame(2, $currentSlot['index']);
        $this->assertSame('PC', $currentSlot['type']);
    }

    /**
     * Test getting eligible actors for current slot.
     */
    public function testGetEligibleActorsForCurrentSlot(): void
    {
        $slotConfig = [
            ['type' => 'PC', 'initiative' => 20],
        ];

        $strategy = new SlotBased($slotConfig);

        $pc1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $pc2 = new Actor('pc2', 'Wizard', 14, ['type' => 'PC']);
        $npc1 = new Actor('npc1', 'Goblin', 12, ['type' => 'NPC']);

        $actors = ['pc1' => $pc1, 'pc2' => $pc2, 'npc1' => $npc1];
        $strategy->calculateInitialOrder(array_values($actors));

        $state1 = new ActorState('pc1');
        $state2 = new ActorState('pc2');
        $state3 = new ActorState('npc1');

        $actorStates = ['pc1' => $state1, 'pc2' => $state2, 'npc1' => $state3];

        // Get eligible actors for PC slot
        $eligible = $strategy->getEligibleActorsForCurrentSlot($actorStates, $actors);

        $this->assertCount(2, $eligible);
        $this->assertContains('pc1', $eligible);
        $this->assertContains('pc2', $eligible);
        $this->assertNotContains('npc1', $eligible);

        // Mark pc1 as acted
        $state1->markActed();

        // Now only pc2 should be eligible
        $eligible = $strategy->getEligibleActorsForCurrentSlot($actorStates, $actors);
        $this->assertCount(1, $eligible);
        $this->assertContains('pc2', $eligible);
    }

    /**
     * Test round advancement when all slots filled.
     */
    public function testShouldAdvanceRoundWhenAllSlotsFilled(): void
    {
        $slotConfig = [
            ['type' => 'PC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
        ];

        $strategy = new SlotBased($slotConfig);

        $pc1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $npc1 = new Actor('npc1', 'Goblin', 12, ['type' => 'NPC']);

        $actors = ['pc1' => $pc1, 'npc1' => $npc1];
        $strategy->calculateInitialOrder(array_values($actors));

        $encounterState = new EncounterState(TurnOrderType::SLOT);
        $actorStates = [];

        // Should not advance - slots not filled yet
        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));

        // Fill first slot
        $strategy->fillSlot('pc1', $actors);
        $this->assertFalse($strategy->shouldAdvanceRound($actorStates, $encounterState));

        // Fill second slot
        $strategy->fillSlot('npc1', $actors);

        // Now should advance
        $this->assertTrue($strategy->shouldAdvanceRound($actorStates, $encounterState));
    }

    /**
     * Test resetting slots for new round.
     */
    public function testResetRound(): void
    {
        $slotConfig = [
            ['type' => 'PC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
        ];

        $strategy = new SlotBased($slotConfig);

        $pc1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $npc1 = new Actor('npc1', 'Goblin', 12, ['type' => 'NPC']);

        $actors = ['pc1' => $pc1, 'npc1' => $npc1];
        $strategy->calculateInitialOrder(array_values($actors));

        // Fill both slots
        $strategy->fillSlot('pc1', $actors);
        $strategy->fillSlot('npc1', $actors);

        // Current slot should be null (all filled)
        $this->assertNull($strategy->getCurrentSlot());

        // Reset round
        $strategy->resetRound();

        // Should be back to first slot
        $currentSlot = $strategy->getCurrentSlot();
        $this->assertNotNull($currentSlot);
        $this->assertSame(0, $currentSlot['index']);
        $this->assertSame('PC', $currentSlot['type']);
    }

    /**
     * Test actor removal handling.
     */
    public function testActorRemoval(): void
    {
        $slotConfig = [
            ['type' => 'PC', 'initiative' => 20],
            ['type' => 'NPC', 'initiative' => 15],
        ];

        $strategy = new SlotBased($slotConfig);

        $pc1 = new Actor('pc1', 'Fighter', 18, ['type' => 'PC']);
        $npc1 = new Actor('npc1', 'Goblin', 12, ['type' => 'NPC']);
        $actors = ['pc1' => $pc1, 'npc1' => $npc1];

        $strategy->calculateInitialOrder(array_values($actors));

        // Fill first slot with pc1
        $strategy->fillSlot('pc1', $actors);

        // Current slot should be at index 1 (NPC)
        $currentSlot = $strategy->getCurrentSlot();
        $this->assertSame(1, $currentSlot['index']);

        $encounterState = new EncounterState(TurnOrderType::SLOT);

        // Remove pc1 - slot 0 should be unfilled but currentSlotIndex stays at 1
        $strategy->removeActor('pc1', $encounterState);

        // Current slot should still be at index 1
        $currentSlot = $strategy->getCurrentSlot();
        $this->assertNotNull($currentSlot);
        $this->assertSame(1, $currentSlot['index']);
    }

    /**
     * Test that slot-based systems don't use passes.
     */
    public function testShouldNotAdvancePass(): void
    {
        $slotConfig = [
            ['type' => 'PC', 'initiative' => 20],
        ];

        $strategy = new SlotBased($slotConfig);
        $actorStates = [];

        $this->assertFalse($strategy->shouldAdvancePass($actorStates));
    }
}
