<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Model\ResourceModel\Event;

use Magento\CatalogEvent\Model\Event;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixture Magento/CatalogEvent/_files/events.php
 */
class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected $_collection;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->_collection = $this->objectManager->create(Collection::class)
            ->addOrder('category_id', Collection::SORT_ORDER_ASC);
    }

    /**
     * Assert that collection contains expected item at expected index within the expected number of items
     *
     * @param int $expectedItemCount
     * @param int $expectedItemIndex
     * @param array $expectedItemData
     */
    protected function _assertCollectionData(int $expectedItemCount, int $expectedItemIndex, array $expectedItemData)
    {
        $items = array_values($this->_collection->getItems());
        $this->assertEquals($expectedItemCount, count($items), 'Expected number of collection items.');

        /** @var $actualItem Event */
        $actualItem = $items[$expectedItemIndex];

        $this->assertInstanceOf(Event::class, $actualItem);
        foreach ($expectedItemData as $filedName => $expectedValue) {
            $actualValue = $actualItem->getDataUsingMethod($filedName);
            $this->assertEquals(
                $expectedValue,
                $actualValue,
                "Field '{$filedName}' value doesn't match expectations."
            );
        }
    }

    /**
     * @return array
     */
    public function loadDataProvider()
    {
        return [
            'closed event' => [
                'index' => 0,
                'data' => [
                    'category_id' => null,
                    'display_state' => Event::DISPLAY_CATEGORY_PAGE,
                    'sort_order' => 30,
                    'status' => Event::STATUS_CLOSED,
                    'image' => 'default_store_view.jpg',
                ],
            ],
            'open event' => [
                'index' => 1,
                'data' => [
                    'category_id' => 1,
                    'display_state' => Event::DISPLAY_PRODUCT_PAGE,
                    'sort_order' => 20,
                    'status' => Event::STATUS_OPEN,
                    'image' => 'default_website.jpg',
                ],
            ],
            'upcoming event' => [
                'index' => 2,
                'data' => [
                    'category_id' => 2,
                    'display_state' => 3,
                    /*\Magento\CatalogEvent\Model\Event::DISPLAY_CATEGORY_PAGE,
                        \Magento\CatalogEvent\Model\Event::DISPLAY_PRODUCT_PAGE*/
                    'sort_order' => 10,
                    'status' => Event::STATUS_UPCOMING,
                    'image' => 'default_store_view.jpg',
                ],
            ]
        ];
    }

    /**
     * @dataProvider loadDataProvider
     * @param $expectedItemIndex
     * @param array $expectedItemData
     * @return void
     */
    public function testLoad($expectedItemIndex, array $expectedItemData): void
    {
        $this->_collection->addCategoryData()->addImageData();
        $this->_assertCollectionData(3, $expectedItemIndex, $expectedItemData);
    }

    /**
     * @return array|array[]
     */
    public function loadVisibleDataProvider(): array
    {
        $result = $this->loadDataProvider();

        unset($result['closed event']);
        $result['open event']['index'] = 0;
        $result['upcoming event']['index'] = 1;

        return $result;
    }

    /**
     * @dataProvider loadVisibleDataProvider
     * @param $expectedItemIndex
     * @param array $expectedItemData
     * @return void
     */
    public function testLoadVisible($expectedItemIndex, array $expectedItemData): void
    {
        $this->_collection->addCategoryData()
            ->addImageData()
            ->addVisibilityFilter();
        $this->_assertCollectionData(2, $expectedItemIndex, $expectedItemData);
    }

    /**
     * @dataProvider addFieldToFilterDataProvider
     * @param $value
     * @param $expectedCount
     * @param $expectedItemData
     */
    public function testAddFieldToFilter($value, $expectedCount, $expectedItemData)
    {
        $this->_collection->addCategoryData()
            ->addImageData()
            ->addFieldToFilter('display_state', $value);
        $this->_assertCollectionData($expectedCount, 0, $expectedItemData);
    }

    /**
     * Data for testAddFieldToFilter
     *
     * @return array
     */
    public function addFieldToFilterDataProvider()
    {
        $data = $this->loadDataProvider();

        return [
            [
                'display_state' => Event::DISPLAY_CATEGORY_PAGE,
                'expected_count' => 2,
                'data' => $data['closed event']['data'],
            ],
            [
                'display_state' => Event::DISPLAY_PRODUCT_PAGE,
                'expected_count' => 2,
                'data' => $data['open event']['data']
            ],
            [
                'display_state' => 0,
                'expected_count' => 3,
                'data' => $data['closed event']['data']
            ]
        ];
    }

    /**
     * Checks that event image is preserved after event status change
     *
     * @return void
     */
    public function testPreserveEventImageAfterStatusChange(): void
    {
        $event = $this->_collection->getItemsByColumnValue('category_id', 2);
        $event = array_shift($event);
        $eventId = $event->getId();
        $event->setStatus(Event::STATUS_OPEN);
        $event->save();
        $collectionAfterSave = $this->objectManager->get(Collection::class)
            ->addImageData();
        $savedEvent = $collectionAfterSave->getItemById($eventId);

        $this->assertEquals('default_store_view.jpg', $savedEvent->getImage());
    }
}
