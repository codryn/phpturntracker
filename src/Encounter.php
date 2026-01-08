<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker;

use Codryn\PhpTurnTracker\Exceptions\ActorNotFoundException;
use Codryn\PhpTurnTracker\Exceptions\DuplicateActorException;
use Codryn\PhpTurnTracker\Exceptions\EncounterAlreadyActiveException;
use Codryn\PhpTurnTracker\Exceptions\EncounterNotActiveException;
use Codryn\PhpTurnTracker\Exceptions\NoActorsException;
use Codryn\PhpTurnTracker\State\ActorState;
use Codryn\PhpTurnTracker\State\EncounterState;
use Codryn\PhpTurnTracker\TurnOrder\TurnOrderInterface;
use Codryn\PhpTurnTracker\Validators\InitiativeValidator;

/**
 * Manages turn tracking for an encounter.
 *
 * Coordinates actors, state, and delegates turn order logic to a strategy.
 */
class Encounter
{
    /** @var array<string, Actor> Actors keyed by ID */
    private array $actors = [];

    /** @var array<string, ActorState> Actor states keyed by ID */
    private array $actorStates = [];

    private EncounterState $state;
    private TurnOrderInterface $turnOrder;

    /**
     * Create a new encounter.
     *
     * @param TimelineProfile $profile Configuration for turn order system
     * @param TurnOrderInterface|null $turnOrder Optional strategy (auto-created if null)
     */
    public function __construct(
        private TimelineProfile $profile,
        ?TurnOrderInterface $turnOrder = null
    ) {
        $this->profile->validate();
        $this->state = new EncounterState();

        // Auto-create strategy based on profile type
        $this->turnOrder = $turnOrder ?? $this->createTurnOrderStrategy();
    }

    /**
     * Start the encounter.
     *
     * @throws EncounterAlreadyActiveException If encounter already active
     * @throws NoActorsException If no actors added
     */
    public function start(): void
    {
        if ($this->state->isActive()) {
            throw new EncounterAlreadyActiveException('Encounter is already active');
        }

        if (empty($this->actors)) {
            throw new NoActorsException('Cannot start encounter with no actors');
        }

        // Calculate initial turn order
        $turnOrder = $this->turnOrder->calculateInitialOrder($this->actors);

        // Set state to active
        $this->state->setActive(true);
        $this->state->setCurrentRound(1);

        // Set first actor as current
        if (!empty($turnOrder)) {
            $this->state->setCurrentActorId($turnOrder[0]);
        }
    }

    /**
     * Add an actor to the encounter.
     *
     * @param Actor $actor The actor to add
     * @throws DuplicateActorException If actor ID already exists
     */
    public function addActor(Actor $actor): void
    {
        $actorId = $actor->getId();

        if (isset($this->actors[$actorId])) {
            throw new DuplicateActorException(
                sprintf('Actor with ID "%s" already exists', $actorId)
            );
        }

        // Validate initiative bounds
        InitiativeValidator::validate($actor->getInitiative(), $this->profile);

        // Add actor and create state
        $this->actors[$actorId] = $actor;
        $this->actorStates[$actorId] = new ActorState(
            $actorId,
            hasActed: false,
            currentInitiative: $actor->getInitiative()
        );

        // If encounter is active, notify turn order strategy
        if ($this->state->isActive()) {
            $this->turnOrder->addActor($actor, $this->state);
        }
    }

    /**
     * Remove an actor from the encounter.
     *
     * @param string $actorId The actor ID to remove
     * @throws ActorNotFoundException If actor not found
     */
    public function removeActor(string $actorId): void
    {
        if (!isset($this->actors[$actorId])) {
            throw new ActorNotFoundException(
                sprintf('Actor with ID "%s" not found', $actorId)
            );
        }

        // Check if removing current actor
        $wasCurrentActor = ($this->state->getCurrentActorId() === $actorId);

        // Remove from collections
        unset($this->actors[$actorId]);
        unset($this->actorStates[$actorId]);

        // Notify turn order strategy
        if ($this->state->isActive()) {
            $this->turnOrder->removeActor($actorId, $this->state);

            // If we removed current actor, advance to next
            if ($wasCurrentActor) {
                $this->advanceTurn();
            }
        }
    }

    /**
     * Get the current actor.
     *
     * @return Actor|null The current actor or null if no current actor
     */
    public function getCurrentActor(): ?Actor
    {
        $currentId = $this->state->getCurrentActorId();

        if ($currentId === null) {
            return null;
        }

        return $this->actors[$currentId] ?? null;
    }

    /**
     * Advance to the next turn.
     *
     * @throws EncounterNotActiveException If encounter not active
     * @throws NoActorsException If no actors in encounter
     */
    public function advanceTurn(): void
    {
        if (!$this->state->isActive()) {
            throw new EncounterNotActiveException('Encounter is not active');
        }

        if (empty($this->actors)) {
            throw new NoActorsException('Cannot advance turn with no actors');
        }

        // Mark current actor as having acted
        $currentId = $this->state->getCurrentActorId();
        if ($currentId !== null && isset($this->actorStates[$currentId])) {
            $this->actorStates[$currentId]->markActed();
        }

        // Check if round should advance
        if ($this->turnOrder->shouldAdvanceRound($this->actorStates)) {
            $this->advanceRound();
        }

        // Get next actor
        $nextId = $this->turnOrder->getNextActor(
            $this->actors,
            $this->actorStates,
            $this->state
        );

        $this->state->setCurrentActorId($nextId);
    }

    /**
     * Get the current round number.
     */
    public function getCurrentRound(): int
    {
        return $this->state->getCurrentRound();
    }

    /**
     * Check if encounter is active.
     */
    public function isActive(): bool
    {
        return $this->state->isActive();
    }

    /**
     * Get actors who have acted this round.
     *
     * @return Actor[]
     */
    public function getActedActors(): array
    {
        $acted = [];

        foreach ($this->actorStates as $actorId => $state) {
            if ($state->hasActed() && isset($this->actors[$actorId])) {
                $acted[] = $this->actors[$actorId];
            }
        }

        return $acted;
    }

    /**
     * Get actors who have not acted this round.
     *
     * @return Actor[]
     */
    public function getUnactedActors(): array
    {
        $unacted = [];

        foreach ($this->actorStates as $actorId => $state) {
            if (!$state->hasActed() && isset($this->actors[$actorId])) {
                $unacted[] = $this->actors[$actorId];
            }
        }

        return $unacted;
    }

    /**
     * Advance to the next round.
     */
    private function advanceRound(): void
    {
        $this->state->incrementRound();

        // Reset all actor acted status
        foreach ($this->actorStates as $state) {
            $state->resetActed();
        }
    }

    /**
     * Create turn order strategy based on profile type.
     */
    private function createTurnOrderStrategy(): TurnOrderInterface
    {
        // For now, only support RoundBasedIndividual
        // Other strategies will be added in later user stories
        return match ($this->profile->getType()) {
            TurnOrderType::ROUND_INDIVIDUAL => new TurnOrder\RoundBasedIndividual(),
            default => throw new \RuntimeException('Unsupported turn order type'),
        };
    }
}
