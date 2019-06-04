<?php
namespace Modulbank\PaymentGateway\Block\Adminhtml\Form\Field;


class Url extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_url;
    /**
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Framework\UrlInterface $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        $data = []
    ) {
        $this->_url = $urlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Generates element html
     *
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<a href="'.$this->_url->getUrl('modulbank/modulbank/index').'">Скачать логи</a>';
        return $html;
    }

}
