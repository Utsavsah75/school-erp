<?php

namespace App\Core;

/** Thrown when a payment submit reuses an idempotency key — i.e. a double-click or back-button resubmit. */
class DuplicatePaymentException extends \RuntimeException
{
}
