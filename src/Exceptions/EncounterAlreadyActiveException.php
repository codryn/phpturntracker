<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Exceptions;

/**
 * Thrown when attempting to start an already active encounter.
 */
class EncounterAlreadyActiveException extends PHPTurnTrackerException
{
}
