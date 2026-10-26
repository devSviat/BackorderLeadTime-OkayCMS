<?php

namespace Modules\Sviat\BackorderLeadTime;

use Okay\Core\EntityFactory;
use Okay\Core\Settings;
use Okay\Helpers\XmlFeedHelper;
use Okay\Entities\ProductsEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Entities\BackorderLeadTimesEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Extenders\FeedExtender;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeSettings;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class FeedExtenderTest extends TestCase
{
    private int $findCalls = 0;

    private ?array $productFields = null;

    protected function tearDown(): void
    {
        if ($this->productFields !== null) {
            new ReflectionProperty(ProductsEntity::class, 'fields')->setValue(null, $this->productFields);
        }
    }

    public function testBackorderItemGetsStatusAndDate(): void
    {
        $result = $this->extender()->googleMerchantItem($this->outOfStock(), $this->product(0, 1));

        $this->assertSame('backorder', $result['g:availability']['data']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T12:00[+-]\d{4}$/',
            $result['g:availability_date']['data']
        );
        $this->assertSame('title', array_key_first($result));
    }

    public function testBackorderExcludedFromAdsByDefault(): void
    {
        $result = $this->extender()->googleMerchantItem($this->outOfStock(), $this->product(0, 1));

        $this->assertSame(FeedExtender::AD_DESTINATIONS, $this->excludedDestinations($result));
        $this->assertNotContains('Free_listings', $this->excludedDestinations($result));
    }

    public function testDisabledBrandStaysOutOfStock(): void
    {
        $extender = $this->extender(disabledBrands: [1]);

        $this->assertSame($this->outOfStock(), $extender->googleMerchantItem($this->outOfStock(), $this->product(0, 1)));
        $items = [['tag' => 'item', 'data' => $this->outOfStock()]];
        $this->assertSame($items, $extender->feedsGoogleItem($items, $this->product(0, 1)));
        $this->assertSame('backorder', $extender->googleMerchantItem($this->outOfStock(), $this->product(0, 2))['g:availability']['data']);
    }

    public function testExclusionTurnedOffKeepsAds(): void
    {
        $result = $this->extender(excludeAds: '0')->googleMerchantItem($this->outOfStock(), $this->product(0, 1));

        $this->assertSame('backorder', $result['g:availability']['data']);
        $this->assertSame([], $this->excludedDestinations($result));
    }

    public function testExcludedDestinationsCompileToRepeatedTags(): void
    {
        $result = $this->extender()->googleMerchantItem($this->outOfStock(), $this->product(0, 1));
        $helper = new \ReflectionClass(XmlFeedHelper::class)->newInstanceWithoutConstructor();

        $xml = $helper->compileItems($result);

        $this->assertSame(1, substr_count($xml, '<g:excluded_destination>Shopping_ads</g:excluded_destination>'));
        $this->assertSame(1, substr_count($xml, '<g:excluded_destination>Display_ads</g:excluded_destination>'));
        $this->assertSame(1, substr_count($xml, '<g:excluded_destination>Local_inventory_ads</g:excluded_destination>'));
        $this->assertStringNotContainsString('excluded_destination:', $xml);
    }

    public function testFeedsAdapterItemExcludedFromAds(): void
    {
        $items = [['tag' => 'item', 'data' => $this->outOfStock()]];

        $result = $this->extender()->feedsGoogleItem($items, $this->product(0, 1));

        $this->assertSame(FeedExtender::AD_DESTINATIONS, $this->excludedDestinations($result[0]['data']));
    }

    public function testInStockItemUntouched(): void
    {
        $extender = $this->extender();
        $inStock = ['g:availability' => ['data' => 'in stock']];

        $this->assertSame($inStock, $extender->googleMerchantItem($inStock, $this->product(3, 1)));
        $this->assertSame($inStock, $extender->googleMerchantItem($inStock, $this->product(null, 1)));
        $this->assertSame([], $this->excludedDestinations($extender->googleMerchantItem($inStock, $this->product(3, 1))));
    }

    public function testPreorderOffUntouched(): void
    {
        $result = $this->outOfStock();

        $this->assertSame($result, $this->extender(preorder: 0)->googleMerchantItem($result, $this->product(0, 1)));
    }

    public function testFeedsAdapterItemGetsStatusAndDate(): void
    {
        $items = [['tag' => 'item', 'data' => $this->outOfStock()]];

        $result = $this->extender()->feedsGoogleItem($items, $this->product(0, 1));

        $this->assertSame('backorder', $result[0]['data']['g:availability']['data']);
        $this->assertArrayHasKey('g:availability_date', $result[0]['data']);
        $this->assertSame('item', $result[0]['tag']);
    }

    public function testNoSqlInsideLoop(): void
    {
        $extender = $this->extender();
        $query = new \stdClass();

        $this->assertSame($query, $extender->warmUpTerms($query));
        $this->assertSame(1, $this->findCalls);

        foreach ([1, 3, 7] as $brandId) {
            $extender->googleMerchantItem($this->outOfStock(), $this->product(0, $brandId));
        }
        $this->assertSame(1, $this->findCalls);
    }

    public function testDiscontinuedProductUntouched(): void
    {
        $this->registerDiscontinuedField();
        $extender = $this->extender(discontinued: [42]);
        $extender->warmUpTerms(new \stdClass());

        $this->assertSame($this->outOfStock(), $extender->googleMerchantItem($this->outOfStock(), $this->product(0, 1, 42)));
        $items = [['tag' => 'item', 'data' => $this->outOfStock()]];
        $this->assertSame($items, $extender->feedsGoogleItem($items, $this->product(0, 1, 42)));

        $other = $extender->googleMerchantItem($this->outOfStock(), $this->product(0, 1, 43));
        $this->assertSame('backorder', $other['g:availability']['data']);
        $this->assertSame(2, $this->findCalls);
    }

    public function testWithoutDiscontinuedFieldNoProductsQuery(): void
    {
        if (in_array('discontinued', ProductsEntity::getFields(), true)) {
            $this->markTestSkipped('поле discontinued уже зареєстроване в цьому процесі');
        }
        $extender = $this->extender(discontinued: [42]);
        $extender->warmUpTerms(new \stdClass());

        $result = $extender->googleMerchantItem($this->outOfStock(), $this->product(0, 1, 42));

        $this->assertSame('backorder', $result['g:availability']['data']);
        $this->assertSame(1, $this->findCalls);
    }

    /** @return list<string> значення всіх g:excluded_destination у порядку появи */
    private function excludedDestinations(array $data): array
    {
        $values = [];
        foreach ($data as $key => $item) {
            if (($item['tag'] ?? $key) === 'g:excluded_destination') {
                $values[] = $item['data'];
            }
        }

        return $values;
    }

    private function registerDiscontinuedField(): void
    {
        $this->productFields = ProductsEntity::getFields();
        ProductsEntity::addField('discontinued');
    }

    private function outOfStock(): array
    {
        return ['title' => ['data' => 'x'], 'g:availability' => ['data' => 'out of stock']];
    }

    private function product(int|string|null $stock, int $brandId, int $productId = 1): object
    {
        return (object) ['stock' => $stock, 'brand_id' => $brandId, 'product_id' => $productId];
    }

    /** @param list<int> $discontinued id товарів, які віддасть ProductsEntity з discontinued = 1 */
    private function extender(int $preorder = 1, array $discontinued = [], ?string $excludeAds = null, array $disabledBrands = []): FeedExtender
    {
        $settings = $this->createStub(Settings::class);
        $settings->method('get')->willReturnCallback(fn($name) => match (true) {
            $name === 'is_preorder' => $preorder,
            str_ends_with($name, 'default_from') => 7,
            str_ends_with($name, 'default_to') => 14,
            str_ends_with($name, 'exclude_from_ads') => $excludeAds,
            str_ends_with($name, 'disabled_brands') => $disabledBrands,
            default => null,
        });

        $entity = $this->createStub(BackorderLeadTimesEntity::class);
        $entity->method('noLimit')->willReturnSelf();
        $entity->method('find')->willReturnCallback(function () {
            $this->findCalls++;
            return [(object) ['id' => 1, 'brand_id' => 1, 'days_from' => 21, 'days_to' => 28]];
        });
        $products = $this->createStub(ProductsEntity::class);
        $products->method('cols')->willReturnSelf();
        $products->method('noLimit')->willReturnSelf();
        $products->method('find')->willReturnCallback(function (array $filter) use ($discontinued) {
            $this->findCalls++;
            return $filter === ['discontinued' => 1] ? array_map(strval(...), $discontinued) : [];
        });
        $factory = $this->createStub(EntityFactory::class);
        $factory->method('get')->willReturnCallback(fn($class) => $class === ProductsEntity::class ? $products : $entity);

        $leadTimeSettings = new LeadTimeSettings($settings);

        return new FeedExtender(new BackorderLeadTime($factory, $leadTimeSettings, $settings), $leadTimeSettings);
    }
}
