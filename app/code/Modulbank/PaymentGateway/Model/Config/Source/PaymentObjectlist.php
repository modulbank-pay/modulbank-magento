<?php
namespace Modulbank\PaymentGateway\Model\Config\Source;

class PaymentObjectlist implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
				['value' => 'commodity', 'label' => __('товар')],
				['value' => 'excise', 'label' => __('подакцизный товар')],
				['value' => 'job', 'label' => __('работа')],
				['value' => 'service', 'label' => __('услуга')],
				['value' => 'gambling_bet', 'label' => __('ставка в азартной игре')],
				['value' => 'gambling_prize', 'label' => __('выигрыш в азартной игре')],
				['value' => 'lottery', 'label' => __('лотерейный билет')],
				['value' => 'lottery_prize', 'label' => __('выигрыш в лотерею')],
				['value' => 'intellectual_activity', 'label' => __('результаты интеллектуальной деятельности')],
				['value' => 'payment', 'label' => __('платеж')],
				['value' => 'agent_commission', 'label' => __('агентское вознаграждение')],
				['value' => 'composite', 'label' => __('несколько вариантов')],
				['value' => 'another', 'label' => __('другое')],
		];
	}
}