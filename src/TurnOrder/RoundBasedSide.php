<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\TurnOrder;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\State\ActorState;
use Codryn\PHPTurnTracker\State\EncounterState;

/**
 * Side-based turn order strategy (B/X D&D, AD&D, OSR).
 *
 * Groups actors by their 'side' attribute (e.g., "Players" vs "Monsters").
 * Each side rolls initiative collectively, and all members of the winning side
 * act before the losing side. Within a side, actors can act in any order.
 */
class RoundBasedSide implements TurnOrderInterface
{
    /** @var array<string, array{initiative: int, actorIds: string[]}> Side name => side data */
    private array $sides = [];

    /** @var string[] Order of sides by initiative (descending) */
    private array $sideOrder = [];

    /** @var int Current side index in sideOrder */
    private int $currentSideIndex = 0;

    /**
     * Sort sides by initiative and rebuild order.
     */
    private function sortSidesByInitiative(): void
    {
        // Create sorted array manually to preserve type structure
        $sidesList = [];
        foreach ($this->sides as $sideName => $sideData) {
            $sidesList[] = [
                'name' => $sideName,
                'initiative' => $sideData['initiative'],
                'actorIds' => $sideData['actorIds'],
            ];
        }

        usort($sidesList, fn ($a, $b) => $b['initiative'] <=> $a['initiative']);

        $this->sides = [];
        foreach ($sidesList as $side) {
            $this->sides[$side['name']] = [
                'initiative' => $side['initiative'],
                'actorIds' => $side['actorIds'],
            ];
        }

        $this->sideOrder = array_keys($this->sides);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateInitialOrder(array $actors): array
    {
        // Group actors by side attribute
        $this->sides = [];
        foreach ($actors as $actor) {
            $sideAttr = $actor->getAttributes()['side'] ?? 'default';
            $side = is_string($sideAttr) ? $sideAttr : 'default';

            if (!isset($this->sides[$side])) {
                $this->sides[$side] = [
                    'initiative' => $actor->getInitiative(),
                    'actorIds' => [],
                ];
            }

            $this->sides[$side]['actorIds'][] = $actor->getId();
        }

        // Sort sides by initiative (descending)
        $this->sortSidesByInitiative();

        $this->currentSideIndex = 0;

        // Return all actor IDs (for compatibility)
        $allActorIds = [];
        foreach ($this->sides as $sideData) {
            $allActorIds = array_merge($allActorIds, $sideData['actorIds']);
        }

        return $allActorIds;
    }

    /**
     * {@inheritDoc}
     */
    public function getNextActor(
        array $actors,
        array $actorStates,
        EncounterState $encounterState
    ): ?string {
        // In side-based initiative, we don't automatically select next actor
        // The GM chooses which actor from current side acts
        // Return any unacted actor from current side, or null if all acted

        if (empty($this->sideOrder)) {
            return null;
        }

        $currentSide = $this->sideOrder[$this->currentSideIndex];
        $sideActors = $this->sides[$currentSide]['actorIds'];

        // Find first unacted actor from current side
        foreach ($sideActors as $actorId) {
            $state = $actorStates[$actorId] ?? null;
            if ($state !== null && !$state->hasActed()) {
                return $actorId;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvanceRound(array $actorStates, EncounterState $encounterState): bool
    {
        // Round advances when all sides have completed
        return $this->currentSideIndex >= count($this->sideOrder) - 1
            && $this->allSideActorsHaveActed($actorStates);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvancePass(array $actorStates): bool
    {
        // Side-based doesn't use passes
        return false;
    }

    /**
     * Check if all actors in current side have acted.
     *
     * @param array<string, ActorState> $actorStates
     * @return bool
     */
    public function allSideActorsHaveActed(array $actorStates): bool
    {
        if (empty($this->sideOrder) || $this->currentSideIndex >= count($this->sideOrder)) {
            return true;
        }

        $currentSide = $this->sideOrder[$this->currentSideIndex];
        $sideActors = $this->sides[$currentSide]['actorIds'];

        foreach ($sideActors as $actorId) {
            $state = $actorStates[$actorId] ?? null;
            if ($state !== null && !$state->hasActed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Advance to the next side.
     */
    public function advanceToNextSide(): void
    {
        $this->currentSideIndex++;
    }

    /**
     * Reset to first side for a new round.
     */
    public function resetForNewRound(): void
    {
        $this->currentSideIndex = 0;
    }

    /**
     * Get current side name.
     *
     * @return string|null
     */
    public function getCurrentSide(): ?string
    {
        if (empty($this->sideOrder) || $this->currentSideIndex >= count($this->sideOrder)) {
            return null;
        }

        return $this->sideOrder[$this->currentSideIndex];
    }

    /**
     * {@inheritDoc}
     */
    public function addActor(Actor $actor, array $allActors, EncounterState $encounterState): void
    {
        $sideAttr = $actor->getAttributes()['side'] ?? 'default';
        $side = is_string($sideAttr) ? $sideAttr : 'default';

        // Add to existing side or create new side
        if (!isset($this->sides[$side])) {
            $this->sides[$side] = [
                'initiative' => $actor->getInitiative(),
                'actorIds' => [],
            ];

            // Re-sort sides and rebuild order
            $this->sortSidesByInitiative();
        }

        $this->sides[$side]['actorIds'][] = $actor->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function removeActor(string $actorId, EncounterState $encounterState): void
    {
        // Find and remove actor from their side
        foreach ($this->sides as $sideName => $sideData) {
            $key = array_search($actorId, $sideData['actorIds'], true);
            if ($key !== false) {
                unset($sideData['actorIds'][$key]);
                $reindexedActorIds = array_values($sideData['actorIds']);

                // Reassign the whole side structure to preserve type shape
                $this->sides[$sideName] = [
                    'initiative' => $sideData['initiative'],
                    'actorIds' => $reindexedActorIds,
                ];

                // Remove side if empty
                if (empty($this->sides[$sideName]['actorIds'])) {
                    unset($this->sides[$sideName]);
                    $this->sideOrder = array_keys($this->sides);
                }

                break;
            }
        }
    }

    /**
     * {@inheritDoc}\n     */
    public function changeInitiative(
        string $actorId,
        int $newInitiative,
        array $actors,
        array $actorStates,
        EncounterState $encounterState
    ): void {
        // In side-based initiative, individual actor initiative changes don't affect turn order
        // Only the side's collective initiative matters
        // This would require re-rolling for the entire side, which is typically not done mid-combat

        // No-op: side initiative is determined collectively, not per-actor
    }
}
