<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the question behaviour base class
 *
 * @package    moodlecore
 * @subpackage questionbehaviours
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace core_question\local\behaviour;

use question_utils;

/**
 * This helper class contains the constants and methods required for
 * manipulating scores for certainty based marking.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_cbm {
    /**#@+ @var integer named constants for the certainty levels. */
    const LOW = 1;
    const MED = 2;
    const HIGH = 3;
    /**#@-*/

    /** @var array list of all the certainty levels. */
    public static $certainties = array(self::LOW, self::MED, self::HIGH);

    /**#@+ @var array coefficients used to adjust the fraction based on certainty. */
    protected static $rightscore = array(
        self::LOW  => 1,
        self::MED  => 2,
        self::HIGH => 3,
    );
    protected static $wrongscore = array(
        self::LOW  =>  0,
        self::MED  => -2,
        self::HIGH => -6,
    );
    /**#@-*/

    /**#@+ @var array upper and lower limits of the optimal window. */
    protected static $lowlimit = array(
        self::LOW  => 0,
        self::MED  => 0.666666666666667,
        self::HIGH => 0.8,
    );
    protected static $highlimit = array(
        self::LOW  => 0.666666666666667,
        self::MED  => 0.8,
        self::HIGH => 1,
    );
    /**#@-*/

    /**
     * @return int the default certaintly level that should be assuemd if
     * the student does not choose one.
     */
    public static function default_certainty() {
        return self::LOW;
    }

    /**
     * Given a fraction, and a certainty, compute the adjusted fraction.
     * @param number $fraction the raw fraction for this question.
     * @param int $certainty one of the certainty level constants.
     * @return number the adjusted fraction taking the certainty into account.
     */
    public static function adjust_fraction($fraction, $certainty) {
        if ($certainty == -1) {
            // Certainty -1 has never been used in standard Moodle, but is
            // used in Tony-Gardiner Medwin's patches to mean 'No idea' which
            // we intend to implement: MDL-42077. In the mean time, avoid
            // errors for people who have used TGM's patches.
            return 0;
        }
        if ($fraction <= question_utils::MARK_TOLERANCE) {
            return self::$wrongscore[$certainty];
        } else {
            return self::$rightscore[$certainty] * $fraction;
        }
    }

    /**
     * @param int $certainty one of the LOW/MED/HIGH constants.
     * @return string a textual description of this certainty.
     */
    public static function get_string($certainty) {
        return get_string('certainty' . $certainty, 'qbehaviour_deferredcbm');
    }

    /**
     * @param int $certainty one of the LOW/MED/HIGH constants.
     * @return string a short textual description of this certainty.
     */
    public static function get_short_string($certainty) {
        return get_string('certaintyshort' . $certainty, 'qbehaviour_deferredcbm');
    }

    /**
     * Add information about certainty to a response summary.
     * @param string $summary the response summary.
     * @param int $certainty the level of certainty to add.
     * @return string the summary with information about the certainty added.
     */
    public static function summary_with_certainty($summary, $certainty) {
        if (is_null($certainty)) {
            return $summary;
        }
        return $summary . ' [' . self::get_short_string($certainty) . ']';
    }

    /**
     * @param int $certainty one of the LOW/MED/HIGH constants.
     * @return float the lower limit of the optimal probability range for this certainty.
     */
    public static function optimal_probablility_low($certainty) {
        return self::$lowlimit[$certainty];
    }

    /**
     * @param int $certainty one of the LOW/MED/HIGH constants.
     * @return float the upper limit of the optimal probability range for this certainty.
     */
    public static function optimal_probablility_high($certainty) {
        return self::$highlimit[$certainty];
    }
}
