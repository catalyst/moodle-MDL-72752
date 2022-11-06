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

namespace qbank_viewcreator;

use core_question\local\bank\condition;

/**
 * This class controls to filter according to modified date.
 *
 * @package    qbank_viewcreator
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modified_date_condition extends condition {
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
        return 'midifieddate';
    }

    /**
     * Build query from filter value
     *
     * @param array $filters filter objects
     * @return array where sql and params
     */
    public static function build_query_from_filters(array $filters): array {
        if (isset($filters['midifieddate'])) {
            $filter = (object) $filters['midifieddate'];
            $filterverb = $filter->jointype ?? self::JOINTYPE_DEFAULT;
            $where = "";
            if ($filterverb === self::JOINTYPE_NONE) {
                $where = " NOT ";
            }

            if ($filter->rangetype === self::RANGETYPE_AFTER) {
                $timeafter = $filter->values[0];
                $where .= " ( q.timemodified >= {$timeafter} ) ";
            }
            if ($filter->rangetype === self::RANGETYPE_BEFORE) {
                $timebefore = $filter->values[0];
                $where .= " ( q.timemodified <= {$timebefore} ) ";
            }
            if ($filter->rangetype === self::RANGETYPE_BETWEEN) {
                $timefrom = $filter->values[0];
                $timeto = $filter->values[1];
                $where .= " ( q.timemodified >= {$timefrom} AND q.timemodified <= {$timeto} ) ";
            }
            return [$where, []];
        }
        return ['', []];
    }

    public function get_join_list(): array {
        return [
            self::JOINTYPE_NONE,
            self::JOINTYPE_ALL
        ];
    }

    public function get_title() {
        return get_string('modifiedby', 'qbank_viewcreator');
    }

    public function get_filter_class() {
        return 'core/datafilter/filtertypes/date';
    }
}
