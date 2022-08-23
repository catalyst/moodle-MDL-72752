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

namespace core_question\local;

/**
 * Class question_reference to handle the low level changes in the database and any associated bits.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_reference {

    /**
     * Question reference object.
     *
     * @var array $questionreference
     */
    public $questionreference;

    public function __construct($usingcontext, $component, $questionarea, $itemid, $questionid) {
        global $DB;
        $this->questionreference['usingcontextid'] = $usingcontext;
        $this->questionreference['component'] = $component;
        $this->questionreference['questionarea'] = $questionarea;
        $this->questionreference['itemid'] = $itemid;
        $this->questionreference['questionbankentryid'] = get_question_bank_entry($questionid)->id; // this method will also move.
        $this->questionreference['version'] = null;
        if ($referencerecord = $DB->get_record('question_references', $this->questionreference)) {
            $this->questionreference['version'] = $referencerecord->version;
            $this->questionreference['id'] = $referencerecord->id;
        }

    }

    public function delete(): void {
        global $DB;
        if (isset($this->questionreference['id'])) {
            $DB->delete_records('question_references', ['id' => $this->questionreference['id']]);
        } else {
            throw new \moodle_exception('Can not find the record, seems not created yet.');
        }
    }

    public function save(): void {
        global $DB;
        $this->questionreference = (object) $this->questionreference;
        if (isset($this->questionreference['id'])) {
            $DB->update_record('question_references', $this->questionreference);
        } else {
            $DB->insert_record('question_references', $this->questionreference);
        }
    }

    // Some more specialized methods as required.

}
