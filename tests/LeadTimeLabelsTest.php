<?php

namespace Modules\Sviat\BackorderLeadTime;

use DateTimeImmutable;
use DateTimeZone;
use Okay\Core\FrontTranslations;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTerm;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeLabels;
use PHPUnit\Framework\TestCase;

class LeadTimeLabelsTest extends TestCase
{
    public function testTermTextPluralsAndSingleNumber(): void
    {
        $labels = $this->labels('ua');

        $this->assertSame('21–28 днів', $labels->termText(new LeadTerm(21, 28)));
        $this->assertSame('1–21 день', $labels->termText(new LeadTerm(1, 21)));
        $this->assertSame('2–3 дні', $labels->termText(new LeadTerm(2, 3)));
        $this->assertSame('11–12 днів', $labels->termText(new LeadTerm(11, 12)));
        $this->assertSame('7 днів', $labels->termText(new LeadTerm(7, 7)));
        $this->assertSame('1 день', $labels->termText(new LeadTerm(1, 1)));
    }

    public function testEnglishHasNoPaucalForm(): void
    {
        $labels = $this->labels('en');

        $this->assertSame('2–3 days', $labels->termText(new LeadTerm(2, 3)));
        $this->assertSame('1 day', $labels->termText(new LeadTerm(1, 1)));
    }

    public function testSentenceUsesStorefrontDateRule(): void
    {
        $labels = $this->labels('ua');
        $now = new DateTimeImmutable('2026-10-25 09:30', new DateTimeZone('Europe/Kyiv'));

        $this->assertSame('Відправка через 7–14 днів, орієнтовно до 8 листопада', $labels->sentence(new LeadTerm(7, 14), $now));
        $this->assertSame('Відправка через 3 дні, орієнтовно до 28 жовтня', $labels->sentence(new LeadTerm(3, 3), $now));
        $this->assertSame('22 листопада', $labels->dateLabel(new LeadTerm(21, 28)->shipDate($now)));
    }

    public function testScriptTextsCarryAllMonths(): void
    {
        $texts = $this->labels('ua')->forScript();

        $this->assertCount(12, $texts['months']);
        $this->assertSame('листопада', $texts['months'][10]);
        $this->assertSame('дні', $texts['day_2']);
    }

    private function labels(string $langLabel): LeadTimeLabels
    {
        /** @var array<string, string> $lang */
        $lang = [];
        include dirname(__DIR__, 4) . "/Okay/Modules/Sviat/BackorderLeadTime/design/lang/{$langLabel}.php";
        $translations = $this->createStub(FrontTranslations::class);
        $translations->method('getTranslation')->willReturnCallback(fn($key) => $lang[$key] ?? null);

        return new LeadTimeLabels($translations);
    }
}
