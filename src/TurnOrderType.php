<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker;

/**
 * Enumeration of supported turn order system types.
 *
 * Each constant represents a different RPG combat system:
 * - ROUND_INDIVIDUAL: Individual initiative, round-based (e.g., D&D 5e)
 * - ROUND_SIDE: Side-based initiative, rounds (e.g., B/X D&D)
 * - PASS: Pass-based (actors have multiple passes per round, e.g., Shadowrun)
 * - SLOT: Slot-based (fixed slots filled by actors, e.g., Genesys)
 * - POPCORN: Popcorn initiative (actors designate next actor, e.g., Feng Shui)
 */
class TurnOrderType
{
    public const ROUND_INDIVIDUAL = 'round_individual';
    public const ROUND_SIDE = 'round_side';
    public const PASS = 'pass';
    public const SLOT = 'slot';
    public const POPCORN = 'popcorn';

    /**
     * Get all valid turn order type constants.
     *
     * @return string[]
     */
    public static function getAll(): array
    {
        return [
            self::ROUND_INDIVIDUAL,
            self::ROUND_SIDE,
            self::PASS,
            self::SLOT,
            self::POPCORN,
        ];
    }

    /**
     * Check if a given value is a valid turn order type.
     *
     * @param string $type The type to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::getAll(), true);
    }
}
