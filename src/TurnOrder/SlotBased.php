<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\TurnOrder;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\State\ActorState;
use Codryn\PhpTurnTracker\State\EncounterState;

/**
 * Slot-based turn order strategy (Genesys, FFG Star Wars).
 *
 * Initiative determines typed slots (PC/NPC) that any actor of matching type can fill.
 * Actors don't have fixed positions; they choose when to act within their slot type.
 */
class SlotBased implements TurnOrderInterface
{
    /** @var array<int, array{type: string, initiative: int, filled: bool, actorId: string|null}> */
    private array $slots = [];

    private int $currentSlotIndex = 0;

    /**
     * @param array<int, array{type: string, initiative: int}> $slotConfiguration
     */
    public function __construct(
        private array $slotConfiguration
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function calculateInitialOrder(array $actors): array
    {
        // Initialize slots from configuration
        $this->slots = [];
        foreach ($this->slotConfiguration as $index => $slot) {
            $this->slots[$index] = [
                'type' => $slot['type'],
                'initiative' => $slot['initiative'],
                'filled' => false,
                'actorId' => null,
            ];
        }

        $this->currentSlotIndex = 0;

        // Return actor IDs sorted by initiative for reference (not used for slot-based)
        usort($actors, fn ($a, $b) => $b->getInitiative() <=> $a->getInitiative());
        return array_map(fn ($a) => $a->getId(), $actors);
    }

    /**
     * {@inheritDoc}
     */
    public function getNextActor(
        array $actors,
        array $actorStates,
        EncounterState $encounterState
    ): ?string {
        // For slot-based, we don't automatically get next actor
        // The encounter must explicitly call fillSlot()
        // This method returns null to indicate waiting for slot fill
        return null;
    }

    /**
     * Get the current slot that needs to be filled.
     *
     * @return array{type: string, initiative: int, index: int}|null
     */
    public function getCurrentSlot(): ?array
    {
        if ($this->currentSlotIndex >= count($this->slots)) {
            return null;
        }

        $slot = $this->slots[$this->currentSlotIndex];
        return [
            'type' => $slot['type'],
            'initiative' => $slot['initiative'],
            'index' => $this->currentSlotIndex,
        ];
    }

    /**
     * Fill the current slot with an actor.
     *
     * @param string $actorId Actor to fill the slot
     * @param array<string, Actor> $actors All actors
     * @return bool True if slot was filled successfully
     */
    public function fillSlot(string $actorId, array $actors): bool
    {
        if ($this->currentSlotIndex >= count($this->slots)) {
            return false;
        }

        $currentSlot = $this->slots[$this->currentSlotIndex];
        $actor = $actors[$actorId] ?? null;

        if ($actor === null) {
            return false;
        }

        // Verify actor matches slot type
        $actorType = $actor->getAttributes()['type'] ?? null;
        if ($actorType !== $currentSlot['type']) {
            return false;
        }

        // Fill the slot
        $this->slots[$this->currentSlotIndex]['filled'] = true;
        $this->slots[$this->currentSlotIndex]['actorId'] = $actorId;

        // Move to next slot
        $this->currentSlotIndex++;

        return true;
    }

    /**
     * Get actors eligible for the current slot.
     *
     * @param array<string, ActorState> $actorStates
     * @param array<string, Actor> $actors
     * @return string[]
     */
    public function getEligibleActorsForCurrentSlot(array $actorStates, array $actors): array
    {
        if ($this->currentSlotIndex >= count($this->slots)) {
            return [];
        }

        $currentSlot = $this->slots[$this->currentSlotIndex];
        $eligible = [];

        foreach ($actors as $actorId => $actor) {
            $state = $actorStates[$actorId] ?? null;
            if ($state === null || $state->hasActed()) {
                continue;
            }

            $actorType = $actor->getAttributes()['type'] ?? null;
            if ($actorType === $currentSlot['type']) {
                $eligible[] = $actorId;
            }
        }

        return $eligible;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvanceRound(array $actorStates, EncounterState $encounterState): bool
    {
        // Round advances when all slots are filled
        return $this->currentSlotIndex >= count($this->slots);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvancePass(array $actorStates): bool
    {
        // Slot-based systems don't use passes
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function addActor(Actor $actor, array $allActors, EncounterState $encounterState): void
    {
        // Slots don't change when actors are added
        // Just need to recalculate turn order for reference
        $this->calculateInitialOrder($allActors);
    }

    /**
     * {@inheritDoc}
     */
    public function removeActor(string $actorId, EncounterState $encounterState): void
    {
        // If removed actor was filling a slot, we need to handle it
        foreach ($this->slots as $index => $slot) {
            if ($slot['actorId'] === $actorId) {
                $this->slots[$index]['filled'] = false;
                $this->slots[$index]['actorId'] = null;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function changeInitiative(
        string $actorId,
        int $newInitiative,
        array $actors,
        EncounterState $encounterState
    ): void {
        // Initiative changes don't affect slot configuration
        // Slots are fixed for the encounter
    }

    /**
     * Reset slots for a new round.
     */
    public function resetRound(): void
    {
        foreach ($this->slots as $index => $slot) {
            $this->slots[$index]['filled'] = false;
            $this->slots[$index]['actorId'] = null;
        }
        $this->currentSlotIndex = 0;
    }
}
