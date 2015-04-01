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
namespace Workers;

/**
 * Class Worker
 * @package Bezk
 * @author Анатолий Березняк <bereznyak@me.com>
 * @homepage http://unrealmac.ru
 * @link https://github.com/Bezk/Workers
 */

final class Worker
{
    /**
     * @const string номер версии скрипта
     */
    const VERSION = '1.0.0-beta';

    /**
     * @var int PID текущего процесса
     */
    private $iPID;

    /**
     * @var int PID родительского процесса
     */
    private $iParentPID;

    /**
     * @var int PID процесса, при завершении которого сработает
     * родительский деструктор
     */
    private $iParentDestructorPID;

    /**
     * @var string путь к временной директории
     */
    private $sTempDirectory;

    /**
     * @var string путь к директории для логов
     */
    private $sLogsDirectory;

    /**
     * @var int приоритет процесса
     */
    private $iWorkerPriority = 0;

    /**
     * @var string путь к PID файлу
     */
    private $sPIDFilePath;

    /**
     * @var string префикс технических файлов (PID, логи)
     */
    private $sFilesBasename;

    /**
     * @var int максимальное количество одновременно работающих процессов
     */
    private $iNumberOfWorkers = 1;

    /**
     * @var int лимит на время исполнения дочернего процесса (в секундах)
     */
    private $iMaxExecutionTimeInSeconds = 0;

    /**
     * @var bool флаг остановки демона
     */
    private $bIsStop = false;

    /**
     * @var array массив работающих воркеров
     */
    private $aInstances = array();

    /**
     * @var callable функция с программой воркера
     */
    private $cExecutingFunction = null;

    /**
     * @var callable функция с деструктором родительского процесса
     */
    private $cDestructorFunction = null;

    /**
     * @var callable функция с деструктором воркера
     */
    private $cWorkerDestructorFunction = null;

    /**
     * @var callable функция выполняемая по завершению родительского процесса
     */
    private $cShutdownFunction = null;

    /**
     * @var int время первичной задержки для асинхронного запуска в секундах
     */
    private $iFirstDelayBetweenLaunch = 0;

    /**
     * @var int номер процесса
     */
    private $iNumberWorker = 0;

    /**
     * @var int порядковый номер процесса
     */
    private $iSerialNumberWorker = 0;

    /**
     * @var int время задержки между попытками запуска новых воркеров
     * в милисекундах
     * (при достижении максимального количества работающих воркеров)
     */
    private $iDelayBetweenLaunch = 1000;

    /**
     * @var array массив с данными для воркеров
     */
    private $aWorkers = array();

    /**
     * @var array ключи воркеров
     */
    private $aWorkersNames = array();

    /**
     * @var array данные воркеров
     */
    private $aWorkersData = array();

    /**
     * @var bool флаг цикличной работы
     */
    private $bIsLooped = true;

    /**
     * @var bool флаг привязки к консоли
     */
    private $bHookedConsole = false;

    /**
     * @var bool флаг вывода в консоль
     */
    private $bSTDInConsole = false;

    /**
     * @var bool флаг подробного вывода
     */
    private $bVerbose = false;

    /**
     * @var bool флаг последовательной (один за одним) работы
     */
    private $bInSequence = false;

    /**
     * @var array массив мета информации процессов
     */
    private $aMeta = array();

    /**
     * @var mixed переменная с данными, которые могут использоваться в воркере
     */
    private $mInstanceData = null;

    /**
     * @var bool флаг "запущенности" скрипта
     */
    private $bIsActive = false;

    /**
     * @var bool флаг первичного запуска метода run
     */
    private $bIsFirstRun = true;

    /**
     * @var bool флаг быстрого запуска
     */
    private $bFastRun = true;

    /**
     * @var bool флаг запуска метода run
     */
    private $bIsRun = false;

    /**
     * @var array массив воркеров завершивших работу
     */
    private $aStopped = array();

    /**
     * @var array массив воркеров завершивших работу
     */
    private $bVerboseInOUT = false;

    /**
     * @var array ключ воркера
     */
    private $sInstanceName = null;

    /**
     * @var array массив с ресурсами на потоки ввода/вывода
     */
    private $STD = array(
        'IN'    => STDIN,
        'OUT'   => STDOUT,
        'ERR'   => STDERR
    );

    final public function __construct(
                    $bFastRun                   = null,
                    $aWorkersData               = null,
        callable    $cExecutingFunction         = null,
        callable    $cDestructorFunction        = null,
        callable    $cWorkerDestructorFunction  = null,
        callable    $cShutdownFunction          = null,
                    $sTempDirectory             = null,
                    $sLogsDirectory             = null,
                    $iNumberOfWorkers           = null,
                    $iWorkerPriority            = null,
                    $iMaxExecutionTimeInSeconds = null,
                    $iFirstDelayBetweenLaunch   = null,
                    $iDelayBetweenLaunch        = null,
                    $bIsLooped                  = null,
                    $bInSequence                = null,
                    $bHookedConsole             = null,
                    $bSTDInConsole              = null,
                    $bVerbose                   = null,
                    $bVerboseInOUT              = null
    )

