<?php

declare(strict_types=1);

namespace Square\Models\Builders;

use Core\Utils\CoreHelper;
use Square\Models\PaymentBalanceActivityFreeProcessingDetail;

/**
 * Builder for model PaymentBalanceActivityFreeProcessingDetail
 *
 * @see PaymentBalanceActivityFreeProcessingDetail
 */
class PaymentBalanceActivityFreeProcessingDetailBuilder
{
    /**
     * @var PaymentBalanceActivityFreeProcessingDetail
     */
    private $instance;

    private function __construct(PaymentBalanceActivityFreeProcessingDetail $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Initializes a new payment balance activity free processing detail Builder object.
     */
    public static function init(): self
    {
        return new self(new PaymentBalanceActivityFreeProcessingDetail());
    }

    /**
     * Sets payment id field.
     */
    public function paymentId(?string $value): self
    {
        $this->instance->setPaymentId($value);
        return $this;
    }

    /**
     * Unsets payment id field.
     */
    public function unsetPaymentId(): self
    {
        $this->instance->unsetPaymentId();
        return $this;
    }

    /**
     * Initializes a new payment balance activity free processing detail object.
     */
    public function build(): PaymentBalanceActivityFreeProcessingDetail
    {
        return CoreHelper::clone($this->instance);
    }
}
