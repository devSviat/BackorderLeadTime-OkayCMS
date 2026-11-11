<?php

namespace Modules\Sviat\BackorderLeadTime;

use Closure;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Modules\Sviat\MerchantApi\Services\CatalogItemBuilder;
use Okay\Modules\Sviat\BackorderLeadTime\Extenders\FeedExtender;
use Okay\Modules\Sviat\BackorderLeadTime\Init\Init;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Модуль доповнює записи товарів і для MerchantApi: без GoogleMerchant той збирає записи сам
 * (CatalogItemBuilder), і без цього підключення зміни модуля в Google більше не потрапляли б.
 */
class MerchantApiWiringTest extends TestCase
{
    public function testHooksIntoCatalogItemBuilder(): void
    {
        if (!class_exists(CatalogItemBuilder::class)) {
            $this->markTestSkipped('Sviat/MerchantApi не встановлено: підключення до нього перевіряється там, де він є.');
        }
        $chains = $this->registeredChains();

        $this->assertContains([CatalogItemBuilder::class, 'getQuery', FeedExtender::class, 'warmUpTerms'], $chains);
        $this->assertContains([CatalogItemBuilder::class, 'getItem', FeedExtender::class, 'googleMerchantItem'], $chains);
    }

    /** @return list<array{string, string, string, string}> */
    private function registeredChains(): array
    {
        $recorded = [];
        $facade = $this->createStub(ExtenderFacade::class);
        $facade->method('newChainExtension')->willReturnCallback(static function ($expandable, $extension) use (&$recorded) {
            $recorded[] = [$expandable['class'] ?? $expandable[0], $expandable['method'] ?? $expandable[1], $extension['class'] ?? $extension[0], $extension['method'] ?? $extension[1]];
        });

        $init = (new ReflectionClass(Init::class))->newInstanceWithoutConstructor();
        Closure::bind(fn() => $this->extenderFacade = $facade, $init, \Okay\Core\Modules\AbstractInit::class)();
        (fn() => $this->registerFeedExtenders())->call($init);

        return $recorded;
    }
}
