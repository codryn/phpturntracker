<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\TurnOrder;

use Codryn\PHPTurnTracker\Actor;
use Codryn\PHPTurnTracker\State\ActorState;
use Codryn\PHPTurnTracker\State\EncounterState;

/**
 * Strategy interface for different turn order systems.
 *
 * Each turn order type (round-individual, pass-based, popcorn, etc.)
 * implements this interface to provide system-specific logic for
 * calculating turn order, advancing turns, and handling state changes.
 */
interface TurnOrderInterface
{
    /**
     * Calculate initial turn order when encounter starts.
     *
     * @param Actor[] $actors All actors in the encounter
     * @return string[] Array of actor IDs in turn order
     */
    public function calculateInitialOrder(array $actors): array;

    /**
     * Get the next actor ID in sequence.
     *
     * @param Actor[] $actors All actors in the encounter (keyed by ID)
     * @param ActorState[] $actorStates State for each actor (keyed by ID)
     * @param EncounterState $encounterState Current encounter state
     * @return string|null The next actor's ID, or null if no actors available
     */
    public function getNextActor(
        array $actors,
        array $actorStates,
        EncounterState $encounterState
    ): ?string;

    /**
     * Handle actor addition mid-encounter.
     *
     * @param Actor $actor The actor being added
     * @param Actor[] $allActors All actors in encounter (keyed by ID)
     * @param EncounterState $encounterState Current encounter state
     */
    public function addActor(Actor $actor, array $allActors, EncounterState $encounterState): void;

    /**
     * Handle actor removal mid-encounter.
     *
     * @param string $actorId ID of actor being removed
     * @param EncounterState $encounterState Current encounter state
     */
    public function removeActor(string $actorId, EncounterState $encounterState): void;

    /**
     * Handle initiative change mid-encounter.
     *
     * @param string $actorId ID of actor whose initiative is changing
     * @param int $newInitiative New initiative value
     * @param Actor[] $actors All actors in the encounter
     * @param ActorState[] $actorStates State for each actor (keyed by ID)
     * @param EncounterState $encounterState Current encounter state
     */
    public function changeInitiative(
        string $actorId,
        int $newInitiative,
        array $actors,
        array $actorStates,
        EncounterState $encounterState
    ): void;

    /**
     * Check if the round should advance.
     *
     * @param ActorState[] $actorStates State for each actor (keyed by ID)
     * @param EncounterState $encounterState Current encounter state
     * @return bool True if round should advance
     */
    public function shouldAdvanceRound(array $actorStates, EncounterState $encounterState): bool;

    /**
     * Check if the pass should advance (pass-based systems only).
     *
     * @param ActorState[] $actorStates State for each actor (keyed by ID)
     * @return bool True if pass should advance
     */
    public function shouldAdvancePass(array $actorStates): bool;
}
