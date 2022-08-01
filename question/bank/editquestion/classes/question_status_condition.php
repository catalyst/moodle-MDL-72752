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

namespace qbank_editquestion;

use core_question\local\bank\condition;
use core_question\local\bank\question_version_status;

/**
 * Question bank search class to allow searching/filtering by status of a question.
 *
 * @package    qbank_editquestion
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_status_condition extends condition {

    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    /**
     * @var array $params parameters for query.
     */
    protected $params;

    public function __construct($qbank = null) {
        if (!$qbank) {
            return;
        }

        $this->filters = $qbank->get_pagevars('filters');
        //Build where and params.
        [$this->where, $this->params] = self::build_query_from_filters($this->filters);
    }

    public function where() {
        return $this->where;
    }

    public function params() {
        return $this->params;
    }

    public function get_condition_key() {
        return 'qstatus';
    }

    public static function build_query_from_filters(array $filters): array {
        $where = '';

        if (isset($filters['qstatus'])) {
            $filter = (object) $filters['qstatus'];
            $selectedstatuses = $filter->values ?? [];
            foreach ($selectedstatuses as $key => $selectedstatus) {
                if ($selectedstatus === '0') {
                    $selectedstatuses[$key] = question_version_status::QUESTION_STATUS_READY;
                } else if ($selectedstatus === '2') {
                    $selectedstatuses[$key] = question_version_status::QUESTION_STATUS_DRAFT;
                }
            }
            $filterverb = $filter->jointype ?? self::JOINTYPE_DEFAULT;
            $condition = 'AND';
            $operator = '=';
            if ($filterverb === self::JOINTYPE_ANY ||
                $filterverb === self::JOINTYPE_ALL ||
                $filterverb === self::JOINTYPE_DEFAULT) {
                $condition = 'OR';
            }
            if ($filterverb === self::JOINTYPE_NONE) {
                $operator = '!=';
            }
            $firstwhere  = 0;
            foreach ($selectedstatuses as $selectedstatus) {
                $wherecondition = $condition;
                if (!$firstwhere) {
                    $wherecondition = '';
                }
                $where .= $wherecondition . " " . "qv.status " . $operator . " '" . $selectedstatus . "'";
                $firstwhere ++;
            }
        }

        return [$where, []];

    }

    public function get_title() {
        return get_string('questionstatus', 'qbank_editquestion');
    }

    public function get_filter_class() {
        return null;
    }

    public function get_join_list(): array {
        return [
            self::JOINTYPE_ALL,
            self::JOINTYPE_NONE,
        ];
    }

    public function get_initial_values() {
        $values = [
            [
                'value' => 0,
                'title' => get_string('questionstatusready', 'qbank_editquestion'),
            ],
            [
                'value' => 2,
                'title' => get_string('questionstatusdraft', 'qbank_editquestion'),
            ],
        ];
        return $values;
    }
}
