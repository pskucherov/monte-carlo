<?php

set_time_limit(0);

include 'classes/InterfCalcPi.php';
include 'classes/CalcPi.php';

include 'classes/InterfThread.php';
include 'classes/Thread.php';

include 'classes/InterfOSProc.php';


/*
   $argv[1] = $id
   $argv[2] = numMaxIter
   $argv[3] = restart
   $argv[4] = OS
   $argv[5] = $method [pointsCalculator]
 */

try {

    if ( $argv[4] == 'win' ) {
        include 'classes/WinCmd.php';
        $thread = new WinCmd($argv[1], $argv[2], $argv[3]);
    } else {
        include 'classes/NixShell.php';
        $thread = new NixShell($argv[1], $argv[2], $argv[3]);
    }

    $thread->sendNewInfo();

    switch($argv[5]) {
        case 'pointsCalculator':

            do {

                $thread->commExec();
                $thread->runMethod()->pointsCalculator();

            } while ( $thread->runMethod()->getCntDoneIter()
                        < $thread->runMethod()->getNumIterMax() );
            break;
    }

} catch( Exception $e ) {
    echo '<b>' . $e->getMessage() . '</b>';
}
