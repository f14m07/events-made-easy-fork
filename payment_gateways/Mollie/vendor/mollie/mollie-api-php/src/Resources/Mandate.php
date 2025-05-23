<?php

namespace Mollie\Api\Resources;

use Mollie\Api\Http\Requests\RevokeMandateRequest;
use Mollie\Api\Types\MandateStatus;

/**
 * @property \Mollie\Api\MollieApiClient $connector
 */
class Mandate extends BaseResource
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $mode;

    /**
     * @var string
     */
    public $method;

    /**
     * @var \stdClass|null
     */
    public $details;

    /**
     * @var string|null
     */
    public $customerId;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @var string
     */
    public $mandateReference;

    /**
     * Date of signature, for example: 2018-05-07
     *
     * @var string
     */
    public $signatureDate;

    /**
     * @var \stdClass
     */
    public $_links;

    public function isValid(): bool
    {
        return $this->status === MandateStatus::VALID;
    }

    public function isPending(): bool
    {
        return $this->status === MandateStatus::PENDING;
    }

    public function isInvalid(): bool
    {
        return $this->status === MandateStatus::INVALID;
    }

    /**
     * Revoke the mandate
     */
    public function revoke(): ?Mandate
    {
        if (! isset($this->customerId)) {
            return $this;
        }

        return $this
            ->connector
            ->send((new RevokeMandateRequest(
                $this->customerId,
                $this->id,
            ))->test($this->mode === 'test'))
            ->toResource();
    }
}
