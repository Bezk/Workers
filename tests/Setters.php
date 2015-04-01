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

 ini_set('display_errors', 'on');

date_default_timezone_set('Europe/Moscow');
require_once '../autoloader.php';

use Workers\Worker;

$aWorkersData = array(
    '1 worker' => 'Data 1',
    '2 worker' => 'Data 2',
    '3 worker' => 'Data 3'
);

$oWorker = new Worker(false);
$oWorker->setUnhookConsole(true);
$oWorker->setLoopedMode(false);
$oWorker->setLaunchInstancesDelay(5);
$oWorker->setLaunchInstancesDelay(5);
$oWorker->setDelayBetweenLaunchAttempts(2000);
$oWorker->setMaxExecutionTime(15);
$oWorker->setVerboseMode(true);
$oWorker->setWorkersData($aWorkersData);
$oWorker->setNumberOfWorkers(5);
$oWorker->setIOInConsole(false);
$oWorker->setTempDirectory('./SettersLogs');
$oWorker->setLogsDirectory('./SettersLogs');
$oWorker->setWorkersPriority(10);

$oWorker->run();

var_dump($oWorker);