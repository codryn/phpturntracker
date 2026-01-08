<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Exceptions;

/**
 * Thrown when attempting to start an already active encounter.
 */
class EncounterAlreadyActiveException extends PhpTurnTrackerException
{
}