    {
        declare(ticks=1);

        if (isset($bInSequence))
            $this->bInSequence = (bool) $bInSequence;

        if ($cExecutingFunction)
            $this->cExecutingFunction = (string) $cExecutingFunction;

        $this->iPID = getmypid();
        $this->iParentPID = $this->iPID;

        $this->sTempDirectory = $sTempDirectory
            ? (string) $sTempDirectory
            : sys_get_temp_dir();

        $this->sLogsDirectory = $sLogsDirectory
            ? (string) $sLogsDirectory
            : $this->sTempDirectory;

        if ($iFirstDelayBetweenLaunch)
            $this->iFirstDelayBetweenLaunch = $iFirstDelayBetweenLaunch;

        $this->sFilesBasename = basename($_SERVER['PHP_SELF']);

        $this->sPIDFilePath = $this->sTempDirectory
                            . DIRECTORY_SEPARATOR
                            . $this->sFilesBasename
                            . '.pid';

        if (isset($bHookedConsole))
            $this->bHookedConsole = (bool) $bHookedConsole;

        if (isset($bSTDInConsole))
            $this->bSTDInConsole = (bool) $bSTDInConsole;

        if (isset($bIsLooped))
            $this->bIsLooped = (bool) $bIsLooped;

        if ($cDestructorFunction)
            $this->cDestructorFunction = (string) $cDestructorFunction;

        if ($cWorkerDestructorFunction)
            $this->cWorkerDestructorFunction = (string) $cWorkerDestructorFunction;

        if ($cShutdownFunction)
            $this->cShutdownFunction = (string) $cShutdownFunction;

        if ($iMaxExecutionTimeInSeconds)
            $this->iMaxExecutionTimeInSeconds = (int) $iMaxExecutionTimeInSeconds;

        if ($iWorkerPriority)
            $this->iWorkerPriority = (int) $iWorkerPriority;

        if (isset($bVerbose))
            $this->bVerbose = (bool) $bVerbose;

        if ($iDelayBetweenLaunch)
            $this->iDelayBetweenLaunch = (int) $iDelayBetweenLaunch;

        $this->bIsActive = $this->isActive();

        if ($aWorkersData) {
            // выставляем количество воркеров
            $this->iNumberOfWorkers = count((array) $aWorkersData);

            // массив имен демонов => значений для воркеров
            $this->aWorkers = (array) $aWorkersData;

            // массив имен демонов
            $this->aWorkersNames = array_keys((array) $aWorkersData);

            // массив значений для воркеров
            $this->aWorkersData = array_values((array) $aWorkersData);
        }

        if ($iNumberOfWorkers) {
            if ($aWorkersData) {
                $this->writeInLog(
                    $this->STD['OUT'],
                    'Ignore option "The numbers of processes" because set up'
                    . ' option "Workers data"'
                );
            } else {
                $this->iNumberOfWorkers = (int) $iNumberOfWorkers;

                for ($i = 1; $i <= $this->iNumberOfWorkers; $i++)
                    $this->aWorkers[] = null;

                // массив имен демонов
                $this->aWorkersNames = array_keys((array) $this->aWorkers);

                // массив значений для воркеров
                $this->aWorkersData = array_values((array) $this->aWorkers);
            }
        }

        if (isset($bVerboseInOUT))
            $this->bVerboseInOUT = (bool) $bVerboseInOUT;

        if (isset($bFastRun))
            $this->bFastRun = $bFastRun;

        $this->rVerboseStream = $this->bVerboseInOUT
            ? $this->STD['OUT']
            : $this->STD['ERR'];

        $this->aStopped = $this->getInstancesKeys($this->aWorkersNames);

        // быстрый старт
        if ($this->bFastRun)
            $this->run();
    }

    /**
     * Деструкторы родительского и дочернего процессов
     *
     * @return void
     */
    final public function __destruct()
    {
        switch (getmypid()) {
            // деструктор родительского процесса
            case $this->iParentDestructorPID:
                $this->setStopMeta();

                $this->writeInLog($this->rVerboseStream, 'Stopped');

                if ($this->bVerbose)
                    $this->writeMeta();

                $this->writeInLog(
                    $this->rVerboseStream,
                    '-----------------------------------------'
                );

                if ($this->cDestructorFunction)
                    call_user_func($this->cDestructorFunction);

                unlink($this->sPIDFilePath);
                break;

            case $this->iParentPID:
                break;

            // деструктор дочерних процессов
            default:
                $sContent = $this->getOutput(ob_get_contents());
                ob_end_clean();

                echo $sContent;

                $this->setStopMeta();

                if ($this->bVerbose) {
                    $this->writeInLog($this->rVerboseStream, 'Stopped');

                    $this->writeMeta();

                    $this->writeInLog(
                        $this->rVerboseStream,
                        '-----------------------------------------'
                    );
                }

                if ($this->cWorkerDestructorFunction)
                    call_user_func($this->cWorkerDestructorFunction);
        }
    }

