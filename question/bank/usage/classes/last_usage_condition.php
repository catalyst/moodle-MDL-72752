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

namespace qbank_usage;

use core_question\local\bank\condition;

/**
 * This class controls from which date to which date questions are last used.
 *
 * @package    qbank_viewcreator
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class last_usage_condition extends condition {
    /** @var array Questions filters */
    protected $filters;

    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    /**
     * Constructor to initialize the date filter condition.
     */
    public function __construct($qbank = null) {
        if (!$qbank) {
            return;
        }

        $this->filters = $qbank->get_pagevars('filters');
        // Build where and params.
        list($this->where, $this->params) = self::build_query_from_filters($this->filters);
    }

    public function where() {
        return $this->where;
    }

    public function get_condition_key() {
        return 'lastuseddate';
    }

    /**
     * Return parameters to be bound to the above WHERE clause fragment.
     * @return array parameter name => value.
     */
    public function params() {
        return [];
    }

    /**
     * Build query from filter value
     *
     * @param array $filters filter objects
     * @return array where sql and params
     */
    public static function build_query_from_filters(array $filters): array {
        if (isset($filters['lastuseddate'])) {
            $filter = (object) $filters['lastuseddate'];
            $where = 'q.id IN (SELECT qsn.id
                                 FROM {quiz} qz
                                 JOIN {quiz_attempts} qa ON qa.quiz = qz.id
                                 JOIN {question_usages} qu ON qu.id = qa.uniqueid
                                 JOIN {question_attempts} qatt ON qatt.questionusageid = qu.id
                                 JOIN {question} qsn ON qsn.id = qatt.questionid
                                WHERE qa.preview = 0';
            if ($filter->rangetype === self::RANGETYPE_AFTER) {
                $timeafter = $filter->values[0];
                $where .= " AND qatt.timemodified >= {$timeafter} )";
            }
            if ($filter->rangetype === self::RANGETYPE_BEFORE) {
                $timebefore = $filter->values[0];
                $where .= " AND qatt.timemodified <= {$timebefore} )";
            }
            if ($filter->rangetype === self::RANGETYPE_BETWEEN) {
                $timefrom = $filter->values[0];
                $timeto = $filter->values[1];
                $where .= "AND qatt.timemodified >= {$timefrom} AND qatt.timemodified <= {$timeto} )";
            }
            return [$where, []];
        }
        return ['', []];
    }

    public function get_title() {
        return get_string('questionlastused', 'qbank_usage');
    }

    public function get_filter_class() {
        return 'core/datafilter/filtertypes/date';
    }
}
