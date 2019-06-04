<?php
namespace Modulbank\PaymentGateway\Controller\Adminhtml\Modulbank;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Backend\App\Action
{
/**
 * @var \Magento\Framework\View\Result\PageFactory
 */
    protected $resultPageFactory;

    protected $_config;

/**
 * Constructor
 *
 * @param \Magento\Backend\App\Action\Context $context
 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
 */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Modulbank\PaymentGateway\Model\Modulbank $paymentConfig
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_config = $paymentConfig;
    }

/**
 * Load the page defined in view/adminhtml/layout/exampleadminnewpage_helloworld_index.xml
 *
 * @return \Magento\Framework\View\Result\Page
 */
    public function execute()
    {
        $this->_config->sendlogs();
        exit();
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
