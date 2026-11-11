<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a library rule blocks a loan, return, renewal or reservation.
 * The message is written for the person at the screen.
 */
class LoanException extends RuntimeException {}
