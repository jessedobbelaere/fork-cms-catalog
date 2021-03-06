<?php

namespace Backend\Modules\Catalog\Domain\Order;

use Symfony\Component\Validator\Constraints as Assert;

class ConfirmOrderDataTransferObject
{
    /**
     * @var string
     *
     * @Assert\NotBlank(message="err.FieldIsRequired")
     */
    public $accept_terms_and_conditions;
}
