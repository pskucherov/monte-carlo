<?php

/**
 * Class NixShell
 * @package OSWork
 */

class NixShell extends Thread implements InterfOSProc
{

    /**
     * Way to launch php
     * @var string
     */
    static public $phpRunWay = '/usr/local/bin/php';

    static public function testExec()
    {
        if ( exec(
            self::$phpRunWay . " "
            . $_SERVER['DOCUMENT_ROOT'] . "/test.php"
        ) == 1) {
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
        return exec(
            "nice -n +5 " . trim(
                $process . " " . $file . " " . implode(' ', $argsSet)
            ) . " > /dev/null &"
        );
    }

    public function killProcess($pId)
    {
        $this->runProcess("kill", $pId);
    }

    public function procInOSTaskList($pId)
    {
        if ( preg_match("/$pId*./", exec('ps -p ' . $pId)) ) {
            return true;
        }
        return false;
    }
}