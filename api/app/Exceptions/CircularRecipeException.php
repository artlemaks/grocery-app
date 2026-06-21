<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when sub-recipe links form a cycle (A includes B includes A), which would make
 * recursive expansion non-terminating. Surfaced to the API as a 422.
 */
class CircularRecipeException extends RuntimeException
{
}
