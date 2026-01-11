<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Tests\Fixtures;

use Codryn\PHPTurnTracker\TimelineProfile;
use Codryn\PHPTurnTracker\TurnOrderType;

/**
 * Predefined TimelineProfile configurations for common RPG systems.
 */
class TimelineProfiles
{
    /**
     * D&D 5e style: individual initiative, round-based, bounded 1-20.
     */
    public static function dnd5e(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 20,
            tieBreakerAttribute: 'dexterity'
        );
    }

    /**
     * Shadowrun 4e/5e style: pass-based with 4 passes, initiative decay enabled.
     */
    public static function shadowrun4(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::PASS,
            minInitiative: 1,
            maxInitiative: 50,
            passesPerRound: 4,
            decayEnabled: true,
            decayAmount: 10
        );
    }

    /**
     * Shadowrun 5e style: pass-based without decay.
     */
    public static function shadowrun5(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::PASS,
            minInitiative: 1,
            maxInitiative: 50,
            passesPerRound: 4,
            decayEnabled: false
        );
    }

    /**
     * OSR/B/X D&D style: side-based initiative.
     */
    public static function osr(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::ROUND_SIDE,
            minInitiative: 1,
            maxInitiative: 6
        );
    }

    /**
     * Genesys/FFG Star Wars style: slot-based initiative.
     */
    public static function genesys(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::SLOT,
            slotConfiguration: [
                'slots' => [5, 4, 3, 2, 1], // Initiative slots highest to lowest
            ]
        );
    }

    /**
     * Popcorn initiative style (Marvel Heroic, Cortex, etc.).
     */
    public static function popcorn(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::POPCORN,
            allowRepeatPopcorn: false
        );
    }

    /**
     * Popcorn initiative with repeat designation allowed.
     */
    public static function popcornWithRepeat(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::POPCORN,
            allowRepeatPopcorn: true
        );
    }

    /**
     * Unbounded initiative (no min/max limits).
     */
    public static function unbounded(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL
        );
    }

    /**
     * Simple round-based with tie-breaker callback.
     */
    public static function withTieBreakerCallback(): TimelineProfile
    {
        $tieBreaker = fn (array $a, array $b) => $b['dexterity'] <=> $a['dexterity'];

        return new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            tieBreakerCallback: $tieBreaker
        );
    }

    /**
     * Pathfinder 2e style: individual initiative, bounded 1-30.
     */
    public static function pathfinder2e(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 30,
            tieBreakerAttribute: 'dexterity'
        );
    }

    /**
     * 13th Age style: round-based individual, escalation tracking.
     */
    public static function thirteenthAge(): TimelineProfile
    {
        return new TimelineProfile(
            type: TurnOrderType::ROUND_INDIVIDUAL,
            minInitiative: 1,
            maxInitiative: 30
        );
    }
}
