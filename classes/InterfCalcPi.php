<?php

/**
 * Interface InterfCalcPi
 * @package CalcPi
 */

interface InterfCalcPi
{
    /**
     * Counting points which are within the circle.
     * @return void
     */
    public function pointsCalculator();

    /**
     * The calculation of pi.
     * @return float
     */
    public function calcPi();

}
