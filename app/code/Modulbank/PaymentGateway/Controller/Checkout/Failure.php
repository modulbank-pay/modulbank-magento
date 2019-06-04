<?php

namespace Modulbank\PaymentGateway\Controller\Checkout;

class Failure extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Tinkoff\TinkoffPayment\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Payment\Helper\Data $paymentHelper
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        $message = __('Payment failed, order canceled.');

        $order = $this->_getCheckoutSession()->getLastRealOrder();

        if ($order->getId()) {
            $order->cancel();
            $order->addStatusHistoryComment($message);
            $order->save();
        }

        // Restore the quote
        $this->_getCheckoutSession()->restoreQuote();
        $this->messageManager->addError($message);
        $this->_redirect('checkout/cart');
    }

    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}