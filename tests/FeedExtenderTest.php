<?php

namespace Modules\Sviat\BackorderLeadTime;

use Okay\Core\EntityFactory;
use Okay\Core\Settings;
use Okay\Modules\Sviat\BackorderLeadTime\Entities\BackorderLeadTimesEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Extenders\FeedExtender;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeSettings;
use PHPUnit\Framework\TestCase;

class FeedExtenderTest extends TestCase
{
    private int $findCalls = 0;

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

    public function testInStockItemUntouched(): void
    {
        $extender = $this->extender();
        $inStock = ['g:availability' => ['data' => 'in stock']];

        $this->assertSame($inStock, $extender->googleMerchantItem($inStock, $this->product(3, 1)));
        $this->assertSame($inStock, $extender->googleMerchantItem($inStock, $this->product(null, 1)));
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

    private function outOfStock(): array
    {
        return ['title' => ['data' => 'x'], 'g:availability' => ['data' => 'out of stock']];
    }

    private function product(int|string|null $stock, int $brandId): object
    {
        return (object) ['stock' => $stock, 'brand_id' => $brandId];
    }

    private function extender(int $preorder = 1): FeedExtender
    {
        $settings = $this->createStub(Settings::class);
        $settings->method('get')->willReturnCallback(fn($name) => match (true) {
            $name === 'is_preorder' => $preorder,
            str_ends_with($name, 'default_from') => 7,
            str_ends_with($name, 'default_to') => 14,
            default => null,
        });

        $entity = $this->createStub(BackorderLeadTimesEntity::class);
        $entity->method('noLimit')->willReturnSelf();
        $entity->method('find')->willReturnCallback(function () {
            $this->findCalls++;
            return [(object) ['id' => 1, 'brand_id' => 1, 'days_from' => 21, 'days_to' => 28]];
        });
        $factory = $this->createStub(EntityFactory::class);
        $factory->method('get')->willReturn($entity);

        return new FeedExtender(new BackorderLeadTime($factory, new LeadTimeSettings($settings), $settings));
    }
}
