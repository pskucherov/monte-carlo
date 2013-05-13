<?php

/**
 * Class Main
 * The table, which manages the process of calculating the number Pi.
 * @package Main
 */

class Main
{
    /**
     * The connection to Memcache
     * @var object
     */
    private $_mem;
    /**
     * An array that is used to exchange information with the child processes.
     * @var array
     */
    private $_memCell    = array();
    private $_os         = '';

    /**
     * The number of running threads
     * @var int
     */
    private $_numThreads = 0;

    /**
     * The maximum number of threads that can be run.
     * @var int
     */
    private $_maxThreads = 10;
    private $_osClass;

    private $_resultPi = 0;
    private $_resultTm = 0;
    
    /**
     * The minimum number of iterations
     * @var int
     */
    static public $startMinNumIter = 100000;

    /**
     * @param $os
     */
    public function __construct($os)
    {
        if ( empty($os) || ($os != 'win' && $os != 'nix') ) {
            throw new Exception('Error: Specify the OS ( "win" / "nix"  )');
        } else {
            $this->_os = $os;
        }

        $this->connectMemcache();
        $this->testExec();

    }

    /**
     * Kill the process if it is a child.
     * @param $pId
     */
    public function killProc($pId)
    {
        $this->checkMemCells();
        foreach ( $this->_memCell as $key => $val ) {
            if ( $val['pid'] == $pId ) {
                $this->_osClass->killProcess($pId);
            }
        }
    }

    /**
     * Continue the process.
     * @param $pId
     */
    public function continueProc($pId)
    {
        $this->checkMemCells();
        foreach ( $this->_memCell as $key => $val ) {
            if ( $pId == $val['pid']
                && !$this->_osClass->procInOSTaskList($val['pid']) ) {

                $this->runThread(
                    $val['id'], $val['maxIter'],
                    0, $this->_os, 'pointsCalculator'
                );

                break;
            }
        }
    }

    /**
     * Sends a command to stop all child processes.
     */
    public function stopAll()
    {
        $this->sendComm(1);
    }

    /**
     * Requires that all child processes have updated information on the state.
     */
    public function refreshInfo()
    {
        $this->sendComm(0);
    }

    /**
     * Send command.
     * $commId = 0 - refreshInfo
     * $commId = 1 - Stop
     * @param $commId
     */
    private function sendComm( $commId )
    {
        $this->getMemCells();
        foreach ( $this->_memCell as $key => $val ) {
            if ( $this->_osClass->procInOSTaskList($val['pid']) ) {
                $this->_mem->set('Comm_' . $commId . '_' . $key, 1, 0, 0);
            }
        }
    }

