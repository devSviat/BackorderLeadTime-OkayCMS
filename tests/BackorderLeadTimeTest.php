<?php

namespace Modules\Sviat\BackorderLeadTime;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Okay\Core\EntityFactory;
use Okay\Core\Settings;
use Okay\Modules\Sviat\BackorderLeadTime\Entities\BackorderLeadTimesEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTerm;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeSettings;
use PHPUnit\Framework\TestCase;

class BackorderLeadTimeTest extends TestCase
{
    public function testBrandTermWinsOverDefault(): void
    {
        $service = $this->service(['default_from' => 7, 'default_to' => 14], [5 => [21, 28]]);

        $this->assertEquals(new LeadTerm(21, 28), $service->termFor(5));
    }

    public function testBrandWithoutTermUsesDefault(): void
    {
        $service = $this->service(['default_from' => 7, 'default_to' => 14], [5 => [21, 28]]);

        $this->assertEquals(new LeadTerm(7, 14), $service->termFor(9));
        $this->assertEquals(new LeadTerm(7, 14), $service->termFor(0));
        $this->assertEquals(new LeadTerm(7, 14), $service->termFor(null));
    }

    public function testDisabledBrandIsNotBackorder(): void
    {
        $service = $this->service(['default_from' => 7, 'default_to' => 14, 'disabled_brands' => [5, '9']], [5 => [21, 28]]);

        $this->assertNull($service->termFor(5));
        $this->assertNull($service->forItem(5, 0));
        $this->assertNull($service->forItem(9, 0));
        $this->assertFalse($service->isBrandEnabled(5));
        $this->assertEquals(new LeadTerm(7, 14), $service->termFor(3));
        $this->assertEquals(new LeadTerm(7, 14), $service->termFor(null));
    }

    public function testBrokenDisabledListMeansAllEnabled(): void
    {
        foreach (['', 'x', 'a:1:{', [0, -3, 'abc']] as $stored) {
            $service = $this->service(['default_from' => 7, 'default_to' => 14, 'disabled_brands' => $stored], []);

            $this->assertEquals(new LeadTerm(7, 14), $service->termFor(5), var_export($stored, true));
        }
    }

    public function testBusinessDaysSkipWeekends(): void
    {
        $term = new LeadTerm(14, 21);

        $this->assertSame(['from' => 10, 'to' => 15], $term->businessDays($this->kyiv('2026-10-26 09:00')));
        $this->assertSame(['from' => 1, 'to' => 1], new LeadTerm(1, 1)->businessDays($this->kyiv('2026-10-31 09:00')));
        $this->assertSame(['from' => 1, 'to' => 1], new LeadTerm(1, 2)->businessDays($this->kyiv('2026-10-30 18:00')));
    }

    public function testNoDefaultMeansNothing(): void
    {
        $service = $this->service([], [5 => [21, 28]]);

        $this->assertNull($service->forItem(9, 0));
        $this->assertNull($service->forItem(5, 0));
    }

    public function testOnlyZeroStockWithPreorderIsBackorder(): void
    {
        $service = $this->service([], []);
        $this->assertTrue($service->isBackorder(0));
        $this->assertTrue($service->isBackorder('0'));
        $this->assertFalse($service->isBackorder(null));
        $this->assertFalse($service->isBackorder(3));
        $this->assertFalse($service->isBackorder(''));

        $this->assertFalse($this->service([], [], preorder: 0)->isBackorder(0));
    }

    public function testShipDateIsTodayPlusUpperBound(): void
    {
        $service = $this->service(['default_from' => 21, 'default_to' => 28], []);

        $backorder = $service->forItem(null, 0, $this->kyiv('2026-10-25 09:30'));

        $this->assertSame('2026-11-22 12:00', $backorder->shipDate->format('Y-m-d H:i'));
        $this->assertSame('2026-11-22T12:00+0200', $backorder->availabilityDate());
    }

    public function testShipDateAcrossDstAndYearEnd(): void
    {
        $this->assertSame(
            '2026-10-27T12:00+0200',
            $this->service(['default_from' => 3, 'default_to' => 7], [])->forItem(null, 0, $this->kyiv('2026-10-20 09:30'))->availabilityDate()
        );
        $this->assertSame(
            '2027-01-03T12:00+0200',
            $this->service(['default_from' => 1, 'default_to' => 14], [])->forItem(null, 0, $this->kyiv('2026-12-20 09:30'))->availabilityDate()
        );
        $this->assertSame(
            '2026-04-03T12:00+0300',
            $this->service(['default_from' => 1, 'default_to' => 14], [])->forItem(null, 0, $this->kyiv('2026-03-20 09:30'))->availabilityDate()
        );
    }

    public function testInvalidBrandRowFallsBackToDefault(): void
    {
        $service = $this->service(
            ['default_from' => 7, 'default_to' => 14],
            [1 => [0, 5], 2 => [10, 5], 3 => [300, 400], 4 => [21, 28]],
        );

        foreach ([1, 2, 3] as $brandId) {
            $this->assertEquals(new LeadTerm(7, 14), $service->termFor($brandId));
        }
        $this->assertEquals(new LeadTerm(21, 28), $service->termFor(4));
        $this->assertSame([4], array_keys($service->brandTerms()));
    }

    public function testParseRejectsInvalid(): void
    {
        foreach ([['0', '5'], ['5', '3'], ['5', ''], ['366', '400'], ['a', '5']] as [$from, $to]) {
            try {
                LeadTerm::parse($from, $to);
                $this->fail("«{$from}»–«{$to}» мало бути відхилене");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertNull(LeadTerm::parse('', ''));
    }

    private function kyiv(string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($time, new DateTimeZone('Europe/Kyiv'));
    }

    /**
     * @param array<string, mixed> $moduleSettings ключі без префікса
     * @param array<int, array{int, int}> $brandRows brand_id => [від, до]
     */
    private function service(array $moduleSettings, array $brandRows, int $preorder = 1): BackorderLeadTime
    {
        $settings = $this->createStub(Settings::class);
        $settings->method('get')->willReturnCallback(function ($name) use ($moduleSettings, $preorder) {
            if ($name === 'is_preorder') {
                return $preorder;
            }

            return $moduleSettings[substr($name, strlen(LeadTimeSettings::PREFIX))] ?? null;
        });

        $rows = [];
        foreach ($brandRows as $brandId => [$from, $to]) {
            $rows[] = (object) ['id' => $brandId, 'brand_id' => $brandId, 'days_from' => $from, 'days_to' => $to];
        }
        $entity = $this->createStub(BackorderLeadTimesEntity::class);
        $entity->method('noLimit')->willReturnSelf();
        $entity->method('find')->willReturn($rows);
        $factory = $this->createStub(EntityFactory::class);
        $factory->method('get')->willReturn($entity);

        return new BackorderLeadTime($factory, new LeadTimeSettings($settings), $settings);
    }
}
