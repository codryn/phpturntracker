<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\TurnOrder;

use Codryn\PhpTurnTracker\Actor;
use Codryn\PhpTurnTracker\Exceptions\InvalidDesignationException;
use Codryn\PhpTurnTracker\State\ActorState;
use Codryn\PhpTurnTracker\State\EncounterState;

/**
 * Popcorn initiative turn order strategy (Marvel Heroic, Feng Shui, Cortex).
 *
 * Current actor designates who acts next from actors who haven't acted yet.
 * If allowRepeatPopcorn is enabled, can re-designate actors who already acted.
 */
class Popcorn implements TurnOrderInterface
{
    /** @var string[] */
    private array $turnOrder = [];

    private ?string $designatedNextActor = null;

    /**
     * @param bool $allowRepeatPopcorn Allow re-designating actors who have already acted
     */
    public function __construct(
        private bool $allowRepeatPopcorn = false
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function calculateInitialOrder(array $actors): array
    {
        // Sort by initiative descending to determine first actor
        usort($actors, fn ($a, $b) => $b->getInitiative() <=> $a->getInitiative());
        $this->turnOrder = array_map(fn ($a) => $a->getId(), $actors);
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
        // If someone was designated, return them
        if ($this->designatedNextActor !== null) {
            $designated = $this->designatedNextActor;
            $this->designatedNextActor = null;
            return $designated;
        }

        $currentActorId = $encounterState->getCurrentActorId();

        // If no current actor, return first in initiative order
        if ($currentActorId === null) {
            return $this->turnOrder[0] ?? null;
        }

        // Check if only one actor hasn't acted (auto-select them)
        $unactedActors = [];
        foreach ($actorStates as $actorId => $state) {
            if (!$state->hasActed() && $actorId !== $currentActorId) {
                $unactedActors[] = $actorId;
            }
        }

        // If only one unacted actor left, automatically select them
        if (count($unactedActors) === 1) {
            return $unactedActors[0];
        }

        // Otherwise wait for designation
        return null;
    }

    /**
     * Designate the next actor to act.
     *
     * @param string $actorId Actor to designate
     * @param array<string, ActorState> $actorStates Current actor states
     * @throws InvalidDesignationException If designation is invalid
     */
    public function designateNext(string $actorId, array $actorStates): void
    {
        $state = $actorStates[$actorId] ?? null;

        if ($state === null) {
            throw new InvalidDesignationException("Cannot designate nonexistent actor: {$actorId}");
        }

        // Check if actor has already acted
        if ($state->hasActed() && !$this->allowRepeatPopcorn) {
            throw new InvalidDesignationException(
                "Cannot designate actor who has already acted: {$actorId}. " .
                'Enable allowRepeatPopcorn to allow this.'
            );
        }

        $this->designatedNextActor = $actorId;
    }

    /**
     * Check if an actor can be designated.
     *
     * @param string $actorId Actor to check
     * @param array<string, ActorState> $actorStates Current actor states
     * @return bool
     */
    public function canDesignate(string $actorId, array $actorStates): bool
    {
        $state = $actorStates[$actorId] ?? null;

        if ($state === null) {
            return false;
        }

        // Can designate if actor hasn't acted, or if repeat designation is allowed
        return !$state->hasActed() || $this->allowRepeatPopcorn;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldAdvanceRound(array $actorStates, EncounterState $encounterState): bool
    {
        // Check if all actors have acted
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
        // Popcorn systems don't use passes
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function addActor(Actor $actor, array $allActors, EncounterState $encounterState): void
    {
        // Recalculate turn order to include new actor
        $this->calculateInitialOrder($allActors);
    }

    /**
     * {@inheritDoc}
     */
    public function removeActor(string $actorId, EncounterState $encounterState): void
    {
        // Remove from turn order
        $this->turnOrder = array_values(array_filter(
            $this->turnOrder,
            fn ($id) => $id !== $actorId
        ));

        // Clear designation if removed actor was designated
        if ($this->designatedNextActor === $actorId) {
            $this->designatedNextActor = null;
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
        // In popcorn initiative, initiative only determines first actor
        // Changes don't affect turn order mid-round
        // Recalculate for potential future rounds
        $this->calculateInitialOrder($actors);
    }
}
