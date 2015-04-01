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

/**
 * Этот файл нужен только если не используется composer
 * https://getcomposer.org/
 */
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
  throw new Exception('Requires PHP version 5.4 or higher.');
}
/**
 * Регистрация автозагрузчика для классов Workers
 *
 * @param string $sClass имя класса
 * @return void
 */
spl_autoload_register(function($sClass) {
  $sPrefix = 'Workers\\';

  $sBaseDir = __DIR__ . '/src/Workers/';

  $iLenght = strlen($sPrefix);

  if (strncmp($sPrefix, $sClass, $iLenght) !== 0)
    return;

  $sClass = substr($sClass, $iLenght);

  $sFilePath = $sBaseDir . str_replace('\\', '/', $sClass) . '.php';

  if (file_exists($sFilePath))
    require $sFilePath;
});