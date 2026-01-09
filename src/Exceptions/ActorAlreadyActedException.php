<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Exceptions;

/**
 * Thrown when attempting invalid operations on actors who have already acted.
 */
class ActorAlreadyActedException extends PhpTurnTrackerException
{
}