    /**
     * Класс запрещено клонировать
     *
     * @return void
     */
    final private function __clone()
    {

    }

    /**
     * Выводит дебаг-инфо
     *
     * @return array
     */
    public function __debugInfo()
    {
        return array(
            'instance' => array(
                'name'
                    => $this->sInstanceName,
                'data'
                    => $this->mInstanceData,
                'meta'
                    => $this->aMeta,
                'serial number worker'
                    => $this->iSerialNumberWorker,
                'number worker'
                    => $this->iNumberWorker,
                'PID'
                    => $this->iPID,
                'parent PID'
                    => $this->iParentPID
            ),
            'common' => array(
                'version'
                    => self::VERSION,
                'parent PID'
                    => $this->iParentPID,
                'parent PID in file'
                    => $this->getPIDFromFile(),
                'PID file'
                    => realpath($this->sPIDFilePath),
                'fast run'
                    => $this->bFastRun,
                'workers'
                    => $this->aWorkers,
                'workers names'
                    => $this->aWorkersNames,
                'workers data'
                    => $this->aWorkersData,
                'meta'
                    => $this->aMeta,
                'workers program function'
                    => $this->cExecutingFunction,
                'parent destructor function'
                    => $this->cDestructorFunction,
                'worker destructor function'
                    => $this->cWorkerDestructorFunction,
                'shutdown function'
                    => $this->cShutdownFunction,
                'files prefix'
                    => $this->sFilesBasename,
                'temporary directory'
                    => realpath($this->sTempDirectory),
                'logs directory'
                    => realpath($this->sLogsDirectory),
                'numbers of workers'
                    => $this->iNumberOfWorkers,
                'worker priority'
                    => $this->iWorkerPriority,
                'maximum execution time limit'
                    => $this->iMaxExecutionTimeInSeconds,
                'first delay between launch workers'
                    => $this->iFirstDelayBetweenLaunch,
                'delay between attempts launch workers'
                    => $this->iDelayBetweenLaunch,
                'looped mode'
                    => $this->bIsLooped,
                'in sequence mode'
                    => $this->bInSequence,
                'is unhook console'
                    => !$this->bHookedConsole,
                'IO in console'
                    => $this->bSTDInConsole,
                'IO'
                    => $this->STD,
                'verbose mode'
                    => $this->bVerbose,
                'is verbose in out stream'
                    => $this->bVerboseInOUT
            )
        );
    }

    /**
     * Запускает работу демона (если объект вызывается как функция)
     *
     * @return void
     */
    public function __invoke()
    {
        $this->run();
    }

    /**
     * Приостанавливает работу демона
     *
     * @return void
     */
    public function __sleep()
    {
        $this->iNumberOfWorkers = 0;

        foreach ($this->aInstances as $iPID => $sValue) {
            pcntl_waitpid($iPID, $iStatus);
            if ($iStatus === 0) {
                $this->aStopped[$this->aInstances[$iPID]]
                    = $this->aInstances[$iPID];

                unset($this->aInstances[$iPID]);
            }
        }

        pcntl_sigwaitinfo(array(SIGCONT), $aInfo);
        $this->iNumberOfWorkers = count($this->aWorkers);
    }

    /**
     * Возобновляет работу демона после остановки
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->bIsStop = false;
        $this->iNumberOfWorkers = count($this->aWorkers);
    }

    /**
     * Возвращает строковое представление объекта
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->sFilesBasename;
    }

    /**
     * Отвязывает демона от консоли
     *
     * @return boolean
     */
    private function unhookConsole()
    {
        if ($this->bVerbose) {
            $this->writeInLog(
                $this->rVerboseStream,
                'Unhook console: Fork process and kill parent, set up children'
                . ' as parent...'
            );
        }

        $iChildPID = pcntl_fork();

        if ($iChildPID === -1) {
            $this->writeInLog($this->STD['ERR'], 'Fork ended with error');
            return false;
        }

        // выходим из родительского (привязанного к консоли) процесса
        if ($iChildPID)
            exit(0);

        // продолжим работу после завершения родительского процесса
        $bResult = true;
        while ($bResult)
            $bResult = posix_kill($this->iPID, 0);

        // делаем дочерний процесс основным
        $bResult = posix_setsid();

        if ($bResult === -1)
            throw new WorkerException(
                'Set up current process a session leader ended with error'
            );

        if ($this->bVerbose)
            $this->writeInLog($this->rVerboseStream, "\t done");

        return true;
    }

