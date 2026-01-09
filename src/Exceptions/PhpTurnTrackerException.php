<?php

declare(strict_types=1);

namespace Codryn\PhpTurnTracker\Exceptions;

use Exception;

/**
 * Base exception for all PhpTurnTracker library errors.
 *
 * All custom exceptions in this library extend this base class,
 * allowing consumers to catch all library-specific exceptions
 * with a single catch block if desired.
 */
class PhpTurnTrackerException extends Exception
{
}
