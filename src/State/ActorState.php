<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\State;

/**
 * Tracks per-actor state within an encounter.
 *
 * Stores whether an actor has acted in the current round/pass,
 * how many passes they have remaining (for pass-based systems),
 * and their current initiative (which may decay in pass-based).
 */
class ActorState
{
    /**
     * Create a new ActorState.
     *
     * @param string $actorId The actor's unique identifier
     * @param bool $hasActed Whether actor has acted this round/pass
     * @param int $passesRemaining Passes remaining (pass-based only)
     * @param int $currentInitiative Current initiative (may differ from base if decay enabled)
     * @param int|null $addedInRound Round number when actor was added (null if present at start)
     */
    public function __construct(
        private string $actorId,
        private bool $hasActed = false,
        private int $passesRemaining = 0,
        private int $currentInitiative = 0,
        private ?int $addedInRound = null
    ) {
    }

    /**
     * Get the actor ID.
     */
    public function getActorId(): string
    {
        return $this->actorId;
    }

    /**
     * Check if actor has acted this round/pass.
     */
    public function hasActed(): bool
    {
        return $this->hasActed;
    }

    /**
     * Set whether actor has acted.
     */
    public function setHasActed(bool $hasActed): void
    {
        $this->hasActed = $hasActed;
    }

    /**
     * Mark actor as having acted.
     */
    public function markActed(): void
    {
        $this->hasActed = true;
    }

    /**
     * Reset acted status (typically at round start).
     */
    public function resetActed(): void
    {
        $this->hasActed = false;
    }

    /**
     * Get remaining passes (pass-based systems only).
     */
    public function getPassesRemaining(): int
    {
        return $this->passesRemaining;
    }

    /**
     * Set remaining passes.
     */
    public function setPassesRemaining(int $passes): void
    {
        $this->passesRemaining = $passes;
    }

    /**
     * Decrement remaining passes by one.
     */
    public function decrementPassesRemaining(): void
    {
        if ($this->passesRemaining > 0) {
            $this->passesRemaining--;
        }
    }

    /**
     * Get current initiative (may be decayed from base).
     */
    public function getCurrentInitiative(): int
    {
        return $this->currentInitiative;
    }

    /**
     * Set current initiative.
     */
    public function setCurrentInitiative(int $initiative): void
    {
        $this->currentInitiative = $initiative;
    }

    /**
     * Apply initiative decay (reduce by specified amount).
     */
    public function applyDecay(int $decayAmount): void
    {
        $this->currentInitiative -= $decayAmount;
    }

    /**
     * Get the round when actor was added.
     *
     * @return int|null Round number, or null if present at start
     */
    public function getAddedInRound(): ?int
    {
        return $this->addedInRound;
    }

    /**
     * Set the round when actor was added.
     */
    public function setAddedInRound(?int $round): void
    {
        $this->addedInRound = $round;
    }
}
