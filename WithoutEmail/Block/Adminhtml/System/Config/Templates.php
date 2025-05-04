<?php
namespace MagoArab\WithoutEmail\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Templates extends Field
{
    protected $_template = 'MagoArab_WithoutEmail::system/config/templates.phtml';

    /**
     * Get the HTML
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
    
    /**
     * Get available placeholders
     */
    public function getPlaceholders()
    {
        return [
            '{{order_id}}' => __('Order ID'),
            '{{customer_name}}' => __('Customer Name'),
            '{{order_total}}' => __('Order Total'),
            '{{tracking_number}}' => __('Tracking Number'),
            '{{business_name}}' => __('Business Name'),
            '{{support_phone}}' => __('Support Phone'),
            '{{order_date}}' => __('Order Date'),
            '{{delivery_date}}' => __('Estimated Delivery Date'),
            '{{payment_method}}' => __('Payment Method'),
            '{{shipping_method}}' => __('Shipping Method'),
            '{{order_status}}' => __('Order Status'),
            '{{order_link}}' => __('Order Link')
        ];
    }
    
    /**
     * Get default templates
     */
    public function getDefaultTemplates()
    {
        return [
            'pending' => __('Hello {{customer_name}}, your order #{{order_id}} has been received and is being processed. Thank you for shopping with {{business_name}}!'),
            'processing' => __('Hello {{customer_name}}, your order #{{order_id}} is now being processed. We will notify you once it ships.'),
            'complete' => __('Hello {{customer_name}}, your order #{{order_id}} has been completed. Thank you for shopping with {{business_name}}!'),
            'canceled' => __('Hello {{customer_name}}, your order #{{order_id}} has been canceled. If you have any questions, please contact us at {{support_phone}}.'),
            'holded' => __('Hello {{customer_name}}, your order #{{order_id}} is currently on hold. Our team will contact you soon.'),
            'shipped' => __('Hello {{customer_name}}, your order #{{order_id}} has been shipped! Tracking number: {{tracking_number}}'),
            'refunded' => __('Hello {{customer_name}}, your refund for order #{{order_id}} has been processed. The amount will be credited to your account within 5-7 business days.')
        ];
    }
}