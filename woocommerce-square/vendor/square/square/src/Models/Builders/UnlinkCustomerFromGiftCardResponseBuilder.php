<?php

declare(strict_types=1);

namespace Square\Models\Builders;

use Core\Utils\CoreHelper;
use Square\Models\GiftCard;
use Square\Models\UnlinkCustomerFromGiftCardResponse;

/**
 * Builder for model UnlinkCustomerFromGiftCardResponse
 *
 * @see UnlinkCustomerFromGiftCardResponse
 */
class UnlinkCustomerFromGiftCardResponseBuilder
{
    /**
     * @var UnlinkCustomerFromGiftCardResponse
     */
    private $instance;

    private function __construct(UnlinkCustomerFromGiftCardResponse $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Initializes a new unlink customer from gift card response Builder object.
     */
    public static function init(): self
    {
        return new self(new UnlinkCustomerFromGiftCardResponse());
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
     * Sets gift card field.
     */
    public function giftCard(?GiftCard $value): self
    {
        $this->instance->setGiftCard($value);
        return $this;
    }

    /**
     * Initializes a new unlink customer from gift card response object.
     */
    public function build(): UnlinkCustomerFromGiftCardResponse
    {
        return CoreHelper::clone($this->instance);
    }
}
