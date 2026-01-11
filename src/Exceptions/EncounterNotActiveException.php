<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Exceptions;

/**
 * Thrown when attempting operations that require an active encounter.
 */
class EncounterNotActiveException extends PHPTurnTrackerException
{
}
