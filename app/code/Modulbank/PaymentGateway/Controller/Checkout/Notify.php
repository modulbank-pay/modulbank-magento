<?php

namespace Modulbank\PaymentGateway\Controller\Checkout;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;


class Notify extends \Magento\Framework\App\Action\Action  implements CsrfAwareActionInterface
{
    /**
     * @var \Modulbank\PaymentGateway\Model\Config
     */
    protected $_config;


    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var TransactionBuilder
     */
    protected $_transactionBuilder;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;


    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Modulbank\PaymentGateway\Model\Modulbank $paymentConfig,
        \Magento\Sales\Model\Order\Payment\Transaction\Builder $trans,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
   )
    {
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->_config = $paymentConfig;
        $this->_transactionBuilder = $trans;
        $this->_order = $order;
        $this->_invoiceService = $invoiceService;
        $this->_invoiceSender = $invoiceSender;
        $this->_orderSender = $orderSender;

        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $this->_config->log($params, 'notify');
        if ($this->checkSign($params)) {
            $orderRef = $params['order_id'];
            $order = $this->_order->loadByIncrementId($orderRef);

            if (!$order->getId()){
                die("NOTOK"); }

            $order_id = $order->getIncrementId();

            $payment_id = $params['transaction_id'];
            $amount = $params['amount'];

            if ($params['state'] === 'COMPLETE' ) {
                $order->setState($order::STATE_PROCESSING)
                          ->setStatus($order->getConfig()->getStateDefaultStatus($order::STATE_PROCESSING))
                          ->save();

                // Send order notification
                try {
                    $this->_orderSender->send($order);
                } catch (\Exception $e) {
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                }

                //get payment object from order object
                $payment = $order->getPayment();
                $payment->setLastTransId($payment_id);
                $payment->setTransactionId($payment_id);

                $message = __('Captured amount is %1 rubles.', $amount);

                //get the object of builder class
                $trans = $this->_transactionBuilder;
                $transaction = $trans->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($payment_id)
                    ->setFailSafe(true)
                    //build method creates the transaction and returns the object
                    ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

                $payment->addTransactionCommentsToOrder(
                    $transaction,
                    $message
                );
                $payment->setParentTransactionId(null);
                $payment->save();
                $order->save();

                $trans_id = $transaction->save()->getTransactionId();

                $message = __('Payment has been captured.');

                // Create Invoice for Sale Transaction
                $invoice = $this->makeInvoice($order, [], false, $message);
                $invoice->setTransactionId($trans_id);
                $invoice->save();

                die("OK");
            } elseif ($params['state'] === 'FAILED') {
                $order->cancel();
                $order->addStatusHistoryComment(__('Order automatically canceled. Failed to complete payment.'));
                $order->save();

                die("OK");
            }
        }
    }


    private function checkSign($post)
    {
        $key       = $this->_config->getSecretKey();
        $signature = \ModulbankHelper::calcSignature($key, $post);
        return strcasecmp($signature, $post['signature']) === 0;
    }

    /**
     * Create Invoice
     * @param \Magento\Sales\Model\Order $order
     * @param array $qtys
     * @param bool $online
     * @param string $comment
     * @return \Magento\Sales\Model\Order\Invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeInvoice(\Magento\Sales\Model\Order $order, array $qtys = [], $online = false, $comment = '')
    {
        /** @var \Magento\Framework\ObjectManagerInterface $om */
        $om = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $this->_invoiceService->prepareInvoice($order, $qtys);
        $invoice->setRequestedCaptureCase($online ? \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE : \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);

        // Add Comment
        if (!empty($comment)) {
            $invoice->addComment(
                $comment,
                true,
                true
            );

            $invoice->setCustomerNote($comment);
            //$invoice->setCustomerNoteNotify(true);
        }

        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);

        // send invoice emails
        try {
            $this->_invoiceSender->send($invoice);
        } catch (\Exception $e) {
            $om->get('Psr\Log\LoggerInterface')->critical($e);
        }

        $invoice->setIsPaid(true);

        // Assign Last Transaction Id with Invoice
        $transactionId = $invoice->getOrder()->getPayment()->getLastTransId();
        if ($transactionId) {
            $invoice->setTransactionId($transactionId);
            $invoice->save();
        }

        return $invoice;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

}