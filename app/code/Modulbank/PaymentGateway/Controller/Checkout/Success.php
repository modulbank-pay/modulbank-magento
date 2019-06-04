<?php

namespace Modulbank\PaymentGateway\Controller\Checkout;

class Success extends \Magento\Framework\App\Action\Action
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
     * @var Helper
     */
    protected $_helper;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->_orderSender = $orderSender;

        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->_getCheckoutSession()->getLastRealOrder();
        $realOrderId = $order->getRealOrderId();

        /** @var \Magento\Payment\Model\Method\AbstractMethod $method */
        $method = $order->getPayment()->getMethodInstance();

        $params = $this->getRequest()->getParams();

        try {
            $resultRedirect = $this->resultRedirectFactory->create();

            if(!isset($params['transaction_id']))
            {
                $this->messageManager->addNoticeMessage(__('Invalid return, no orderId specified.'));
                $this->_logger->critical('Invalid return, no orderId specified.', $params);
                $resultRedirect->setPath('checkout/cart');
                return $resultRedirect;
            } else {
                $transactionResult = $method->getTransactionStatus($params['transaction_id']);
                $paymentStatusText = "Ожидаем поступления средств";
                if (isset($transactionResult->status) && $transactionResult->status == "ok") {

                    switch ($transactionResult->transaction->state) {
                        case 'PROCESSING':$paymentStatusText = "В процессе";
                            break;
                        case 'WAITING_FOR_3DS':$paymentStatusText = "Ожидает 3DS";
                            break;
                        case 'FAILED':$paymentStatusText = "При оплате возникла ошибка";
                            break;
                        case 'COMPLETE':$paymentStatusText = "Оплата прошла успешно";
                            break;
                        default:$paymentStatusText = "Ожидаем поступления средств";
                    }
                }
                $paymentStatusText = "Статус оплаты: ".$paymentStatusText;
                $this->messageManager->addNoticeMessage($paymentStatusText);
                $order->addStatusHistoryComment($paymentStatusText)->save();

                $this->_orderSender->send($order);

                $this->_getCheckoutSession()->start();
                $resultRedirect->setPath('checkout/onepage/success');
                return $resultRedirect;
            }



       } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong, please try again later'));
            $this->_logger->critical($e);
            $this->_getCheckoutSession()->restoreQuote();
            $this->_redirect('checkout/cart');
        }
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