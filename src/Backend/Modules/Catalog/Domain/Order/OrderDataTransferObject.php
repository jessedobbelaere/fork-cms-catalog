<?php

namespace Backend\Modules\Catalog\Domain\Order;

use Backend\Modules\Catalog\Domain\Account\Account;
use Backend\Modules\Catalog\Domain\Cart\Cart;
use Backend\Modules\Catalog\Domain\OrderAddress\OrderAddress;
use Backend\Modules\Catalog\Domain\OrderProduct\OrderProduct;
use Backend\Modules\Catalog\Domain\OrderRule\OrderRule;
use Backend\Modules\Catalog\Domain\OrderVat\OrderVat;
use Doctrine\Common\Collections\ArrayCollection;

class OrderDataTransferObject
{
    /**
     * @var Order
     */
    protected $orderEntity;

    /**
     * @var int
     */
    public $id;

    /**
     * @var Account
     */
    public $account;

    /**
     * @var Cart
     */
    public $cart;

    /**
     * @var string
     */
    public $paymentMethod;

    /**
     * @var string
     */
    public $shipment_method;

    /**
     * @var float
     */
    public $shipment_price;

    /**
     * @var \DateTime
     */
    public $date;

    /**
     * @var string
     */
    public $comment;

    /**
     * @var float
     */
    public $sub_total;

    /**
     * @var float
     */
    public $total;

    /**
     * @var string
     */
    public $invoiceNumber;

    /**
     * @var \DateTime
     */
    public $invoiceDate;

    /**
     * @var OrderAddress
     */
    public $invoiceAddress;

    /**
     * @var OrderAddress
     */
    public $shipmentAddress;

    /**
     * @var ArrayCollection
     */
    public $rules;

    /**
     * @var ArrayCollection
     */
    public $products;

    /**
     * @var ArrayCollection
     */
    public $vats;

    /**
     * OrderDataTransferObject constructor.
     *
     * @param Order|null $order
     * @throws \Exception
     */
    public function __construct(Order $order = null)
    {
        $this->orderEntity = $order;
        $this->date = new \DateTime();
        $this->rules = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->vats = new ArrayCollection();

        if (!$this->hasExistingOrder()) {
            return;
        }

        $this->id = $order->getId();
        $this->account = $order->getAccount();
        $this->cart = $order->getCart();
        $this->paymentMethod = $order->getPaymentMethod();
        $this->shipment_method = $order->getShipmentMethod();
        $this->shipment_price = $order->getShipmentPrice();
        $this->date = $order->getDate();
        $this->comment = $order->getComment();
        $this->sub_total = $order->getSubTotal();
        $this->total = $order->getTotal();
        $this->invoiceNumber = $order->getInvoiceNumber();
        $this->invoiceDate = $order->getInvoiceDate();
        $this->invoiceAddress = $order->getInvoiceAddress();
        $this->shipmentAddress = $order->getShipmentAddress();
        $this->rules = $order->getRules();
        $this->products = $order->getProducts();
        $this->vats = $order->getVats();
    }

    public function setOrderEntity(Order $orderEntity): void
    {
        $this->orderEntity = $orderEntity;
    }

    /**
     * @param OrderRule $orderRule
     */
    public function addRule(OrderRule $orderRule): void
    {
        $this->rules->add($orderRule);
    }

    /**
     * @param OrderRule $orderRule
     */
    public function removeRule(OrderRule $orderRule): void
    {
        $this->rules->removeElement($orderRule);
    }

    /**
     * @param OrderProduct $product
     */
    public function addProduct(OrderProduct $product): void
    {
        $this->products->add($product);
    }

    /**
     * @param OrderVat $vat
     */
    public function addVat(OrderVat $vat): void
    {
        $this->vats->add($vat);
    }

    public function getOrderEntity(): Order
    {
        return $this->orderEntity;
    }

    public function hasExistingOrder(): bool
    {
        return $this->orderEntity instanceof Order;
    }
}