    /**
     * Verifies that all existing processes execute the command.
     * @return bool
     */
    private function commExists()
    {
        $this->getMemCells();
        for ($i = 0; $i < Thread::$numComm; $i++ ) {
            foreach ( $this->_memCell as $key => $val ) {
                if ( !$this->_osClass->procInOSTaskList($val['pid']) ) {
                    $this->_mem->delete('Comm_' . $i . '_' . $key);
                } else {
                    if ( $this->_mem->get('Comm_' . $i . '_' . $key) ) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Back to the initial state
     */
    public function resetAll()
    {
        if ( $this->checkMemCells() != 1 ) {
            foreach ( $this->_memCell as $key => $val ) {
                $this->killProc($val['pid']);
                $this->_mem->delete('Thread_' . $key);
            }
            $this->_memCell = array();
        }
    }

    /**
     * The table, which manages the process of calculating the number Pi.
     */
    public function mainPage()
    {
        echo '<div style="text-align: center">';
        if ( !$this->commExists() ) {
            ?>
            <table width=600 align=center border="1" >
                <tr>
                    <td align=center>Id</td>
                    <td align=center>pId</td>
                    <td align=center>Proc Run</td>
                    <td align=center>Proc Runs (sec.)</td>
                    <td align=center>DoneIter</td>
                    <td align=center>MaxIter</td>
                    <td align=center>PntInCircle</td>
                    <td align=center>Pi</td>
                    <td align=center>&nbsp</td>
                </tr>
                <?php echo $this->getTableOfCells(); ?>
            </table>
            <br><br>

            <h2>Result time: <?php echo round($this->_resultTm, 2); ?> sec.</h2>
            <h2>Result Pi: <?php echo $this->_resultPi; ?></h2>
            <BR>

        <?php
        } else {
            echo $this->wait(3, 'Waiting for the command execution...');
        }
        ?>
            <form method="POST" >
                <input type="submit" name="refreshInfo" value="Refresh Info" >
                <input type="submit" name="stopAll" value="Stop all" >
                <input type="submit" name="resetProc" value="Reset" >
            </form>
        </div>
        <?php
    }

    /**
     * Processes the data received from the child processes.
     * @return string
     */
    private function getTableOfCells()
    {
        $text       = '';
        $bufIter    = 0;
        $bufInCircl = 0;
        $minTime    = 0;
        $maxTime    = 0;


        foreach ( $this->_memCell as $key => $val ) {
            if ( !$val['endTime'] ) {
                $val['endTime'] = microtime(true);
            }

            if ( $this->_osClass->procInOSTaskList($val['pid']) ) {
                $procRun = '+';
            } else {
                $procRun = '-';
            }

            if ( !$minTime ) {
                $minTime = $val['startTime'];
            }
            if ( $maxTime < $val['endTime'] ) {
                $maxTime = $val['endTime'];
            }

            $bufIter    += intval($val['doneIter']);
            $bufInCircl += intval($val['pntInCircle']);

            $text .= "<tr>
                    <td align=center>$val[id]</td>
                    <td align=center>$val[pid]</td>
                    <td align=center>$procRun</td>
                    <td align=center>"
                   . round($val['endTime'] - $val['startTime'], 2) . "</td>
                    <td align=center>$val[doneIter]</td>
                    <td align=center>$val[maxIter]</td>
                    <td align=center>$val[pntInCircle]</td>
                    <td align=center>$val[pi]</td>
                    <td >" . $this->startStopLnk($val['pid']) . "</td>
                </tr>";
        }


        $this->_resultTm = $maxTime - $minTime;

        if ( $bufIter > 0 ) {
            $this->_resultPi = CalcPi::calcPiStatic($bufIter, $bufInCircl);
        }

        return $text;
    }

    /**
     * @param $pId
     * @return string
     */
    private function startStopLnk($pId)
    {
        if ( $this->_osClass->procInOSTaskList($pId) ) {
            return "<a href=?kill=$pId >Kill</a>";
        } else {
            foreach ( $this->_memCell as $key => $val ) {
                if ( $pId == $val['pid']
                     && (intval($val['doneIter']) < intval($val['maxIter'])) ) {
                    return "<a href=?start=$pId >Continue</a>";
                }
            }
        }
        return '- - -';
    }

    public function checkMemCells()
    {
        if ( count($this->_memCell) > 0 ) {
            return count($this->_memCell) + 1;
        } else {
            return $this->getMemCells();
        }

    }

    /**
     * Get data from memcache.
     * @return int
     */
    private function getMemCells()
    {
        $i = 0;
        do {
            ++$i;
            $flag = false;
            $this->_memCell[$i] = json_decode(
                $this->_mem->get('Thread_' . $i), true
            );
            if ( $this->_memCell[$i] ) {
                $flag = true;
            } else {
                unset($this->_memCell[$i]);
            }
        } while ($flag);
        return $i;
    }

    /**
     * Sends a request to the OS to start the process.
     * @param $id
     * @param $numIterationsMax
     * @param $restart
     * @param $os
     * @param $method
     */
    private function runThread( $id, $numIterationsMax, $restart, $os, $method )
    {
        $this->_osClass->runPHPProcess(
            $_SERVER['DOCUMENT_ROOT'] . "/runThread.php",
            array($id, $numIterationsMax, $restart, $os, $method)
        );
    }

    public function startForm()
    {
        echo '<div style="text-align: center">';
        if ( !$this->procStartForm() ) {
            echo '<b>Enter the number of threads from 1 to '
                 . $this->_maxThreads . '.</b><BR><BR>';
            ?>
            <form method="POST" >
                The number of threads:
                <input name="numThreads" value="5" size="3" >
                <br><br>
                <input type="submit" name="startproc" value="Calculate Pi" >
            </form>
            <?php

        } else {
            echo $this->wait(3);
        }
        echo '</div>';

    }

    private function wait($sec, $text = '', $url = 'index.php' )
    {
        return '<b>Wait (' . $sec . ' sec) ... <BR><BR>' . $text . ' </b>'
               . '<META HTTP-EQUIV="REFRESH" CONTENT="'
               . $sec . ';URL=' . $url . '"><BR><BR>';
    }

    private function procStartForm()
    {
        if ( isset($_POST['numThreads']) ) {

            $_POST['numThreads'] = intval($_POST['numThreads']);

            if ( $_POST['numThreads'] > 0
              && $_POST['numThreads'] <= $this->_maxThreads ) {

                $this->_numThreads = $_POST['numThreads'];
                for ( $i = 0; $i < $_POST['numThreads']; $i++ ) {
                    $this->runThread(
                        ($i+1), (self::$startMinNumIter * rand(1, 10)), 1,
                        $this->_os, 'pointsCalculator'
                    );
                }

                return 1;
            }
        }
        return 0;
    }

    /**
     * @throws Exception
     */
    private function testExec()
    {
        $test = 0;
        if ( $this->_os == 'win' ) {
            $this->_osClass = new WinCmd();
            $test    = WinCmd::testExec();
        } else {
            $this->_osClass = new NixShell();
            $test    = NixShell::testExec();
        }
        if ( !$test ) {
            throw new Exception(
                'Error: exec PHP not work. Check '
                . ucfirst($this->_os) . 'Cmd::testExec() or $OS in index.php'
            );
        }
    }

    /**
     * @throws Exception
     */
    private function connectMemcache()
    {
        $this->_mem = new Memcache;
        if ( !$this->_mem->connect('localhost', 11211) ) {
            throw new Exception(
                'Error: Memcache could not connect.'
                . '<br>Check (Main -> connectMemcache())'
            );
        }
    }


}