<?php

/**
 * Class Thread
 * @package Thread
 */

class Thread
{

    /**
     * The connection to Memcache
     * @var object
     */
    private $_mem;
    /**
     * Class CalcPi
     * @var object
     */
    private $_calcPi;
    /**
     * An array that is used to exchange information with the child processes.
     * @var array
     */
    private $_memCell   = array();

    /**
     * @var int
     */
    private $_id        = 0;
    /**
     * @var int
     */
    private $_pId       = 0;

    /**
     * @var int
     */
    private $_startTime = 0;

    /**
     * The number of commands that can come from the parent.
     * @var int
     */
    static public $numComm = 2;

    /**
     * @param int $id
     * @param int $numMaxIter
     * @param int $restart
     */
    public function __construct($id = 0, $numMaxIter = 0, $restart = 0)
    {
        if ( $id ) {
            $this->_mem = new Memcache;
            if ( !$this->_mem->connect('localhost', 11211) ) {
                throw new Exception('Error: Memcache could not connect.');
            }

            $this->_id        = $id;
            $this->_pId       = getmypid();
            $this->_startTime = microtime(true);

            if ( $restart ) {
                $this->restart();
            }

            $this->_calcPi = new CalcPi($numMaxIter);

            $this->parseMemCell();

        }

    }

    /**
     * Updates the status of calculations (for the parent).
     */
    public function sendNewInfo()
    {

            $this->_memCell['doneIter']   = $this->_calcPi->getCntDoneIter();
            $this->_memCell['maxIter']    = $this->_calcPi->getNumIterMax();
            $this->_memCell['pntInCircle']= $this->_calcPi->getPointsInCircle();
            $this->_mem->set(
                'Thread_' . $this->_id, json_encode($this->_memCell), 0, 0
            );

    }

    /**
     * Executes a command that came from a parent.
     */
    public function commExec()
    {
        for ($i = 0; $i < self::$numComm; $i++ ) {
            if ($this->_mem->get('Comm_' . $i . '_' . $this->_id)) {
                switch($i) {

                    case 0:
                        $this->sendNewInfo();
                        break;

                    case 1:
                        die();
                        break;

                }
                $this->_mem->delete('Comm_' . $i . '_' . $this->_id);
            }
        }

    }
    public function __destruct()
    {
        if ( $this->_id > 0 ) {

            $this->sendNewInfo();
            $this->_memCell['endTime'] = microtime(true);
            $this->_memCell['pi']      = $this->_calcPi->calcPi();

            $this->_mem->set(
                'Thread_' . $this->_id, json_encode($this->_memCell), 0, 0
            );

            $this->commExec();

        }
    }

    private function restart()
    {
        $this->_memCell = array();
        $this->_mem->delete('Thread_' . $this->_id);
    }

    /**
     * Creates a new cell in the memcache, or use an already created.
     */
    private function parseMemCell()
    {

        $this->_memCell = json_decode(
            $this->_mem->get('Thread_' . $this->_id), true
        );
        if ( !$this->_memCell ) {
            $this->_memCell = array();
            $this->_memCell['id']        = $this->_id;
            $this->_memCell['pid']       = $this->_pId;
            $this->_memCell['startTime'] = $this->_startTime;
            $this->_memCell['endTime']   = 0;

            $this->_mem->set(
                'Thread_' . $this->_id, json_encode($this->_memCell), 0, 0
            );
        }
        if ( $this->_memCell['pid'] != $this->_pId ) {
            $this->_memCell['pid'] = $this->_pId;
            $this->_startTime = $this->_memCell['startTime'];

            $this->_calcPi->setCntDoneIter($this->_memCell['doneIter']);
            $this->_calcPi->setPointsInCircle($this->_memCell['pntInCircle']);

            $this->_mem->set(
                'Thread_' . $this->_id, json_encode($this->_memCell), 0, 0
            );
        }

    }

    /**
     * @return CalcPi|object
     */
    public function runMethod()
    {
        return $this->_calcPi;
    }


}