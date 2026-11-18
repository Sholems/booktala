<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A reservation status change that the booking rules do not allow.
 * The message is safe to show to the person who attempted it.
 */
class InvalidReservationTransition extends RuntimeException {}
