Workers for PHP
====================

[![Beta](http://img.shields.io/badge/Beta-1.0.0-blue.svg)](https://packagist.org/packages/bezk/workers)

Этот репозиторий содержит open source реализацию PHP демонов (воркеров), который позволит исполнять ваши процессы в несколько потоков в ваших PHP приложениях.

Использование
-----

Данная версия Workers для PHP требует PHP 5.4 или выше.

Пример использования:

```php
<?php

date_default_timezone_set('Europe/Moscow');
//эта строка необходима если не используется composer
require_once 'base-workers-dir/autoloader.php';

use Workers\Worker;

// в ключе имя, в значении - данные для использования в воркере
$aWorkersData = array(
    '1 worker' => 'Data 1',
    '2 worker' => 'Data 2',
    '3 worker' => 'Data 3'
);

$oWorker = new Worker(
    true,                   // флаг быстрого запуска
    $aWorkersData,          // массив прокидываемый в воркер
    null,                   // имя функции программы воркеров
    null,                   // имя функции родительского деструктора
    null,                   // имя функции дочернего деструктора
    null,                   // каталог для временных файлов
    './FastStartModeLogs',  // каталог для логов (по умолчанию каталог временных файлов)
    null,                   // количество воркеров
    null,                   // приоритет воркеров (значение от -20 до 20)
    null,                   // лимит в секундах на время исполнения воркера
    null,                   // время задержки для асинхронного запуска в секундах
    null,                   // время ожидания в милисекундах между попытками запустить новый воркер
    false,                  // флаг цикличного выполнения
    null,                   // флаг последовательного запуска (один за одним)
    null,                   // флаг привязки к консоли
    null,                   // флаг вывода потоков в консоль
    true                    // подробный вывод о процессах происходящих в демоне
);

echo $oWorker->getWorkerData();

```

Данный код в три потока напечатает данные, предназначенные для каждого из воркеров.
Типичный use case:

1. Выгрузить из базы ключи, прокинуть их в воркер, в воркере обращаться к БД только по своему ключу.
2. Использовать как select for update
3. Многопоточно выполнить монотонную работу

Остальная документация (будет) размещена в Wiki:
[https://github.com/bezk/workers/wiki](https://github.com/bezk/workers/wiki)


Тестирование и примеры
--------------------

В `tests/` разложен набор тестов для проверки (и демонстрации) проекта.
Инструкции по использованию имеются в комментариях внутри скриптов.
Обращайте особое внимание к скриптам, которые работают в зацикленном режиме отвязанные от консоли.


Содействие
------------

Git Flow:
- Develop branch: develop
- Feature branch: feature
- Hofix branch: hotfix
- Отступы: 4 пробела
- Строка: ≈80 символов
- Стиль: следуя стилю проекта

Комментарий в коммите обязателен
