<?php

/**
 * Class CalcPi
 * To calculate the number Pi using the Monte Carlo method.
 * @package CalcPi
 */

class CalcPi implements InterfCalcPi
{
    /**
     * The radius of the circle.
     * It is used in the calculation of pi.
     * @var float
     */
    private $_circleRadius      = 1.0;

    /**
     * The square of the radius.
     * @var float
     */
    private $_circleRadiusSqr;

    /**
     * The number of iterations for a loop.
     * After a given number of iterations, control returns to the thread
     * that can send the information to the parent script
     * or get a new job from a parent.
     * @var int
     */
    private $_numIterationsNow  = 1000;

    /**
     * The maximum number of iterations.
     * Calculating Pi is ready after reaching the value of this variable.
     * Value can be set in the constructor of the class.
     * @var int
     */
    private $_numIterationsMax  = 1000000;

    /**
     * The number of iterations done​.
     * @var int
     */
    private $_cntDoneIterations = 0;

    /**
     * Number of points in the circle​.
     * @var int
     */
    private $_cntPointsInCircle = 0;

    /**
     * @param int $numIterMax
     */
    public function __construct( $numIterMax = 1000000 )
    {
        $this->_numIterationsMax = $numIterMax;
        if ( $this->_numIterationsNow > $this->_numIterationsMax ) {
            $this->_numIterationsNow = $this->_numIterationsMax;
        }

        if ( $this->_circleRadius != 1 && $this->_circleRadius > 0 ) {
            $this->_circleRadiusSqr = $this->_circleRadius*$this->_circleRadius;
        } else {
            $this->_circleRadiusSqr = 1.0;
        }
    }

    /**
     * Counting points which are within the circle.
     * @return void
     */
    public function pointsCalculator()
    {

        if ( $this->_numIterationsNow <= 0 ) {
            throw new Exception("Error: var _numIterationsNow does not exist");
        }

        for ( $i = $this->_cntDoneIterations,
              $c = $this->_cntDoneIterations + $this->_numIterationsNow;
              $i < $c; $i++ ) {

            $x = $this->randomCoordinate();
            $y = $this->randomCoordinate();
            if ( ($x*$x + $y*$y) < $this->_circleRadiusSqr  ) {
                ++$this->_cntPointsInCircle;
            }
        }

        $this->_cntDoneIterations += $this->_numIterationsNow;

    }

    public function getCntDoneIter()
    {
        return $this->_cntDoneIterations;
    }
    public function getNumIterMax()
    {
        return $this->_numIterationsMax;
    }
    public function getPointsInCircle()
    {
        return $this->_cntPointsInCircle;
    }

    public function setCntDoneIter( $var )
    {
        $this->_cntDoneIterations = intval($var);
    }

    public function setPointsInCircle( $var )
    {
        $this->_cntPointsInCircle = intval($var);
    }

    /**
     * @return float
     * @throws Exception
     */
    public function calcPi()
    {
        if ( $this->_cntDoneIterations <= 0 ||
            $this->_cntPointsInCircle <= 0 ) {
            throw new Exception(
                "Error: prior to the calculation of Pi, "
                . "you must run the method of pointsCalculator()"
            );
        }
        return self::calcPiStatic(
            $this->_cntDoneIterations, $this->_cntPointsInCircle
        );
    }

    /**
     * The calculation of pi.
     * @return float
     */
    static public function calcPiStatic($iter, $pInCirc)
    {
        if ( !$iter ) {
            return 0;
        }
        return 4 * $pInCirc / $iter;
    }

    /**
     * Choosing a random value for the coordinates.
     * @return float
     */
    private function randomCoordinate ()
    {
        if ( $this->_circleRadius == 1 ) {
            return lcg_value();
        } else {
            return lcg_value() * $this->_circleRadius;
        }
    }

}