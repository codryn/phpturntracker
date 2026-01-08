<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\TurnOrder;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\State\EncounterState;

/**
 * Round-based individual initiative turn order (D&D 5e, Pathfinder style).
 *
 * Actors act in descending initiative order. When all actors have acted,
 * the round increments and the cycle repeats.
 */
class RoundBasedIndividual implements TurnOrderInterface
{
    private array $turnOrder = [];

    /**
     * {@inheritDoc}
     */
    public function calculateInitialOrder(array $actors): array
    {
        // Sort actors by initiative (descending), then by stable order for ties
        $sortedActors = $actors;

        usort($sortedActors, function (Actor $a, Actor $b): int {
            // Primary sort: initiative descending
            $initiativeCompare = $b->getInitiative() <=> $a->getInitiative();

            if ($initiativeCompare !== 0) {
                return $initiativeCompare;
            }

            // Tie-breaker: maintain stable order (preserve original array order)
            return 0;
        });

        // Extract and store actor IDs in turn order
        $this->turnOrder = array_map(fn (Actor $actor) => $actor->getId(), $sortedActors);

        return $this->turnOrder;
    }

    /**
     * {@inheritDoc}
     */
    public function getNextActor(
        array $actors,
        array $actorStates,
        EncounterState $encounterState
    ): ?string {
        if (empty($this->turnOrder)) {
            $this->calculateInitialOrder($actors);
        }

        if (empty($this->turnOrder)) {
            return null;
        }

        $currentActorId = $encounterState->getCurrentActorId();

        // If no current actor, return first in order
        if ($currentActorId === null) {
            return $this->turnOrder[0];
        }

        // Find current actor's position
        $currentIndex = array_search($currentActorId, $this->turnOrder, true);

        if ($currentIndex === false) {
            // Current actor not in turn order, return first
            return $this->turnOrder[0];
        }

        // Get next actor in sequence
        $nextIndex = $currentIndex + 1;

        // If we've reached the end, wrap to beginning (new round logic handled by Encounter)
        if ($nextIndex >= count($this->turnOrder)) {
            return $this->turnOrder[0];
        }

        return $this->turnOrder[$nextIndex];
    }

    /**
     * {@inheritDoc}
     */
    public function addActor(Actor $actor, EncounterState $encounterState): void
    {
        // Add actor to turn order in appropriate position based on initiative
        $newActorId = $actor->getId();
        $newInitiative = $actor->getInitiative();

        // If turn order empty, just add it
        if (empty($this->turnOrder)) {
            $this->turnOrder[] = $newActorId;
            return;
        }

        // Find insertion point (maintain descending initiative order)
        // For now, just append and rely on recalculation
        // A more sophisticated implementation would insert at correct position
        $this->turnOrder[] = $newActorId;
    }

    /**
     * {@inheritDoc}
     */
    public function removeActor(string $actorId, EncounterState $encounterState): void
    {
        $key = array_search($actorId, $this->turnOrder, true);

        if ($key !== false) {
            unset($this->turnOrder[$key]);
            // Reindex array to maintain sequential keys
            $this->turnOrder = array_values($this->turnOrder);
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
        // Remove and re-add actor to maintain correct order
        $this->removeActor($actorId, $encounterState);

        // Recalculate turn order with updated initiative
        if (isset($actors[$actorId])) {
            $this->calculateInitialOrder($actors);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvanceRound(array $actorStates): bool
    {
        // Round should advance when all actors have acted
        foreach ($actorStates as $state) {
            if (!$state->hasActed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvancePass(array $actorStates): bool
    {
        // Not applicable for round-based individual systems
        return false;
    }
}
