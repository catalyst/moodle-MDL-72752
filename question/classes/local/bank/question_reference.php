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
 * Manager.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_reference extends question_reference_location {

    /**
     * @var int id of the reference record
     */
    public $id = null;

    /**
     * @var int id of the question bank entry
     */
    public $questionbankentryid;

    /**
     * @var int version number of the reference record
     */
    public $version;

    /**
     * Question reference location constructor.
     *
     * @param int $usingcontext the context its used in
     * @param string $component name of the component eg core_question
     * @param string $questionarea where the question is used in the component eg slot
     * @param int $itemid id of the question area
     */
    public function __construct(int $questionbankentryid, int $version, int $usingcontext, string $component,
        string $questionarea, int $itemid, int $id = 0) {
        $this->id = $id;
        $this->questionbankentryid = $questionbankentryid;
        $this->version = $version;
        parent::__construct($usingcontext, $component, $questionarea, $itemid);
    }

    public function save() {
        global $DB;
        if (!empty($this->id)) {
            $DB->update_record('question_references', $this);
        } else {
            $this->id = $DB->insert_record('question_references', $this);
        }

        return $this->id;
    }

    public function delete() {
        global $DB;
        if (!empty($this->id)) {
            $DB->delete_records('question_referneces', ['id' => $this->id]);
        }
    }

}
