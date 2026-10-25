<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Helpers;

use DateTimeImmutable;
use Okay\Core\FrontTranslations;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\SmartyPlugins\Plugins\Plural;

/**
 * Тексти терміну мовою вітрини. Адмінка показує ними те, що побачить покупець,
 * а Backend/design/js/backorder_lead_time.js відтворює ці самі правила для живого перегляду.
 */
class LeadTimeLabels
{
    private const string PREFIX = 'sviat__backorder_lead_time__';

    public function __construct(private FrontTranslations $translations)
    {
    }

    /** «22 листопада» */
    public function dateLabel(DateTimeImmutable $date): string
    {
        $label = $date->format('j') . ' ' . $this->text('month_' . $date->format('n'));

        return ExtenderFacade::execute(__METHOD__, $label, func_get_args());
    }

    /** Форма слова «день» для числа, за правилом модифікатора plural. */
    public function daysWord(int $days): string
    {
        $word = (string) new Plural()->run($days, $this->text('day_1'), $this->text('day_5'), $this->text('day_2'));

        return ExtenderFacade::execute(__METHOD__, $word, func_get_args());
    }

    /** «21–28 днів»; одне число, коли межі збігаються. */
    public function termText(LeadTerm $term): string
    {
        $range = $term->from === $term->to ? (string) $term->to : "{$term->from}–{$term->to}";
        $text = $range . ' ' . $this->daysWord($term->to);

        return ExtenderFacade::execute(__METHOD__, $text, func_get_args());
    }

    /** «Відправка через 7–14 днів, орієнтовно до 8 листопада» — два рядки картки товару одним реченням. */
    public function sentence(LeadTerm $term, DateTimeImmutable $now): string
    {
        $word = $this->daysWord($term->to);
        $shipsIn = $term->from === $term->to
            ? sprintf($this->text('ships_in_exact'), $term->to, $word)
            : sprintf($this->text('ships_in'), $term->from, $term->to, $word);
        $by = sprintf($this->text('estimated_by'), $this->dateLabel($term->shipDate($now)));
        $sentence = $shipsIn . ', ' . mb_lcfirst($by);

        return ExtenderFacade::execute(__METHOD__, $sentence, func_get_args());
    }

    /**
     * Сирі переклади для JS живого перегляду.
     *
     * @return array<string, string|list<string>>
     */
    public function forScript(): array
    {
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = $this->text('month_' . $month);
        }
        $texts = [
            'months' => $months,
            'day_1' => $this->text('day_1'),
            'day_2' => $this->text('day_2'),
            'day_5' => $this->text('day_5'),
            'ships_in' => $this->text('ships_in'),
            'ships_in_exact' => $this->text('ships_in_exact'),
            'estimated_by' => $this->text('estimated_by'),
        ];

        return ExtenderFacade::execute(__METHOD__, $texts, func_get_args());
    }

    private function text(string $key): string
    {
        return (string) $this->translations->getTranslation(self::PREFIX . $key);
    }
}
