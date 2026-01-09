<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\TurnOrder;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\State\ActorState;
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

        // If no current actor, return first in order (that can act this round)
        if ($currentActorId === null) {
            return $this->getFirstEligibleActor($actorStates, $encounterState);
        }

        // Find current actor's position
        $currentIndex = array_search($currentActorId, $this->turnOrder, true);

        if ($currentIndex === false) {
            // Current actor not in turn order, return first
            return $this->getFirstEligibleActor($actorStates, $encounterState);
        }

        // Get next actor in sequence (skip actors added mid-round)
        $nextIndex = $currentIndex + 1;

        // Search for next eligible actor
        while ($nextIndex < count($this->turnOrder)) {
            $nextActorId = $this->turnOrder[$nextIndex];

            // Check if actor was added in current round
            if (isset($actorStates[$nextActorId])) {
                $state = $actorStates[$nextActorId];
                $addedInRound = $state->getAddedInRound();

                // Skip if added in current round
                if ($addedInRound !== null && $addedInRound === $encounterState->getCurrentRound()) {
                    $nextIndex++;
                    continue;
                }
            }

            return $nextActorId;
        }

        // If we've reached the end, wrap to beginning (new round logic handled by Encounter)
        return $this->turnOrder[0];
    }

    /**
     * Get the first actor eligible to act this round.
     *
     * @param array<string, ActorState> $actorStates
     * @param EncounterState $encounterState
     * @return string|null
     */
    private function getFirstEligibleActor(array $actorStates, EncounterState $encounterState): ?string
    {
        foreach ($this->turnOrder as $actorId) {
            // Check if actor was added in current round
            if (isset($actorStates[$actorId])) {
                $state = $actorStates[$actorId];
                $addedInRound = $state->getAddedInRound();

                // Skip if added in current round
                if ($addedInRound !== null && $addedInRound === $encounterState->getCurrentRound()) {
                    continue;
                }
            }

            return $actorId;
        }

        // All actors were added this round, return first anyway
        return $this->turnOrder[0] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function addActor(Actor $actor, array $allActors, EncounterState $encounterState): void
    {
        // Recalculate entire turn order to maintain correct initiative positions
        $this->calculateInitialOrder($allActors);
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
        array $actorStates,
        EncounterState $encounterState
    ): void {
        // Recalculate turn order using current initiatives from actorStates
        $this->recalculateTurnOrderWithStates($actors, $actorStates);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvanceRound(array $actorStates, EncounterState $encounterState): bool
    {
        // Round should advance when all actors have acted
        // (excluding actors added mid-round)
        $currentRound = $encounterState->getCurrentRound();

        foreach ($actorStates as $state) {
            // Skip actors added in current round
            $addedInRound = $state->getAddedInRound();
            if ($addedInRound !== null && $addedInRound === $currentRound) {
                continue;
            }

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

    /**
     * Recalculate turn order using current initiative from actor states.
     *
     * @param Actor[] $actors
     * @param ActorState[] $actorStates
     */
    private function recalculateTurnOrderWithStates(array $actors, array $actorStates): void
    {
        $sortedActors = $actors;

        usort($sortedActors, function (Actor $a, Actor $b) use ($actorStates): int {
            // Use currentInitiative from state if available, otherwise use base initiative
            $aInit = $actorStates[$a->getId()]->getCurrentInitiative();
            $bInit = $actorStates[$b->getId()]->getCurrentInitiative();

            // Primary sort: initiative descending
            return $bInit <=> $aInit;
        });

        // Extract and store actor IDs in turn order
        $this->turnOrder = array_map(fn (Actor $actor) => $actor->getId(), $sortedActors);
    }
}
