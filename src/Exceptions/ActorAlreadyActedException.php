<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Exceptions;

/**
 * Thrown when attempting invalid operations on actors who have already acted.
 */
class ActorAlreadyActedException extends PHPTurnTrackerException
{
}
