<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Validators;

use Codryn\PHPTurnTracker\Exceptions\InvalidInitiativeException;
use Codryn\PHPTurnTracker\TimelineProfile;

/**
 * Validates initiative values against TimelineProfile bounds.
 */
class InitiativeValidator
{
    /**
     * Validate an initiative value against profile bounds.
     *
     * @param int $initiative The initiative value to validate
     * @param TimelineProfile $profile The timeline profile with bounds configuration
     * @throws InvalidInitiativeException If initiative is outside configured bounds
     */
    public static function validate(int $initiative, TimelineProfile $profile): void
    {
        $min = $profile->getMinInitiative();
        $max = $profile->getMaxInitiative();

        if ($min !== null && $initiative < $min) {
            throw new InvalidInitiativeException(
                sprintf(
                    'Initiative %d is below minimum %d',
                    $initiative,
                    $min
                )
            );
        }

        if ($max !== null && $initiative > $max) {
            throw new InvalidInitiativeException(
                sprintf(
                    'Initiative %d exceeds maximum %d',
                    $initiative,
                    $max
                )
            );
        }
    }

    /**
     * Check if an initiative value is within bounds without throwing.
     *
     * @param int $initiative The initiative value to check
     * @param TimelineProfile $profile The timeline profile with bounds configuration
     * @return bool True if valid, false otherwise
     */
    public static function isValid(int $initiative, TimelineProfile $profile): bool
    {
        $min = $profile->getMinInitiative();
        $max = $profile->getMaxInitiative();

        if ($min !== null && $initiative < $min) {
            return false;
        }

        if ($max !== null && $initiative > $max) {
            return false;
        }

        return true;
    }
}
