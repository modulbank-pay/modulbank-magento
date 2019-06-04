<?php

namespace Modulbank\PaymentGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;

/**
 * Class Modulbank
 * @package Modulbank\PaymentGateway\Model
 */
class Modulbank extends AbstractMethod
{
	const VERSION = '1.0.0';
	/**
	 * @var bool
	 */
	protected $_isGateway = true;

	/**
	 * @var bool
	 */
	protected $_isInitializeNeeded = true;

	/**
	 * Payment code
	 *
	 * @var string
	 */
	protected $_code = 'modulbank';

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_isOffline = false;

	/**
	 * Payment additional info block
	 *
	 * @var string
	 */
	protected $_formBlockType = 'Modulbank\PaymentGateway\Block\Form\Modulbank';

	/**
	 * Sidebar payment info block
	 *
	 * @var string
	 */
	protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

	protected $_gateUrl = "";

	protected $_test;

	protected $_dir;

	protected $orderFactory;

	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
		\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
		\Magento\Payment\Helper\Data $paymentData,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Payment\Model\Method\Logger $logger,
		\Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		\Magento\Framework\Filesystem\DirectoryList $dir,
		\Magento\Framework\UrlInterface $urlBuilder,
		array $data = []) {
		$this->orderFactory = $orderFactory;
		parent::__construct($context,
			$registry,
			$extensionFactory,
			$customAttributeFactory,
			$paymentData,
			$scopeConfig,
			$logger,
			$resource,
			$resourceCollection,
			$data);

		$this->_gateUrl   = 'https://pay.modulbank.ru/pay';
		$this->_dir       = $dir;
		$this->urlBuilder = $urlBuilder;
	}

	/**
	 * Получить объект Order по его orderId
	 *
	 * @param $orderId
	 * @return Order
	 */
	protected function getOrder($orderId)
	{
		return $this->orderFactory->create()->loadByIncrementId($orderId);
	}

	public function log($data, $category)
	{
		if ($this->getConfigData('logging')) {
			$logName = $this->_dir->getRoot() . '/var/log/modulbank.log';
			\ModulbankHelper::log($logName, $data, $category, $this->getConfigData('max_log_size'));
		}
	}

	/**
	 * Получить сумму платежа по orderId заказа
	 *
	 * @param $orderId
	 * @return float
	 */
	public function getAmount($orderId)
	{
		return $this->getOrder($orderId)->getGrandTotal();
	}

	/**
	 * Получить email клиента по orderId заказа
	 *
	 * @param $orderId
	 * @return int|null
	 */
	public function getEmail($orderId)
	{
		return $this->getOrder($orderId)->getShippingAddress()->getEmail();
	}

	/**
	 * Получить email клиента по orderId заказа
	 *
	 * @param $orderId
	 * @return int|null
	 */
	public function getCustomerName($orderId)
	{
		return $this->getOrder($orderId)->getShippingAddress()->getFirstname() . ' '
		. $this->getOrder($orderId)->getShippingAddress()->getLastname();
	}

	/**
	 * Получить код используемой валюты по orderId заказа
	 *
	 * @param $orderId
	 * @return null|string
	 */
	public function getCurrencyCode($orderId)
	{
		return $this->getOrder($orderId)->getBaseCurrencyCode();
	}

	/**
	 * Set order state and status
	 * (Этот метод вызывается при нажатии на кнопку "Place Order")
	 *
	 * @param string $paymentAction
	 * @param \Magento\Framework\DataObject $stateObject
	 * @return void
	 */
	public function initialize($paymentAction, $stateObject)
	{
		$stateObject->setState(Order::STATE_PENDING_PAYMENT);
		$stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
		$stateObject->setIsNotified(false);
	}

	/**
	 * Check whether payment method can be used
	 * (Проверка на доступность метода оплаты)
	 *
	 * @param CartInterface|null $quote
	 * @return bool
	 */
	public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
	{
		if ($quote === null) {
			return false;
		}
		return parent::isAvailable($quote);
	}

	/**
	 * Получить адрес платежного шлюза
	 *
	 * @return string
	 */
	public function getGateUrl()
	{
		return $this->_gateUrl;
	}

	/**
	 * Получить код проверки целостности данных из конфигурации
	 *
	 * @return mixed
	 */
	public function getSecretKey()
	{
		return intval($this->getConfigData('test_mode')) ? $this->getConfigData('test_secret_key') : $this->getConfigData('secret_key');
	}

	protected function cleanProductName($value)
	{
		$result = preg_replace('/[^0-9a-zA-Zа-яА-Я ]/ui', '', htmlspecialchars_decode($value));
		$result = trim(mb_substr($result, 0, 128));
		return $result;
	}

	/**
	 * Получить массив параметров для формы оплаты
	 *
	 * @param $orderId
	 * @return array
	 */
	public function getPostData($orderId)
	{
		$sysinfo = [
			'language' => 'PHP ' . phpversion(),
			'plugin'   => self::VERSION,
			'cms'      => $this->getCmsVersion(),
		];

		$amount     = number_format($this->getAmount($orderId), 2, '.', '');
		$receipt    = new \ModulbankReceipt($this->getConfigData("sno"), $this->getConfigData("payment_method"), $amount);
		$order      = $this->getOrder($orderId);
		$orderItems = $order->getAllItems();
		/** @var Order/Item $orderItem */
		foreach ($orderItems as $orderItem) {
			if ($orderItem->getQtyToInvoice() > 0) {
				$name  = $this->cleanProductName($orderItem->getName());
				$price = $orderItem->getProduct()->getFinalPrice();
				$receipt->addItem($name, $price, $this->getConfigData("vat"), $this->getConfigData("payment_object"), $orderItem->getQtyToInvoice());
			}
		}

		$shippingAmount = $order->getShippingAmount();
		if ($shippingAmount > 0) {
			$name = 'Доставка';
			$receipt->addItem($name, $shippingAmount, $this->getConfigData("delivery_vat"), $this->getConfigData("delivery_payment_object"));
		}

		$notifyUrl = version_compare($sysinfo['cms'], '2.3.0', '>=') ? "modulbank/checkout/notify" : "modulbank/checkout/notify2";

		$successUrl = $this->getConfigData("success_url");
		$failUrl = $this->getConfigData("fail_url");
		$cancelUrl = $this->getConfigData("cancel_url");

		$postData = [
			'merchant'        => $this->getConfigData("merchant"),
			'amount'          => $amount,
			'order_id'        => $orderId,
			'testing'         => intval($this->getConfigData('test_mode')),
			'description'     => "Оплата закза №{$orderId}",
			'success_url'     => $this->urlBuilder->getUrl($successUrl),
			'fail_url'        => $this->urlBuilder->getUrl($failUrl),
			'cancel_url'      => $this->urlBuilder->getUrl($cancelUrl),
			'callback_url'    => $this->urlBuilder->getUrl($notifyUrl),
			'client_name'     => $this->getCustomerName($orderId),
			'client_email'    => $this->getEmail($orderId),
			'receipt_contact' => $this->getEmail($orderId),
			'receipt_items'   => $receipt->getJson(),
			'unix_timestamp'  => time(),
			'sysinfo'         => json_encode($sysinfo),
			'salt'            => \ModulbankHelper::getSalt(),
		];
		$key                   = $this->getSecretKey();
		$postData['signature'] = \ModulbankHelper::calcSignature($key, $postData);
		$this->log($postData, 'paymentForm');
		return $postData;
	}

	protected function getCmsVersion()
	{
		$objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
		$productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
		return $productMetadata->getVersion();
	}

	public function getTransactionStatus($transaction)
	{
		$merchant = $this->getConfigData('merchant');

		$key    = $this->getSecretKey();
		$params = [
			'merchant'    => $merchant,
			'transaction' => $transaction,
		];
		$this->log($params, 'getTransactionStatus');
		$result = \ModulbankHelper::getTransactionStatus(
			$merchant,
			$transaction,
			$key
		);
		$this->log($result, 'getTransactionStatus_response');
		return json_decode($result);
	}

	public function sendlogs()
	{
        \ModulbankHelper::sendPackedLogs($this->_dir->getRoot().'/var/log');
	}

}
