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

namespace core_question\local\bank;

/**
 * Question reference location manager class to handle standard reference elements.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_reference_location {

    /**
     * @var int $usingcontext
     */
    public $usingcontext;

    /**
     * @var string $component
     */
    public $component;

    /**
     * @var string $questionarea
     */
    public $questionarea;

    /**
     * @var int $itemid
     */
    public $itemid;

    /**
     * Question reference location constructor.
     *
     * @param int $usingcontext the context its used in
     * @param string $component name of the component eg core_question
     * @param string $questionarea where the question is used in the component eg slot
     * @param int $itemid id of the question area
     */
    public function __construct($usingcontext, $component, $questionarea, $itemid) {
        $this->usingcontext = $usingcontext;
        $this->component = $component;
        $this->questionarea = $questionarea;
        $this->itemid = $itemid;
    }

    public function __toString() {
        return str_replace(self::TO_ESCAPE, self::ESCAPED, $this->categoryidnumber);
    }

    /**
     * Add parameters representing this location to a URL.
     *
     * @param \moodle_url $url the URL to add to.
     */
    public function add_params_to_url(\moodle_url $url, string $elementname): void {
        $url->param($elementname, $this->$elementname);
    }

}