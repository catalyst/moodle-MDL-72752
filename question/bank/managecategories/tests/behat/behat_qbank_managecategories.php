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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Steps definitions related with the drag and drop into text question type.
 * @package    qbank_managecategories
 * @category   test
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_qbank_managecategories extends behat_base {
    /**
     * Add / Append query to current url and visit it for category filter.
     *
     * @param string $category category name to get proper question category id.
     * @param string $course course name to get proper course id.
     * @param string $jointype join type for query.
     *
     * @Given /^I add "(?P<category>[^"]*)" and "(?P<course>[^"]*)" with "(?P<join_type>[^"]*)" join type parameters to url and visit it$/
     */
    public function i_add_and_with_join_type_parameters_to_url_and_visit_it($category, $course, $jointype) {
        global $DB;
        $jointypes = [
            'None' => 0,
            'Any' => 1,
            'All' => 2
        ];
        $categoryid = $DB->get_field('question_categories', 'id', ['name' => $category]);
        $courseid = $DB->get_field('course', 'id', ['fullname' => $course]);
        $querystring = "courseid%3Dname%253Dcourseid%2526jointype%253D1%2526values%253D0%25253D" . $courseid . "%26category%3Dname%253Dcategory%2526jointype%253D" . $jointypes[$jointype] . "%2526values%253D0%25253D" . $categoryid;
        $querystring = urldecode($querystring);
        $url = new moodle_url($this->getSession()->getCurrentUrl(), ['filter' => $querystring]);
        $this->execute('behat_general::i_visit', [$url->out(false)]);
    }

    /**
     * Drags and drops the specified element in the question category list.
     *
     * @Given /^I drag "(?P<element_string>(?:[^"]|\\")*)" \
     * and I drop it in "(?P<container_element_string>(?:[^"]|\\")*)" in the question category list$/
     * @param string $source source element
     * @param string $target target element
     */
    public function i_drag_and_i_drop_it_in_question_category_list(string $source, string $target) {
        // Finding li element of the drag item.
        // To differentiate between drag event on li, and other mouse click events on other elements in the item.
        $source = "//li[contains(@class, 'list_item') and contains(., '" . $this->escape($source) . "')]";
        $target = "//li[contains(@class, 'list_item') and contains(., '" . $this->escape($target) . "')]";
        $sourcetype = 'xpath_element';
        $targettype = 'xpath_element';

        $generalcontext = behat_context_helper::get('behat_general');
        // Adds support for firefox scrolling.
        $sourcenode = $this->get_node_in_container($sourcetype, $source, 'region', 'categoriesrendered');
        $this->execute_js_on_node($sourcenode, '{{ELEMENT}}.scrollIntoView();');

        $generalcontext->i_drag_and_i_drop_it_in($source, $sourcetype, $target, $targettype);
    }

    /**
     * Select item from autocomplete list.
     *
     * @Given /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" \
     * in the "(?P<modal_name>(?:[^"]|\\")*)" modal$/
     *
     * @param string $element the element
     * @param string $selectortype seletor type
     * @param string $modal name of the modal
     */
    public function i_click_on_in_the_modal(string $element, string $selectortype, string $modal) {
        $this->execute('behat_general::i_click_on_in_the',
            [$element, $selectortype, $modal, "dialogue"]
        );
    }
}