    /**
     * Запускает работу демона
     *
     * @return void
     */
    public function run()
    {
        $this->aMeta['time']['start'] = round(microtime(true), 2);

        // проверка окружения
        $this->checkWorkflow();

        $this->writeInLog(
            $this->STD['OUT'],
            $this->bIsLooped ? 'Looped mode' : 'One-off mode'
        );

        $this->bIsRun = true;
        $this->bIsFirstRun = false;

        // установка кастомных потоков IO
        $this->setStreamsIO();

        // отвязываемся от консоли
        if (!$this->bHookedConsole)
            $this->unhookConsole();

        // назначаем функцию завершения
        if ($this->cShutdownFunction)
            register_shutdown_function($this->cShutdownFunction);

        // необходимо обновить данные после отвязки от консоли
        $this->iPID = getmypid();
        $this->iParentPID = $this->iPID;
        $this->iParentDestructorPID = $this->iPID;

        // помечаем родительский процесс
        if (function_exists('cli_set_process_title')
            && $_SERVER['TERM_PROGRAM'] !== 'Apple_Terminal'
        ) {
            cli_set_process_title($this->sFilesBasename . '.parent');
        }

        if ($this->bVerbose)
            $this->writeInLog($this->rVerboseStream, 'Write PID...');

        // фиксируем PID
        $this->writePIDInFile($this->iPID);

        if ($this->bVerbose)
            $this->writeInLog($this->rVerboseStream, "\t write PID " . $this->iPID);

        if ($this->bVerbose)
            $this->writeInLog($this->rVerboseStream, 'Set up signal handler...');

        // устанавливаем обработку сигналов
        $this->setSignalHandlers();

        if ($this->bVerbose)
            $this->writeInLog($this->rVerboseStream, "\t set");

        while (!$this->bIsStop) {
            $this->iSerialNumberWorker++;
            $this->iNumberWorker++;

            if ($this->iNumberWorker > $this->iNumberOfWorkers) {

                // если демон не зациклен, выход
                if (!$this->bIsLooped)
                    break;

                // сбрасываем порядковый номер дочернего процесса
                $this->iNumberWorker = 1;
            }

            // запущено максимальное количество дочерних процессов, ждем завершения
            while(count($this->aStopped) === 0)
                usleep($this->iDelayBetweenLaunch);

            $this->sInstanceName = array_values($this->aStopped)[0];
            unset($this->aStopped[$this->sInstanceName]);

            // распределяем деление, чтобы не грузить процессор
            if (
                ($this->iSerialNumberWorker > 1
                && $this->iSerialNumberWorker <= $this->iNumberOfWorkers)

                && $this->iFirstDelayBetweenLaunch
            ) {
                if ($this->bVerbose) {
                    $this->writeInLog(
                        $this->rVerboseStream,
                        'Delay between launching instances: '
                        . $this->iFirstDelayBetweenLaunch . ' seconds'
                    );
                }

                sleep($this->iFirstDelayBetweenLaunch);
            }

            $iChildPID = pcntl_fork();

            if ($iChildPID === -1) {
                throw new WorkerException('Create new instance ended with error');
            } elseif ($iChildPID) {
                if ($this->bInSequence)
                    pcntl_wait($sStatus);

                if ($this->bVerbose) {
                    $this->writeInLog(
                        $this->rVerboseStream,
                        'New instance: ' . $this->sInstanceName . ' PID: ' . $iChildPID
                    );
                }

                unset($this->aStopped[$this->sInstanceName]);
                $this->aInstances[$iChildPID] = $this->sInstanceName;
            } else {
                $this->worker();
            }

            // выходим из цикла в дочернем процессе
            if ($this->iPID !== $this->iParentPID)
                break;

            $this->checkZombie();
        }

        if ($this->iPID === $this->iParentPID) {
            if ($this->bVerbose)
                $this->writeInLog($this->rVerboseStream, 'No work. Stopping');

            if ($this->bVerbose)
                $this->writeInLog($this->rVerboseStream, '   waiting…');

            while (count($this->aInstances))
                usleep($this->iDelayBetweenLaunch);

            $this->stop();

            // не будем продолжать работу родительского процесса
            exit(0);
        }
    }

    private function stop()
    {
        $this->bIsStop = true;

        while ($this->aInstances) {
            foreach ($this->aInstances as $iPID => $mValue) {
                $iPID = pcntl_waitpid($iPID, $iStatus, WNOHANG|WUNTRACED);

                if ($iPID > 0) {
                    if (isset($this->aInstances[$iPID])) {
                        if ($this->bVerbose) {
                            $this->writeInLog(
                                $this->rVerboseStream,
                                'End instance: ' . $this->aInstances[$iPID]
                                . ' PID: ' . $iPID
                            );
                        }

                        $this->aStopped[$this->aInstances[$iPID]]
                            = $this->aInstances[$iPID];

                        unset($this->aInstances[$iPID]);
                    }
                }
            }
        }
    }

