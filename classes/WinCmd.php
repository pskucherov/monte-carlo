<?php

/**
 * Class WinCmd
 * @package OSWork
 */

class WinCmd extends Thread implements InterfOSProc
{
    /**
     * Way to launch php
     * @var string
     */
    static public $phpRunWay = 'X:\\usr\\local\\php5\\php.exe';

    static public function testExec()
    {
        if ( exec(
            self::$phpRunWay . " " . $_SERVER['DOCUMENT_ROOT'] . "\\test.php"
        ) == 1 ) {
            return 1;
        } else {
            return 0;
        }
    }

    public function runPHPProcess( $file = '', $argsSet = array() )
    {
        return $this->runProcess(self::$phpRunWay, $file, $argsSet);
    }

    public function runProcess( $process, $file = '', $argsSet = array() )
    {
        return pclose(
            popen(
                "start /B ". trim(
                    $process . " " . $file . " " . implode(' ', $argsSet)
                ), "r"
            )
        );
    }

    public function killProcess($pId)
    {
        return $this->runProcess("taskkill /f /pid ", $pId);
    }

    public function procInOSTaskList($pId)
    {
        if ( preg_match("|PID:\s+$pId|siU", shell_exec('tasklist /FO LIST')) ) {
            return true;
        }
        return false;
    }

}