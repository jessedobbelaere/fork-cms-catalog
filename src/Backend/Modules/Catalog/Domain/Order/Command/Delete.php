<?php

namespace Backend\Modules\Catalog\Domain\Order\Command;

use Backend\Modules\Catalog\Domain\Order\Order;

final class Delete
{
    /** @var Order */
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