    /**
     * Выполняет отлов зомби-процессов
     *
     * @return void
     */
    private function checkZombie()
    {
        foreach ($this->aInstances as $iPID => $mValue) {
            $iPID = pcntl_waitpid($iPID, $iStatus, WNOHANG|WUNTRACED);

            if ($iPID > 0) {
                if (isset($this->aInstances[$iPID])) {
                    if ($this->bVerbose) {
                        $this->writeInLog(
                            $this->rVerboseStream,
                            'End instance: ' . $this->aInstances[$iPID]
                            . ' PID: ' . $iPID
                        );
                    }

                    $this->aStopped[$this->aInstances[$iPID]]
                        = $this->aInstances[$iPID];

                    unset($this->aInstances[$iPID]);
                }
            }
        }
    }

    /**
     * Выполняет проверку окружения
     *
     * @return void
     */
    public function checkWorkflow()
    {
        // процесс не должен запускаться в нескольких экземплярах
        if ($this->bIsActive)
            throw new WorkerException('Already running');

        // нельзя дважды запускать метод run
        if (!$this->bIsFirstRun)
            throw new WorkerException('Method "run" called only once');

        if (!function_exists('pcntl_fork')) {
            throw new WorkerException(
                'PCNTL: Process control doesn\'t support.'
                . ' Compile PHP with --enable-pcntl'
            );
        }

        if (!function_exists('pcntl_wait') && $this->bInSequence) {
            throw new WorkerException(
                'PCNTL: Function pcntl_wait doesn\'t support.'
                . ' Function "In sequence" don\'t work'
            );
        }

        if (function_exists('pcntl_fork') && !function_exists('pcntl_sigwaitinfo')) {
            $this->writeInLog(
                $this->STD['OUT'],
                'PCNTL: OS X doesn\'t support function pcntl_sigwaitinfo.'
                . ' Send SIGTSTP or SIGSTOP return error of undefined function'
            );
        }

        if ($this->iWorkerPriority < -20 || $this->iWorkerPriority > 20) {
            throw new WorkerException(
                'Incorrect value for option "workers priority".'
                . ' Set value range from -20 to 20.');
        }

        if (!is_dir($this->sTempDirectory) || !is_writable($this->sTempDirectory)) {
            throw new WorkerException(
                'Incorrect value for option "temp directory" or directory'
                . ' not allowed write.'
            );
        }

        if (!is_dir($this->sLogsDirectory) || !is_writable($this->sLogsDirectory)) {
            throw new WorkerException(
                'Incorrect value for option "logs directory" or directory'
                . ' not allowed write.'
            );
        }
    }

    /**
     * Выполняет работу воркера
     *
     * @return void
     */
    private function worker()
    {
        $this->iPID = getmypid();
        $this->aMeta['time']['start'] = round(microtime(true), 2);

        // ключ для вызова в функции пользовательской
        $this->mInstanceData = (bool) $this->aWorkersData
            ? $this->aWorkersData[$this->iNumberWorker - 1]
            : null;

        if (function_exists('cli_set_process_title')
            && isset($_SERVER['TERM_PROGRAM'])
            && $_SERVER['TERM_PROGRAM'] !== 'Apple_Terminal'
        ) {
            cli_set_process_title($this->sFilesBasename . '.' . $this->sInstanceName);
        }

        $this->setStreamsIO($this->sInstanceName);

        if ($this->bVerbose)
            $this->writeInLog($this->rVerboseStream, 'PID: ' . $this->iPID);

        // установка приоритета на процесс
        if ($this->iWorkerPriority) {
            if ($this->bVerbose) {
                $this->writeInLog(
                    $this->rVerboseStream,
                    'Set process priority equal ' . $this->iWorkerPriority
                );
            }

            $this->setPriority($this->iWorkerPriority);
        }

        // лимит на время исполнения дочернего процесса
        if ($this->iMaxExecutionTimeInSeconds) {
            if ($this->bVerbose) {
                $this->writeInLog(
                    $this->rVerboseStream,
                    'Set process lifetime ' . $this->iMaxExecutionTimeInSeconds
                    . ' seconds'
                );
            }

            // считает время работы скрипта
            set_time_limit($this->iMaxExecutionTimeInSeconds);

            // считает время исполнения вне скрипта (потоки, коннекты, sleep, etc)
            pcntl_alarm($this->iMaxExecutionTimeInSeconds);
        }

        if ($this->bVerbose)
            $this->writeInLog($this->rVerboseStream, 'Started');

        // перехватываем вывод
        ob_start();

        // запускаем функцию
        if ($this->cExecutingFunction) {
            call_user_func($this->cExecutingFunction, $this->mInstanceData);

        	// успешно выходим
            exit(0);
        }
    }

