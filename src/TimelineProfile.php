<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker;

use Codryn\PHPTurnTracker\Exceptions\InvalidTimelineProfileException;

/**
 * Defines the turn order model and rules for an encounter.
 *
 * Configures which turn order system to use (round-individual, pass-based, etc.)
 * and system-specific parameters like initiative bounds, tie-breakers, decay, etc.
 */
class TimelineProfile
{
    /**
     * Create a new TimelineProfile.
     *
     * @param string $type Turn order type (TurnOrderType constant)
     * @param int|null $minInitiative Optional minimum initiative bound
     * @param int|null $maxInitiative Optional maximum initiative bound
     * @param string|null $tieBreakerAttribute Attribute name for tie-breaking (e.g., 'dexterity')
     * @param callable|null $tieBreakerCallback Custom tie-breaker function
     * @param int|null $passesPerRound Number of passes per round (pass-based only)
     * @param bool $decayEnabled Enable initiative decay per pass (pass-based only)
     * @param int $decayAmount Amount to decay per pass (pass-based only)
     * @param bool $allowRepeatPopcorn Allow re-designating actors who have already acted (popcorn only)
     * @param array<string, mixed>|null $slotConfiguration Slot configuration (slot-based only)
     */
    public function __construct(
        private string $type,
        private ?int $minInitiative = null,
        private ?int $maxInitiative = null,
        private ?string $tieBreakerAttribute = null,
        private $tieBreakerCallback = null,
        private ?int $passesPerRound = null,
        private bool $decayEnabled = false,
        private int $decayAmount = 10,
        private bool $allowRepeatPopcorn = false,
        private ?array $slotConfiguration = null
    ) {
    }

    /**
     * Get the turn order type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get minimum initiative bound.
     */
    public function getMinInitiative(): ?int
    {
        return $this->minInitiative;
    }

    /**
     * Get maximum initiative bound.
     */
    public function getMaxInitiative(): ?int
    {
        return $this->maxInitiative;
    }

    /**
     * Get tie-breaker attribute name.
     */
    public function getTieBreakerAttribute(): ?string
    {
        return $this->tieBreakerAttribute;
    }

    /**
     * Get tie-breaker callback.
     *
     * @return callable|null
     */
    public function getTieBreakerCallback(): mixed
    {
        return $this->tieBreakerCallback;
    }

    /**
     * Get passes per round (pass-based systems only).
     */
    public function getPassesPerRound(): ?int
    {
        return $this->passesPerRound;
    }

    /**
     * Check if initiative decay is enabled.
     */
    public function isDecayEnabled(): bool
    {
        return $this->decayEnabled;
    }

    /**
     * Get decay amount per pass.
     */
    public function getDecayAmount(): int
    {
        return $this->decayAmount;
    }

    /**
     * Check if repeat popcorn designation is allowed.
     */
    public function allowsRepeatPopcorn(): bool
    {
        return $this->allowRepeatPopcorn;
    }

    /**
     * Get slot configuration (slot-based systems only).
     *
     * @return array<string, mixed>|null
     */
    public function getSlotConfiguration(): ?array
    {
        return $this->slotConfiguration;
    }

    /**
     * Validate profile configuration.
     *
     * @throws InvalidTimelineProfileException If configuration is invalid
     */
    public function validate(): void
    {
        // Validate type
        if (!TurnOrderType::isValid($this->type)) {
            throw new InvalidTimelineProfileException(
                sprintf('Invalid turn order type: %s', $this->type)
            );
        }

        // Validate initiative bounds
        if ($this->minInitiative !== null && $this->maxInitiative !== null) {
            if ($this->minInitiative >= $this->maxInitiative) {
                throw new InvalidTimelineProfileException(
                    sprintf(
                        'minInitiative (%d) must be less than maxInitiative (%d)',
                        $this->minInitiative,
                        $this->maxInitiative
                    )
                );
            }
        }

        // Validate pass-based configuration
        if ($this->type === TurnOrderType::PASS) {
            if ($this->passesPerRound === null || $this->passesPerRound <= 0) {
                throw new InvalidTimelineProfileException(
                    'passesPerRound must be greater than 0 for pass-based systems'
                );
            }

            if ($this->decayEnabled && $this->decayAmount <= 0) {
                throw new InvalidTimelineProfileException(
                    'decayAmount must be greater than 0 when decay is enabled'
                );
            }
        }

        // Validate slot-based configuration
        if ($this->type === TurnOrderType::SLOT) {
            if ($this->slotConfiguration === null || empty($this->slotConfiguration)) {
                throw new InvalidTimelineProfileException(
                    'slotConfiguration must be provided for slot-based systems'
                );
            }
        }
    }
}
