<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker;

use Codryn\PhpTurnTracker\Exceptions\ActorAlreadyActedException;
use Codryn\PhpTurnTracker\Exceptions\ActorNotFoundException;
use Codryn\PhpTurnTracker\Exceptions\DuplicateActorException;
use Codryn\PhpTurnTracker\Exceptions\EncounterAlreadyActiveException;
use Codryn\PhpTurnTracker\Exceptions\EncounterNotActiveException;
use Codryn\PhpTurnTracker\Exceptions\InvalidDelayException;
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

        // Initialize pass for pass-based systems
        if ($this->profile->getType() === TurnOrderType::PASS) {
            $this->state->setCurrentPass(1);

            // Calculate passes for each actor
            if ($this->turnOrder instanceof TurnOrder\PassBased) {
                foreach ($this->actorStates as $state) {
                    $actorId = $state->getActorId();
                    $initiative = $this->actors[$actorId]->getInitiative();
                    $passes = $this->turnOrder->calculatePasses($initiative);
                    $state->setPassesRemaining($passes);
                }
            }
        }

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

        // Determine if adding mid-encounter (to track for round rules)
        $addedInRound = $this->state->isActive() ? $this->state->getCurrentRound() : null;

        // Add actor and create state
        $this->actors[$actorId] = $actor;

        // Calculate passes for pass-based systems
        $passesRemaining = 0;
        if ($this->profile->getType() === TurnOrderType::PASS && $this->turnOrder instanceof TurnOrder\PassBased) {
            $passesRemaining = $this->turnOrder->calculatePasses($actor->getInitiative());
        }

        $this->actorStates[$actorId] = new ActorState(
            $actorId,
            hasActed: false,
            passesRemaining: $passesRemaining,
            currentInitiative: $actor->getInitiative(),
            addedInRound: $addedInRound
        );

        // If encounter is active, notify turn order strategy
        if ($this->state->isActive()) {
            $this->turnOrder->addActor($actor, $this->actors, $this->state);
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

        // If removing current actor, get next BEFORE removal
        $nextId = null;
        if ($wasCurrentActor && $this->state->isActive()) {
            $nextId = $this->turnOrder->getNextActor(
                $this->actors,
                $this->actorStates,
                $this->state
            );
        }

        // Remove from collections
        unset($this->actors[$actorId]);
        unset($this->actorStates[$actorId]);

        // Notify turn order strategy
        if ($this->state->isActive()) {
            $this->turnOrder->removeActor($actorId, $this->state);

            // If we removed current actor, set next WITHOUT marking as acted
            if ($wasCurrentActor && $nextId !== null) {
                $this->state->setCurrentActorId($nextId);
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
        if ($this->turnOrder->shouldAdvanceRound($this->actorStates, $this->state)) {
            $this->advanceRound();
        } elseif ($this->profile->getType() === TurnOrderType::ROUND_SIDE) {
            // For side-based, check if current side has completed
            if ($this->turnOrder instanceof TurnOrder\RoundBasedSide) {
                if ($this->turnOrder->allSideActorsHaveActed($this->actorStates)) {
                    $this->turnOrder->advanceToNextSide();
                }
            }
        } elseif ($this->profile->getType() === TurnOrderType::PASS && $this->shouldAdvancePass()) {
            // Check if pass should advance (pass-based only)
            $this->advancePass();
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
     * Change an actor's initiative mid-encounter.
     *
     * Preserves acted/unacted status. If initiative increases past current position,
     * actor is marked as acted (skipped this round).
     *
     * @param string $actorId Actor whose initiative to change
     * @param int $newInitiative New initiative score
     * @throws ActorNotFoundException If actor not found
     * @throws EncounterNotActiveException If encounter not active
     */
    public function changeInitiative(string $actorId, int $newInitiative): void
    {
        if (!$this->state->isActive()) {
            throw new EncounterNotActiveException('Cannot change initiative: encounter not active');
        }

        if (!isset($this->actors[$actorId])) {
            throw new ActorNotFoundException(
                sprintf('Actor with ID "%s" not found', $actorId)
            );
        }

        // Validate new initiative
        InitiativeValidator::validate($newInitiative, $this->profile);

        // Get current actor initiative before change
        $oldInitiative = $this->actors[$actorId]->getInitiative();
        $currentActorId = $this->state->getCurrentActorId();
        $currentActorInitiative = $currentActorId !== null && isset($this->actors[$currentActorId])
            ? $this->actors[$currentActorId]->getInitiative()
            : null;

        // Update actor's initiative
        $this->actors[$actorId] = new Actor(
            $this->actors[$actorId]->getId(),
            $this->actors[$actorId]->getName(),
            $newInitiative,
            $this->actors[$actorId]->getAttributes()
        );

        // Update actor state
        $this->actorStates[$actorId]->setCurrentInitiative($newInitiative);

        // If increasing initiative past current position, mark as acted (skip this round)
        if ($currentActorInitiative !== null &&
            $newInitiative > $currentActorInitiative &&
            $newInitiative > $oldInitiative &&
            !$this->actorStates[$actorId]->hasActed()) {
            $this->actorStates[$actorId]->markActed();
        }

        // Notify turn order strategy to recalculate
        $this->turnOrder->changeInitiative(
            $actorId,
            $newInitiative,
            $this->actors,
            $this->state
        );
    }

    /**
     * Delay an actor's action to a lower initiative.
     *
     * Actor must not have acted yet, and new initiative must be lower than current.
     * Turn advances to next actor immediately. This is a TEMPORARY change for the
     * current round only - the actor's actual initiative is unchanged and will be
     * restored on the next round. For permanent changes, use changeInitiative().
     *
     * @param string $actorId Actor to delay
     * @param int $newInitiative New (lower) initiative score
     * @throws ActorNotFoundException If actor not found
     * @throws ActorAlreadyActedException If actor has already acted
     * @throws InvalidDelayException If new initiative not lower or out of bounds
     * @throws EncounterNotActiveException If encounter not active
     */
    public function delayActor(string $actorId, int $newInitiative): void
    {
        if (!$this->state->isActive()) {
            throw new EncounterNotActiveException('Cannot delay actor: encounter not active');
        }

        if (!isset($this->actors[$actorId])) {
            throw new ActorNotFoundException(
                sprintf('Actor with ID "%s" not found', $actorId)
            );
        }

        // Check if actor has already acted
        if ($this->actorStates[$actorId]->hasActed()) {
            throw new ActorAlreadyActedException(
                sprintf('Actor "%s" has already acted this round and cannot delay', $actorId)
            );
        }

        // Validate new initiative is lower than current (use currentInitiative for temporary changes)
        $currentInitiative = $this->actorStates[$actorId]->getCurrentInitiative();
        if ($newInitiative >= $currentInitiative) {
            throw new InvalidDelayException(
                sprintf(
                    'Cannot delay: new initiative %d must be lower than current %d',
                    $newInitiative,
                    $currentInitiative
                )
            );
        }

        // Validate new initiative is within bounds
        InitiativeValidator::validate($newInitiative, $this->profile);

        // Check if delaying current actor
        $isCurrentActor = ($this->state->getCurrentActorId() === $actorId);

        // If delaying current actor, get next actor BEFORE changing initiative
        $nextId = null;
        if ($isCurrentActor) {
            $nextId = $this->turnOrder->getNextActor(
                $this->actors,
                $this->actorStates,
                $this->state
            );
        }

        // Temporarily update actor state's current initiative (NOT the actor's base initiative)
        $this->actorStates[$actorId]->setCurrentInitiative($newInitiative);

        // Notify turn order strategy to recalculate turn order based on temporary change
        $this->turnOrder->changeInitiative(
            $actorId,
            $newInitiative,
            $this->actors,
            $this->state
        );

        // If delaying current actor, set next actor as current
        if ($isCurrentActor && $nextId !== null) {
            $this->state->setCurrentActorId($nextId);
        }
    }
    /**
     * Get the current pass number (pass-based systems only).
     *
     * @return int|null Pass number, or null if not pass-based
     */
    public function getCurrentPass(): ?int
    {
        return $this->state->getCurrentPass();
    }

    /**
     * Get actor state for a specific actor.
     *
     * @param string $actorId Actor ID
     * @return ActorState|null Actor state, or null if not found
     */
    public function getActorState(string $actorId): ?ActorState
    {
        return $this->actorStates[$actorId] ?? null;
    }

    /**
     * Get the current slot (slot-based systems only).
     *
     * @return array{type: string, initiative: int, index: int}|null Slot info, or null if not slot-based
     */
    public function getCurrentSlot(): ?array
    {
        if (!($this->turnOrder instanceof TurnOrder\SlotBased)) {
            return null;
        }

        return $this->turnOrder->getCurrentSlot();
    }

    /**
     * Fill the current slot with an actor (slot-based systems only).
     *
     * @param string $actorId Actor to fill the slot
     * @throws \RuntimeException If not slot-based or slot cannot be filled
     */
    public function fillSlot(string $actorId): void
    {
        if (!$this->state->isActive()) {
            throw new Exceptions\EncounterNotActiveException('Cannot fill slot: encounter not active');
        }

        if (!($this->turnOrder instanceof TurnOrder\SlotBased)) {
            throw new \RuntimeException('fillSlot() only available for slot-based systems');
        }

        if (!isset($this->actors[$actorId])) {
            throw new Exceptions\ActorNotFoundException("Actor not found: {$actorId}");
        }

        // Fill the slot
        $success = $this->turnOrder->fillSlot($actorId, $this->actors);

        if (!$success) {
            throw new \RuntimeException("Cannot fill slot with actor: {$actorId}");
        }

        // Mark actor as acted
        $this->actorStates[$actorId]->markActed();

        // Set as current actor
        $this->state->setCurrentActorId($actorId);

        // Check if round should advance
        if ($this->turnOrder->shouldAdvanceRound($this->actorStates, $this->state)) {
            $this->advanceRound();
        }
    }

    /**
     * Designate the next actor to act (popcorn systems only).
     *
     * @param string $actorId Actor to designate
     * @throws Exceptions\InvalidDesignationException If designation is invalid
     */
    public function designateNext(string $actorId): void
    {
        if (!$this->state->isActive()) {
            throw new Exceptions\EncounterNotActiveException('Cannot designate next: encounter not active');
        }

        if (!($this->turnOrder instanceof TurnOrder\Popcorn)) {
            throw new \RuntimeException('designateNext() only available for popcorn systems');
        }

        if (!isset($this->actors[$actorId])) {
            throw new Exceptions\ActorNotFoundException("Actor not found: {$actorId}");
        }

        try {
            $this->turnOrder->designateNext($actorId, $this->actorStates);
        } catch (\InvalidArgumentException $e) {
            throw new Exceptions\InvalidDesignationException($e->getMessage(), 0, $e);
        }

        // Mark current actor as acted
        $currentActorId = $this->state->getCurrentActorId();
        if ($currentActorId !== null) {
            $this->actorStates[$currentActorId]->markActed();
        }

        // Set designated actor as current
        $this->state->setCurrentActorId($actorId);

        // Check if round should advance after this action
        // (will happen when designateNext is called again or advanceTurn is called)
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
        $currentPass = $this->profile->getType() === TurnOrderType::PASS
            ? ($this->state->getCurrentPass() ?? 1)
            : null;

        // For side-based systems, only return actors from current side
        $currentSide = null;
        if ($this->profile->getType() === TurnOrderType::ROUND_SIDE &&
            $this->turnOrder instanceof TurnOrder\RoundBasedSide) {
            $currentSide = $this->turnOrder->getCurrentSide();
        }

        foreach ($this->actorStates as $actorId => $state) {
            // Check if actor hasn't acted
            if ($state->hasActed() || !isset($this->actors[$actorId])) {
                continue;
            }

            // For pass-based systems, also check pass eligibility
            if ($currentPass !== null && !$this->isEligibleForPass($state, $currentPass)) {
                continue;
            }

            // For side-based systems, only include actors from current side
            if ($currentSide !== null) {
                $actorSide = $this->actors[$actorId]->getAttributes()['side'] ?? 'default';
                if ($actorSide !== $currentSide) {
                    continue;
                }
            }

            $unacted[] = $this->actors[$actorId];
        }

        return $unacted;
    }

    /**
     * Advance to the next round.
     */
    /**
     * Check if pass should advance (pass-based systems only).
     */
    private function shouldAdvancePass(): bool
    {
        $currentPass = $this->state->getCurrentPass() ?? 1;

        // Check if all eligible actors for current pass have acted
        foreach ($this->actorStates as $state) {
            if (!$state->hasActed() && $this->isEligibleForPass($state, $currentPass)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if actor is eligible for the given pass.
     *
     * Actor can act in pass N if their total passes >= N
     */
    private function isEligibleForPass(ActorState $state, int $pass): bool
    {
        return $state->getPassesRemaining() >= $pass;
    }

    /**
     * Advance to next pass (pass-based systems only).
     */
    private function advancePass(): void
    {
        // Increment pass
        $this->state->incrementPass();

        // Reset acted status for actors who can act in the new pass
        foreach ($this->actorStates as $state) {
            $state->resetActed();
        }

        // Apply initiative decay if enabled
        if ($this->profile->isDecayEnabled() && $this->turnOrder instanceof TurnOrder\PassBased) {
            foreach ($this->actorStates as $state) {
                $this->turnOrder->applyDecay($state);
            }
        }
    }

    private function advanceRound(): void
    {
        $this->state->incrementRound();

        // Reset all actor acted status and restore original initiative (clearing temporary delays)
        foreach ($this->actorStates as $state) {
            $state->resetActed();
            
            // Restore original initiative from Actor (clears temporary delays)
            $actorId = $state->getActorId();
            if (isset($this->actors[$actorId])) {
                $originalInitiative = $this->actors[$actorId]->getInitiative();
                $state->setCurrentInitiative($originalInitiative);
            }
        }

        // For pass-based systems, reset to pass 1 and recalculate passes
        if ($this->profile->getType() === TurnOrderType::PASS) {
            $this->state->setCurrentPass(1);

            // Recalculate passes for each actor based on (possibly decayed) initiative
            if ($this->turnOrder instanceof TurnOrder\PassBased) {
                foreach ($this->actorStates as $state) {
                    $actorId = $state->getActorId();
                    // Initiative already restored above
                    $originalInitiative = $this->actors[$actorId]->getInitiative();
                    $passes = $this->turnOrder->calculatePasses($originalInitiative);
                    $state->setPassesRemaining($passes);
                }
            }
        }

        // For side-based systems, reset to first side
        if ($this->profile->getType() === TurnOrderType::ROUND_SIDE) {
            if ($this->turnOrder instanceof TurnOrder\RoundBasedSide) {
                $this->turnOrder->resetForNewRound();
            }
        }

        // For slot-based systems, reset slots
        if ($this->profile->getType() === TurnOrderType::SLOT) {
            if ($this->turnOrder instanceof TurnOrder\SlotBased) {
                $this->turnOrder->resetRound();
            }
        }
    }

    /**
     * Create turn order strategy based on profile type.
     */
    private function createTurnOrderStrategy(): TurnOrderInterface
    {
        return match ($this->profile->getType()) {
            TurnOrderType::ROUND_INDIVIDUAL => new TurnOrder\RoundBasedIndividual(),
            TurnOrderType::ROUND_SIDE => new TurnOrder\RoundBasedSide(),
            TurnOrderType::PASS => new TurnOrder\PassBased(
                $this->profile->getPassesPerRound() ?? 4,
                $this->profile->isDecayEnabled(),
                $this->profile->getDecayAmount()
            ),
            TurnOrderType::SLOT => new TurnOrder\SlotBased(
                $this->profile->getSlotConfiguration() ?? []
            ),
            TurnOrderType::POPCORN => new TurnOrder\Popcorn(
                $this->profile->allowsRepeatPopcorn()
            ),
            default => throw new \RuntimeException('Unsupported turn order type'),
        };
    }
}