    /**
     * Обработчик сигналов
     *
     * @param  integer  $iSignalNumber
     * @param  integer  $iPID
     * @param  integer  $iPID
     * @return void
     */
    public function sigHandler($iSignalNumber, $iPID = null, $iStatus = null)
    {
        switch($iSignalNumber) {
            // при получении сигнала завершения работы устанавливаем флаг
            case SIGTERM:
            case SIGHUP:
            case SIGINT;
                $this->stop();
                break;
            // сигнал от дочернего процесса
            case SIGCHLD:
                $iPID = pcntl_waitpid(-1, $iStatus, WNOHANG|WUNTRACED);

                if ($iPID == -1) {
                    $this->writeInLog(
                        $this->STD['ERR'],
                        'Error receive ' . SIGCHLD . ' (sigchld) signal'
                    );

                    return true;
                }

                // юниксовый код выхода
                $iExitCode = pcntl_wexitstatus($iStatus);

                // закончим всю работу, если воркеры выходят с фатальной ошибкой
                if ($iExitCode === 255)
                    $this->stop();

                if (isset($this->aInstances[$iPID])) {
                    if ($this->bVerbose) {
                        $this->writeInLog(
                            $this->rVerboseStream,
                            'End instance: ' . $this->aInstances[$iPID]
                            . ' PID: ' . $iPID
                        );
                    }

                    $this->aStopped[$this->aInstances[$iPID]]
                        = $this->aInstances[$iPID];
                    unset($this->aInstances[$iPID]);
                }
                break;
            case SIGALRM:
            case SIGVTALRM:
                // код выхода дочернего процесса
                exit(1);
                echo 'Child exit on timeout' . PHP_EOL;
                break;
            case SIGTSTP:
            case SIGSTOP:
                $this->__sleep();
                break;
            case SIGCONT:
                $this->__wakeup();
                break;
        }

        return true;
    }

    /**
     * Возвращает флаг, является ли PID записанный в файле активным процессом
     *
     * @return boolean
     */
    private function isActive()
    {
        // очищаем файловый кеш для PID файла
        clearstatcache(true, $this->sPIDFilePath);

        if (file_exists($this->sPIDFilePath) && is_readable($this->sPIDFilePath)) {
            $iPID = $this->getPIDFromFile();

    		return (is_numeric($iPID) && posix_kill($iPID, 0) === true)
    		    ? true
    		    : false;
        }

        return false;
    }

    /**
     * Возвращает флаг, является ли массив ассоциативным
     *
     * @param  array    $aArray
     * @return boolean
     */
    private function isAssoc(array $aArray)
    {
        return array_keys($aArray) !== range(0, count($aArray) - 1);
    }

    /**
     * Записывает в файл PID процесса
     *
     * @param  integer  $iPID
     * @return boolean
     */
    private function writePIDInFile($iPID)
    {
        return file_put_contents($this->sPIDFilePath, $iPID, LOCK_EX);
    }

    /**
     * Записывает строку в файл, возвращает количество записанных байт
     *
     * @param  resource $rLog
     * @param  string   $sMessage
     * @return integer
     */
    public function writeInLog($rLog, $sMessage)
    {
        return fwrite(
            $rLog, '[' . date('d-M-Y H:i:s e') . '] ' . $sMessage . PHP_EOL
        );
    }

    /**
     * Пишет в лог мета-информацию
     *
     * @return void
     */
    private function writeMeta()
    {
        $this->writeInLog(
            $this->rVerboseStream,
            'Execution time: ' . round($this->aMeta['execution_time'], 2)
            . ' seconds'
        );

        $this->writeInLog($this->rVerboseStream,
            'Load average: '
                . round($this->aMeta['load_average'][0], 2)
                . ', ' . round($this->aMeta['load_average'][1], 2)
                . ', ' . round($this->aMeta['load_average'][2], 2)
        );
    }

