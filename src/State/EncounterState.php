<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\State;

/**
 * Tracks temporal state of an encounter.
 *
 * Manages active status, current round, pass (for pass-based systems),
 * and which actor is currently taking their turn.
 */
class EncounterState
{
    private bool $isActive = false;
    private int $currentRound = 0;
    private ?int $currentPass = null;
    private ?string $currentActorId = null;
    private ?string $previousActorId = null;

    /**
     * Check if encounter is active.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Set encounter active status.
     */
    public function setActive(bool $active): void
    {
        $this->isActive = $active;
    }

    /**
     * Get current round number (0 before start, 1+ during encounter).
     */
    public function getCurrentRound(): int
    {
        return $this->currentRound;
    }

    /**
     * Set current round number.
     */
    public function setCurrentRound(int $round): void
    {
        $this->currentRound = $round;
    }

    /**
     * Increment round by one.
     */
    public function incrementRound(): void
    {
        $this->currentRound++;
    }

    /**
     * Get current pass number (pass-based systems only, null otherwise).
     */
    public function getCurrentPass(): ?int
    {
        return $this->currentPass;
    }

    /**
     * Set current pass number.
     */
    public function setCurrentPass(?int $pass): void
    {
        $this->currentPass = $pass;
    }

    /**
     * Increment pass by one.
     */
    public function incrementPass(): void
    {
        if ($this->currentPass !== null) {
            $this->currentPass++;
        }
    }

    /**
     * Get ID of actor whose turn it currently is.
     */
    public function getCurrentActorId(): ?string
    {
        return $this->currentActorId;
    }

    /**
     * Set ID of current actor.
     */
    public function setCurrentActorId(?string $actorId): void
    {
        $this->currentActorId = $actorId;
    }

    /**
     * Get ID of previous actor.
     */
    public function getPreviousActorId(): ?string
    {
        return $this->previousActorId;
    }

    /**
     * Set ID of previous actor.
     */
    public function setPreviousActorId(?string $actorId): void
    {
        $this->previousActorId = $actorId;
    }
}
