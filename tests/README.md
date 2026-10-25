# Тести модуля BackorderLeadTime

Тести модульні: сайт не піднімають, у базу не ходять. Вони перевіряють, що
вважається товаром під замовлення, вибір терміну (бренд чи загальний), дату
відправки й часовий пояс, Smarty-плагін і зміни рядків обох фідів Google,
підставляючи заглушки замість сервісів ядра.

## 1. Візьміть тести з репозиторію

В архів релізу тека `tests/` **не входить**. Взяти її можна з репозиторію:

```bash
git clone https://github.com/devSviat/BackorderLeadTime-OkayCMS.git
```

## 2. Покладіть їх у OkayCMS

```
{OkayCMS_root}/
├── Okay/Modules/Sviat/BackorderLeadTime/     ← модуль
└── tests/Modules/Sviat/BackorderLeadTime/     ← його тести
```

```bash
cd {OkayCMS_root}
mkdir -p tests/Modules/Sviat/BackorderLeadTime
cp -r ~/BackorderLeadTime-OkayCMS/tests/. tests/Modules/Sviat/BackorderLeadTime/
```

PHPUnit бере тільки те, що вказано в `phpunit.xml` рушія, а класи ядра, без яких
тести не працюють, приходять із його автозавантажувача.

## 3. Запустіть

```bash
cd {OkayCMS_root}
vendor/bin/phpunit tests/Modules/Sviat/BackorderLeadTime
```

Очікуваний результат — рядок `OK (N tests, M assertions)`.

**Немає `vendor/bin/phpunit`?** Залежності ставили без dev-пакетів:
`composer install`.

## Що перевіряє CI

На кожен PR і мердж у `main`:

1. `php -l` на всьому коді модуля під PHP 8.5;
2. модуль ставиться окремо в стокову OkayCMS 4.5.3 і окремо у
   [форк OkayCMS](https://github.com/devSviat/OkayCMS-Fork), і там запускається
   `phpunit`;
3. після мерджу, якщо версія в `Init/module.json` ще не має тега, ставиться тег
   і збирається архів релізу.
