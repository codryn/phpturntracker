<?php

declare(strict_types=1);

namespace Codryn\PHPTurnTracker\Exceptions;

use Exception;

/**
 * Base exception for all PHPTurnTracker library errors.
 *
 * All custom exceptions in this library extend this base class,
 * allowing consumers to catch all library-specific exceptions
 * with a single catch block if desired.
 */
class PHPTurnTrackerException extends Exception
{
}
