<?php

namespace Frontend\Modules\Catalog\Actions;

use Backend\Modules\Catalog\Domain\Account\Account;
use Backend\Modules\Catalog\Domain\Account\AccountRepository;
use Backend\Modules\Catalog\Domain\Order\Exception\OrderNotFound;
use Backend\Modules\Catalog\Domain\Order\Order;
use Backend\Modules\Catalog\Domain\Order\OrderRepository;
use Common\Exception\RedirectException;
use Frontend\Core\Engine\Base\Block as FrontendBaseBlock;
use Frontend\Core\Engine\Navigation;
use Frontend\Core\Language\Language;
use Frontend\Modules\Profiles\Engine\Authentication as FrontendProfilesAuthentication;
use Frontend\Modules\Profiles\Engine\Profile;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class CustomerOrders extends FrontendBaseBlock
{
    /**
     * @var Profile
     */
    private $profile;

    /**
     * @var Account
     */
    private $account;

    /**
     * Execute the action
     *
     * @throws RedirectException
     * @throws \Exception
     */
    public function execute(): void
    {
        if (!FrontendProfilesAuthentication::isLoggedIn()) {
            throw new InsufficientAuthenticationException('You need to log in to change your email');
        }

        parent::execute();

        $this->profile = FrontendProfilesAuthentication::getProfile();
        $this->account = $this->getAccountRepository()->findOneByProfile($this->profile);

        if ($this->getRequest()->query->has('order_id')) {
            try {
                // We need an deleted entity, there for disable the softdelete
                $em = $this->get('doctrine.orm.entity_manager');
                $em->getFilters()->disable('softdeleteable');

                $order = $this->getOrderRepository()->findByIdAndAccount(
                    $this->getRequest()->query->getInt('order_id'),
                    $this->account
                );

                $this->detail($order);
            } catch (OrderNotFound $e) {
                $this->redirect(Navigation::getUrlForBlock($this->getModule(), $this->getAction()));
            }
        } else {
            $this->overview();
        }
    }

    private function overview(): void
    {
        $this->loadTemplate();
        $this->template->assign('account', $this->account);
    }

    private function detail(Order $order): void
    {
        $this->loadTemplate('CustomerOrderDetail');

        $this->template->assign('account', $this->account);
        $this->template->assign('order', $order);

        $this->breadcrumb->addElement(ucfirst(Language::lbl('Order')) .' - '. $order->getId());
    }

    /**
     * @param string $path The path for the template to use.
     * @param bool $overwrite Should the template overwrite the default?
     */
    protected function loadTemplate(string $path = null, bool $overwrite = false): void
    {
        // no template given, so we should build the path
        if ($path === null) {
             $path = $this->getAction();
        }
        $path = $this->getModule() . '/Layout/Templates/Customer/' . $path  . '.html.twig';

        parent::loadTemplate($path, $overwrite);
    }

    private function getAccountRepository(): AccountRepository
    {
        return $this->get('catalog.repository.account');
    }

    private function getOrderRepository(): OrderRepository
    {
        return $this->get('catalog.repository.order');
    }
}
