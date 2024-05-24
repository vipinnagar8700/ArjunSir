<?php

declare(strict_types=1);

namespace Square\Models\Builders;

use Core\Utils\CoreHelper;
use Square\Models\ListInvoicesRequest;

/**
 * Builder for model ListInvoicesRequest
 *
 * @see ListInvoicesRequest
 */
class ListInvoicesRequestBuilder
{
    /**
     * @var ListInvoicesRequest
     */
    private $instance;

    private function __construct(ListInvoicesRequest $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Initializes a new list invoices request Builder object.
     */
    public static function init(string $locationId): self
    {
        return new self(new ListInvoicesRequest($locationId));
    }

    /**
     * Sets cursor field.
     */
    public function cursor(?string $value): self
    {
        $this->instance->setCursor($value);
        return $this;
    }

    /**
     * Unsets cursor field.
     */
    public function unsetCursor(): self
    {
        $this->instance->unsetCursor();
        return $this;
    }

    /**
     * Sets limit field.
     */
    public function limit(?int $value): self
    {
        $this->instance->setLimit($value);
        return $this;
    }

    /**
     * Unsets limit field.
     */
    public function unsetLimit(): self
    {
        $this->instance->unsetLimit();
        return $this;
    }

    /**
     * Initializes a new list invoices request object.
     */
    public function build(): ListInvoicesRequest
    {
        return CoreHelper::clone($this->instance);
    }
}
