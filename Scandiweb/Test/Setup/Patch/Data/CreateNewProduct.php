<?php

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\StoreManagerInterface;

class CreateNewProduct implements DataPatchInterface
{
    /**
     * @var array
     */
    protected array $sourceItems = [];

    /**
     * @param State $appState
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductRepositoryInterface $productRepository
     * @param EavSetup $eavSetup
     * @param CategoryLinkManagementInterface $categoryLink
     * @param StoreManagerInterface $storeManager
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     */
    public function __construct(
        protected State $appState,
        protected ProductInterfaceFactory $productInterfaceFactory,
        protected ProductRepositoryInterface $productRepository,
        protected EavSetup $eavSetup,
        protected CategoryLinkManagementInterface $categoryLink,
        protected StoreManagerInterface $storeManager,
        protected SourceItemInterfaceFactory $sourceItemFactory,
        protected SourceItemsSaveInterface $sourceItemsSaveInterface
    ) {
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return void
     */
    public function apply(): void
    {
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $product = $this->productInterfaceFactory->create();

        if($product->getIdBySky('test-1')) {
            return;
        }

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');

        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName('Test 1')
            ->setSku('test-1')
            ->setUrlKey('test1')
            ->setPrice(4.99)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Product\Attribute\Source\Status::STATUS_ENABLED);

        $product = $this->productRepository->save($product);

        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setQuantity(1);
        $sourceItem->setSku($product->getSku());
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItems[] = $sourceItem;

        $this->sourceItemsSaveInterface->execute($this->sourceItems);

        $this->categoryLink->assignProductToCategories($product->getSku(), [2]);
    }
}