    /**
     * Устанавливает флаг привязки к консоли
     *
     * @return boolean
     */
    public function setUnhookConsole($bHookedConsole) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "unhook console" because workers already run'
            );

            return false;
        }

        $this->bHookedConsole = (bool) !$bHookedConsole;

        return true;
    }

    /**
     * Устанавливает флаг цикличности
     *
     * @return boolean
     */
    public function setLoopedMode($bIsLooped) {
        $this->bIsLooped = (bool) $bIsLooped;

        return true;
    }

    /**
     * Устанавливает задержку для асинхронного запуска
     *
     * @return boolean
     */
    public function setLaunchInstancesDelay($iFirstDelayBetweenLaunch) {
        $this->iFirstDelayBetweenLaunch = $iFirstDelayBetweenLaunch;

        return true;
    }

    /**
     * Устанавливает задержку между повторными попытками запуска новых воркеров
     *
     * @return boolean
     */
    public function setDelayBetweenLaunchAttempts($iDelayBetweenLaunch) {
        $this->iDelayBetweenLaunch = $iDelayBetweenLaunch;

        return true;
    }

    /**
     * Устанавливает максимальное время работы воркеров в секундах
     *
     * @return boolean
     */
    public function setMaxExecutionTime($iMaxExecutionTimeInSeconds) {
        $this->iMaxExecutionTimeInSeconds = $iMaxExecutionTimeInSeconds;

        return true;
    }

    /**
     * Устанавливает мод подробного вывода
     *
     * @return boolean
     */
    public function setVerboseMode($bVerbose) {
        $this->bVerbose = $bVerbose;

        return true;
    }

    /**
     * Устанавливает количество воркеров
     *
     * @return boolean
     */
    public function setNumberOfWorkers($iWorkers) {
        if ($this->aWorkers) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "The numbers of processes" because set up option'
                . ' "Workers data"'
            );

            return false;
        } else {
            $this->iNumberOfWorkers = (int) $iWorkers;

            for ($i = 1; $i <= $this->iNumberOfWorkers; $i++)
                $this->aWorkers[] = null;

            // массив имен демонов
            $this->aWorkersNames = array_keys((array) $this->aWorkers);

            // массив значений для воркеров
            $this->aWorkersData = array_values((array) $this->aWorkers);

            $this->aStopped = $this->getInstancesKeys($this->aWorkersNames);

            return true;
        }
    }

    /**
     * Устанавливает данные для проброса в воркер
     *
     * @return boolean
     */
    public function setWorkersData($aWorkersData) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "workers data" because workers already run'
            );

            return false;
        }

        if ($this->iNumberOfWorkers)
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "The numbers of processes" because set up option'
                . ' "Workers data"'
            );

        // выставляем количество воркеров
        $this->iNumberOfWorkers = count((array) $aWorkersData);

        // массив имен демонов => значений для воркеров
        $this->aWorkers = (array) $aWorkersData;

        // массив имен демонов
        $this->aWorkersNames = array_keys((array) $aWorkersData);

        // массив значений для воркеров
        $this->aWorkersData = array_values((array) $aWorkersData);

        $this->aStopped = $this->getInstancesKeys($this->aWorkersNames);

        return true;
    }

    /**
     * Устанавливает функцию, в которой находится программа воркеров
     *
     * @return void
     */
    public function setWorkersFunction(callable $cExecutingFunction) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "workers function" because workers already run'
            );

            return false;
        }

        $this->cExecutingFunction = (string) $cExecutingFunction;

        return true;
    }

    /**
     * Устанавливает функцию деструктора родительского процесса
     *
     * @return void
     */
    public function setDestructorFunction($cDestructorFunction) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "destructor function" because workers already run'
            );

            return false;
        }

        $this->cDestructorFunction = (string) $cDestructorFunction;

        return true;
    }

    /**
     * Устанавливает функцию деструктора дочерних процессов
     *
     * @return void
     */
    public function setWorkercDestructorFunction($cWorkerDestructorFunction) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "workers destructor function" because workers'
                . ' already run'
            );

            return false;
        }

        $this->cWorkerDestructorFunction = (string) $cWorkerDestructorFunction;

        return true;
    }

    /**
     * Устанавливает вывод потоков ввода/вывода в консоль
     *
     * @return void
     */
    public function setIOInConsole($bSTDInConsole) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "set IO in console" because workers already run'
            );

            return false;
        }

        $this->bSTDInConsole = (bool) $bSTDInConsole;
    }

    /**
     * Устанавливает временную директорию
     *
     * @return boolean
     */
    public function setTempDirectory($sTempDirectory) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "set temp directory" because workers already run'
            );

            return false;
        }

        $this->sTempDirectory = $sTempDirectory;

        $this->sPIDFilePath = $this->sTempDirectory
                            . DIRECTORY_SEPARATOR
                            . $this->sFilesBasename
                            . '.pid';

        return true;
    }

    /**
     * Устанавливает директорию логов
     *
     * @return boolean
     */
    public function setLogsDirectory($sLogsDirectory) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "set logs directory" because workers already run'
            );

            return false;
        }

        $this->sLogsDirectory = $sLogsDirectory;

        return true;
    }

    /**
     * Устанавливает приоритет для воркерных процессов
     *
     * @return void
     */
    public function setWorkersPriority($iWorkerPriority) {
        if ($this->bIsRun) {
            $this->writeInLog(
                $this->STD['OUT'],
                'Ignore option "set workers priority" because workers already run'
            );

            return false;
        }

        $this->iWorkerPriority = (int) $iWorkerPriority;

        return true;
    }

    /**
     * Устанавливает обработчики сигналов
     *
     * @return void
     */
    private function setSignalHandlers() {
        pcntl_signal(SIGTERM,   array(&$this, "sigHandler"));   // сигнал завершения работы
        pcntl_signal(SIGHUP,    array(&$this, "sigHandler"));   // закрытия консоли
        pcntl_signal(SIGINT,    array(&$this, "sigHandler"));   // ctrl-c c консоли
        pcntl_signal(SIGALRM,   array(&$this, "sigHandler"));   // alarm
        pcntl_signal(SIGVTALRM, array(&$this, "sigHandler"));   // alarm
        pcntl_signal(SIGCHLD,   array(&$this, "sigHandler"));   // сигналы завершения дочернего процессв
        pcntl_signal(SIGTSTP,   array(&$this, "sigHandler"));   // сигналы остановки с консоли ctrl-z
        pcntl_signal(SIGCONT,   array(&$this, "sigHandler"));   // продолжение работы
    }

    /**
     * Переопределяет потоки ввода/вывода
     *
     * @param  $sInstanceName
     * @return void
     */
    private function setStreamsIO($sInstanceName = null)
    {
        if (!$this->bSTDInConsole) {

            if (!$sInstanceName)
                $this->writeInLog($this->STD['OUT'], 'Reassignment STD streams');

            ini_set('display_errors', 'off');
            ini_set(
                'error_log',
                $this->sLogsDirectory
                    . DIRECTORY_SEPARATOR
                    . $this->sFilesBasename
                    . '.error.log'
            );

            $sInstanceName = ($sInstanceName)
                ? '.' . $sInstanceName
                : null;

            // закрываем потоки
            fclose($this->STD['IN']);
            fclose($this->STD['OUT']);
            fclose($this->STD['ERR']);

            // переопределяем
            $this->STD['IN'] = fopen(
                '/dev/null',
                'r'
            );

            $this->STD['OUT'] = fopen(
                $this->sLogsDirectory
                    . DIRECTORY_SEPARATOR
                    . $this->sFilesBasename
                    . $sInstanceName
                    . '.application.log',
                'ab'
            );

            $this->STD['ERR'] = fopen(
                $this->sLogsDirectory
                    . DIRECTORY_SEPARATOR
                    . $this->sFilesBasename
                    . $sInstanceName
                    . '.daemon.log',
                'ab'
            );

            // переобпределяем зависимое свойство
            $this->rVerboseStream = $this->bVerboseInOUT
                ? $this->STD['OUT']
                : $this->STD['ERR'];
        }
    }

    /**
     * Устанавливает приоритет для дочерних процессов
     *
     * @param  integer  $iWorkerPriority
     * @return boolean
     */
    private function setPriority($iWorkerPriority)
    {
        return $iWorkerPriority
            ? pcntl_setpriority($iWorkerPriority)
            : false;
    }

   /**
     * Записывает начальную мета-информацию
     *
     * @return void
     */
    private function setStartMeta()
    {
        $this->aMeta['time']['start'] = round(microtime(true), 2);
    }

    /**
     * Записывает конечную мета-информацию
     *
     * @return void
     */
    private function setStopMeta()
    {
        $this->aMeta['time']['stop'] = round(microtime(true), 2);

        $this->aMeta['execution_time']
            = $this->aMeta['time']['stop'] - $this->aMeta['time']['start'];

        $this->aMeta['load_average'] = sys_getloadavg();
    }

    /**
     * Возвращает имена инстансов
     *
     * @return array
     */
    private function getInstancesKeys()
    {
        $bIsAssoc = $this->isAssoc($this->aWorkers);

        $aKeys = array();

        foreach ($this->aWorkersNames as $mKey)
            $aKeys[$bIsAssoc ? $mKey : $mKey + 1] = $bIsAssoc
                ? $mKey
                : $mKey + 1;

        return $aKeys;
    }

    /**
     * Обрабатывает строки, добавляя дату в начало строки
     *
     * @param  string   $sMessage
     * @return string
     */
    private function getOutput($sMessage) {
        $aOutput = explode(PHP_EOL, $sMessage);

        $aOUT = array();

        foreach ($aOutput as $sRow) {
            $aOUT[] = '[' . date('d-M-Y H:i:s e') . '] ' . rtrim($sRow);
        }

        return implode(PHP_EOL, $aOUT) . PHP_EOL;
    }

    /**
     * Возвращает PID из файла
     *
     * @return integer
     */
    private function getPIDFromFile()
    {
        return (int) trim(file_get_contents($this->sPIDFilePath));
    }

    /**
     * Возвращает PID родительского процесса
     *
     * @return integer
     */
    private function getPID()
    {
        return (int) $this->iParentPID;
    }

    /**
     * Возвращает данные для использования в воркере
     *
     * @return mixed
     */
    public function getWorkerData()
    {
        return $this->mInstanceData;
    }

    /**
     * Возвращает путь к каталогу временных файлов
     *
     * @return string
     */
    public function getTempDirectory()
    {
        return $this->sTempDirectory;
    }

    /**
     * Возвращает путь к каталогу логов
     *
     * @return string
     */
    public function getLogsDirectory()
    {
        return $this->sLogsDirectory;
    }

    /**
     * Возвращает префикс файлов (имя скрипта в котором работает демон)
     *
     * @return string
     */
    public function getFilesBasename()
    {
        return $this->sFilesBasename;
    }

    /**
     * Возвращает путь к PID файлу
     *
     * @return string
     */
    public function getPIDFilePath()
    {
        return $this->sPIDFilePath;
    }
}