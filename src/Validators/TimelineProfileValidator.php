<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Validators;

use Codryn\PHPTurnTracker\Exceptions\InvalidTimelineProfileException;
use Codryn\PHPTurnTracker\TimelineProfile;

/**
 * Validates TimelineProfile configuration.
 *
 * This validator wraps the TimelineProfile::validate() method
 * and can be extended with additional validation logic if needed.
 */
class TimelineProfileValidator
{
    /**
     * Validate a timeline profile configuration.
     *
     * @param TimelineProfile $profile The profile to validate
     * @throws InvalidTimelineProfileException If configuration is invalid
     */
    public static function validate(TimelineProfile $profile): void
    {
        $profile->validate();
    }

    /**
     * Check if a timeline profile is valid without throwing.
     *
     * @param TimelineProfile $profile The profile to check
     * @return bool True if valid, false otherwise
     */
    public static function isValid(TimelineProfile $profile): bool
    {
        try {
            $profile->validate();
            return true;
        } catch (InvalidTimelineProfileException) {
            return false;
        }
    }
}
