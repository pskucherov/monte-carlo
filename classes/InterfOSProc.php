<?php

/**
 * Interface InterfOSProc
 * @package OSWork
 */

interface InterfOSProc
{
    /**
     * Verify that you can run php script and function exec
     * @return bool
     */
    static public function testExec();

    /**
     * @param string $file
     * @param array $argsSet
     * @return mixed
     */
    public function runPHPProcess( $file = '', $argsSet = array() );

    /**
     * @param $process
     * @param string $file
     * @param array $argsSet
     * @return mixed
     */
    public function runProcess( $process, $file = '', $argsSet = array() );

    /**
     * @param $pId
     * @return mixed
     */
    public function killProcess($pId);

    /**
     * Checks whether the process is running.
     * @param $pId
     * @return bool
     */
    public function procInOSTaskList($pId);

}