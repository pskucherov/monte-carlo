<?php


$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

include 'classes/InterfCalcPi.php';
include 'classes/CalcPi.php';

include 'classes/InterfThread.php';
include 'classes/Thread.php';

include 'classes/InterfOSProc.php';
include 'classes/WinCmd.php';
include 'classes/NixShell.php';

include 'classes/Main.php';


if ( substr(PHP_OS, 0, 3) == 'WIN' ) {
    $os = 'win';
} else {
    $os = 'nix';
}

try {

    $main = new Main($os);

    if ( isset($_GET['start'])) {
        $main->continueProc(intval($_GET['start']));
    }

    if ( isset($_GET['kill'])) {
        $main->killProc(intval($_GET['kill']));
    }

    if ( isset($_POST['stopAll'])) {
        $main->stopAll();
    }

    if ( isset($_POST['refreshInfo'])) {
        $main->refreshInfo();
    }

    if ( isset($_POST['resetProc'])) {
        $main->resetAll();
    }

    if ( $main->checkMemCells() == 1 ) {
        $main->startForm();
    } else {
        $main->mainPage();
    }

} catch( Exception $e ) {
    echo '<b>' . $e->getMessage() . '</b>';
}
