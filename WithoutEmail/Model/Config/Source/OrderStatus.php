<?php
namespace MagoArab\WithoutEmail\Model\Config\Source;

use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class OrderStatus implements OptionSourceInterface
{
    protected $statusCollectionFactory;

    public function __construct(
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    public function toOptionArray()
    {
        $options = [];
        $statuses = $this->statusCollectionFactory->create()->toOptionArray();
        
        foreach ($statuses as $status) {
            $options[] = [
                'value' => $status['value'],
                'label' => $status['label']
            ];
        }
        
        return $options;
    }
}