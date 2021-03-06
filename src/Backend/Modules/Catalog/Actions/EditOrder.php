<?php

namespace Backend\Modules\Catalog\Actions;

use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Modules\Catalog\Domain\Order\DataGridOrderHistory;
use Backend\Modules\Catalog\Domain\Order\DataGridProducts;
use Backend\Modules\Catalog\Domain\Order\Event\OrderUpdated;
use Backend\Modules\Catalog\Domain\Order\Exception\OrderNotFound;
use Backend\Modules\Catalog\Domain\Order\Order;
use Backend\Modules\Catalog\Domain\Order\OrderRepository;
use Backend\Modules\Catalog\Domain\Order\OrderType;
use Backend\Modules\Catalog\Domain\OrderHistory\Command\CreateOrderHistory;
use Backend\Modules\Catalog\Domain\OrderHistory\OrderHistoryDataTransferObject;
use Backend\Modules\Catalog\Domain\OrderHistory\OrderHistoryType;
use Common\Exception\RedirectException;
use Symfony\Component\Form\Form;

/**
 * This is the edit-action, it will display a form to edit an existing item
 *
 * @author Tim van Wolfswinkel <tim@webleads.nl>
 * @author Jacob van Dam <j.vandam@jvdict.nl>
 */
class EditOrder extends BackendBaseActionEdit
{
    /**
     * @var Order
     */
    private $order;

    /**
     * Execute the action
     *
     * @throws RedirectException
     * @throws \Exception
     */
    public function execute(): void
    {
        parent::execute();

        $this->order = $this->getOrder();

        $form = $this->getForm();

        if ( ! $form->isSubmitted() || ! $form->isValid()) {
            $this->template->assign('form', $form->createView());
            $this->template->assign('order', $this->order);
            $this->template->assign('dataGridOrderProducts', DataGridProducts::getHtml($this->order));
            $this->template->assign('dataGridOrderHistory', DataGridOrderHistory::getHtml($this->order));

            $this->header->addCSS('EditOrder.css');

            $this->parse();
            $this->display();

            return;
        }

        /** @var CreateOrderHistory $createOrderHistory */
        $createOrderHistory = $this->createOrderHistory($form);

        $this->get('event_dispatcher')->dispatch(
            OrderUpdated::EVENT_NAME,
            new OrderUpdated($this->order, $createOrderHistory->getOrderHistoryEntity())
        );

        $this->redirect(
            $this->getBackLink(
                [
                    'report'    => 'edited',
                    'highlight' => 'row-' . $this->order->getId(),
                ]
            )
        );
    }

    private function createOrderHistory(Form $form): CreateOrderHistory
    {
        /** @var CreateOrderHistory $createOrderHistory */
        $createOrderHistory = $form->getData();
        $createOrderHistory->order = $this->order;

        // The command bus will handle the saving of the product in the database.
        $this->get('command_bus')->handle($createOrderHistory);

        return $createOrderHistory;
    }

    private function getForm(): Form
    {
        $form = $this->createForm(
            OrderType::class,
            new CreateOrderHistory()
        );

        $form->handleRequest($this->getRequest());

        return $form;
    }

    /**
     * @return Order
     * @throws \Common\Exception\RedirectException
     * @throws \Exception
     */
    private function getOrder(): Order
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->get('catalog.repository.order');

        try {
            return $orderRepository->findOneById($this->getRequest()->query->getInt('id'));
        } catch (OrderNotFound $e) {
            $this->redirect($this->getBackLink(['error' => 'non-existing']));
        }
    }

    /**
     * @param array $parameters
     *
     * @return string
     * @throws \Exception
     */
    private function getBackLink(array $parameters = []): string
    {
        return BackendModel::createUrlForAction(
            'Orders',
            null,
            null,
            $parameters
        );
    }
}
