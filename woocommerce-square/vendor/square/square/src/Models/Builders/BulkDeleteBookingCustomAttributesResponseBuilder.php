<?php

declare(strict_types=1);

namespace Square\Models\Builders;

use Core\Utils\CoreHelper;
use Square\Models\BulkDeleteBookingCustomAttributesResponse;

/**
 * Builder for model BulkDeleteBookingCustomAttributesResponse
 *
 * @see BulkDeleteBookingCustomAttributesResponse
 */
class BulkDeleteBookingCustomAttributesResponseBuilder
{
    /**
     * @var BulkDeleteBookingCustomAttributesResponse
     */
    private $instance;

    private function __construct(BulkDeleteBookingCustomAttributesResponse $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Initializes a new bulk delete booking custom attributes response Builder object.
     */
    public static function init(): self
    {
        return new self(new BulkDeleteBookingCustomAttributesResponse());
    }

    /**
     * Sets values field.
     */
    public function values(?array $value): self
    {
        $this->instance->setValues($value);
        return $this;
    }

    /**
     * Sets errors field.
     */
    public function errors(?array $value): self
    {
        $this->instance->setErrors($value);
        return $this;
    }

    /**
     * Initializes a new bulk delete booking custom attributes response object.
     */
    public function build(): BulkDeleteBookingCustomAttributesResponse
    {
        return CoreHelper::clone($this->instance);
    }
}
