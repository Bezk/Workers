<?php
/**
 * The MIT License (MIT)
 *
 * Copyright 2015 Anatoliy Bereznyak
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

date_default_timezone_set('Europe/Moscow');
require_once '../autoloader.php';

use Workers\Worker;

$aWorkersData = array(
    '1 worker' => 'Data 1',
    '2 worker' => 'Data 2',
    '3 worker' => 'Data 3'
);

$oWorker = new Worker(
    null,                       // флаг быстрого запуска
    $aWorkersData,              // массив ключей прокидываемый в воркер. Ключ массива – имя воркера. Значение - первый аргумент функции воркера
    null,                       // имя функции программы воркеров
    null,                       // имя функции родительского деструктора
    null,                       // имя функции дочернего деструктора
    null,                       // имя функции завершения работы
    null,                       // каталог для временных файлов (pid файла, пользовательских) (по умолчанию системный каталог временных файлов)
    './OneOffModeLogs',         // каталог для логов (по умолчанию каталог временных файлов)
    null,                       // количество воркеров
    null,                       // приоритет воркеров (значение от -20 до 20)
    null,                       // лимит в секундах на время исполнения воркера
    null,                       // время задержки для асинхронного запуска в секундах
    null,                       // время ожидания в милисекундах между попытками запустить новый воркер
    false,                      // флаг цикличного выполнения
    null,                       // флаг последовательного запуска (один за одним)
    null,                       // флаг привязки к консоли
    null,                       // флаг вывода потоков в консоль
    true                        // подробный вывод о процессах происходящих в демоне
);

echo $oWorker->getWorkerData();