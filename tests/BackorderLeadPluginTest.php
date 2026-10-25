<?php

namespace Modules\Sviat\BackorderLeadTime;

use DateTimeImmutable;
use DateTimeZone;
use Okay\Core\EntityFactory;
use Okay\Core\FrontTranslations;
use Okay\Core\Settings;
use Okay\Modules\Sviat\BackorderLeadTime\Entities\BackorderLeadTimesEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeLabels;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeSettings;
use Okay\Modules\Sviat\BackorderLeadTime\Plugins\BackorderLeadPlugin;
use PHPUnit\Framework\TestCase;

class BackorderLeadPluginTest extends TestCase
{
    public function testAssignsTermAndLabel(): void
    {
        $assigned = $this->assigned(['default_from' => 7, 'default_to' => 14], ['brand_id' => 5, 'stock' => 0]);

        $this->assertSame([
            'from' => 21,
            'to' => 28,
            'date_label' => '22 листопада',
            'date_iso' => '2026-11-22',
            'availability_date' => '2026-11-22T12:00+0200',
        ], $assigned);
    }

    public function testWithoutStockChecksOnlyPreorderAndTerm(): void
    {
        $this->assertSame(21, $this->assigned(['default_from' => 7, 'default_to' => 14], ['brand_id' => 5])['from']);
        $this->assertNull($this->assigned(['default_from' => 7, 'default_to' => 14], ['brand_id' => 5], preorder: 0));
    }

    public function testAssignsNullWhenInStock(): void
    {
        $this->assertNull($this->assigned(['default_from' => 7, 'default_to' => 14], ['brand_id' => 5, 'stock' => 3]));
    }

    public function testAssignsNullWithoutDefault(): void
    {
        $this->assertNull($this->assigned([], ['brand_id' => 5, 'stock' => 0]));
        $this->assertNull($this->assigned([], ['brand_id' => 5]));
    }

    /**
     * @param array<string, int> $moduleSettings ключі без префікса
     * @param array<string, mixed> $params
     */
    private function assigned(array $moduleSettings, array $params, int $preorder = 1): mixed
    {
        $settings = $this->createStub(Settings::class);
        $settings->method('get')->willReturnCallback(function ($name) use ($moduleSettings, $preorder) {
            if ($name === 'is_preorder') {
                return $preorder;
            }

            return $moduleSettings[substr($name, strlen(LeadTimeSettings::PREFIX))] ?? null;
        });
        $entity = $this->createStub(BackorderLeadTimesEntity::class);
        $entity->method('noLimit')->willReturnSelf();
        $entity->method('find')->willReturn([(object) ['id' => 1, 'brand_id' => 5, 'days_from' => 21, 'days_to' => 28]]);
        $factory = $this->createStub(EntityFactory::class);
        $factory->method('get')->willReturn($entity);
        $service = new BackorderLeadTime($factory, new LeadTimeSettings($settings), $settings);

        /** @var array<string, string> $lang */
        $lang = [];
        include dirname(__DIR__, 4) . '/Okay/Modules/Sviat/BackorderLeadTime/design/lang/ua.php';
        $translations = $this->createStub(FrontTranslations::class);
        $translations->method('getTranslation')->willReturnCallback(fn($key) => $lang[$key] ?? null);

        $plugin = new BackorderLeadPlugin(
            $service,
            new LeadTimeLabels($translations),
            new DateTimeImmutable('2026-10-25 09:30', new DateTimeZone('Europe/Kyiv')),
        );
        $template = new class {
            /** @var array<string, mixed> */
            public array $vars = [];

            public function assign(string $name, mixed $value): void
            {
                $this->vars[$name] = $value;
            }
        };

        $this->assertSame('', $plugin->run($params + ['assign' => 'blt'], $template));
        $this->assertArrayHasKey('blt', $template->vars);

        return $template->vars['blt'];
    }
}
