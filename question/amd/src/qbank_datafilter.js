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
 * Question bank filter management.
 *
 * @module     core_question/qbank_datafilter
 * @copyright  2022 Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CoreFilter from 'core/datafilter';
// import CourseFilter from 'core/datafilter/filtertypes/courseid';
// import GenericFilter from 'core/datafilter/filtertype';
// import {get_strings as getStrings} from 'core/str';
import Notification from 'core/notification';
import Pending from 'core/pending';
import Selectors from 'core/datafilter/selectors';
import Templates from 'core/templates';
import CustomEvents from 'core/custom_interaction_events';
import jQuery from 'jquery';
import GenericFilter from 'core/datafilter/filtertype';

export default class extends CoreFilter {
    constructor(filterSet, applyCallback) {
        super(filterSet, applyCallback);
    }

    /**
     * Initialise event listeners to the filter.
     */
    init() {
        // Add listeners for the main actions.
        this.filterSet.querySelector(Selectors.filterset.region).addEventListener('click', e => {
            if (e.target.closest(Selectors.filterset.actions.addRow)) {
                e.preventDefault();

                this.addFilterRow();
            }

            if (e.target.closest(Selectors.filterset.actions.applyFilters)) {
                e.preventDefault();

                this.updateTableFromFilter();
            }

            if (e.target.closest(Selectors.filterset.actions.resetFilters)) {
                e.preventDefault();

                this.removeAllFilters();
            }
        });

        // Add the listener to remove a single filter.
        this.filterSet.querySelector(Selectors.filterset.regions.filterlist).addEventListener('click', e => {
            if (e.target.closest(Selectors.filter.actions.remove)) {
                e.preventDefault();

                this.removeOrReplaceFilterRow(e.target.closest(Selectors.filter.region), true);
            }
        });

        // Add listeners for the filter type selection.
        let filterRegion = jQuery(this.getFilterRegion());
        CustomEvents.define(filterRegion, [CustomEvents.events.accessibleChange]);
        filterRegion.on(CustomEvents.events.accessibleChange, e => {
            const typeField = e.target.closest(Selectors.filter.fields.type);
            if (typeField && typeField.value) {
                const filter = e.target.closest(Selectors.filter.region);

                this.addFilter(filter, typeField.value);
            }
        });

        this.filterSet.querySelector(Selectors.filterset.fields.join).addEventListener('change', e => {
            this.filterSet.dataset.filterverb = e.target.value;
        });
    }

    addFilterRow(filterdata = {}) {
        const pendingPromise = new Pending('core/datafilter:addFilterRow');
        const rownum = filterdata.rownum ?? 1 + this.getFilterRegion().querySelectorAll(Selectors.filter.region).length;
        return Templates.renderForPromise('core/datafilter/filter_row', {"rownumber": rownum})
            .then(({html, js}) => {
                const newContentNodes = Templates.appendNodeContents(this.getFilterRegion(), html, js);

                return newContentNodes;
            })
            .then(filterRow => {
                // Note: This is a nasty hack.
                // We should try to find a better way of doing this.
                // We do not have the list of types in a readily consumable format, so we take the pre-rendered one and copy
                // it in place.
                const typeList = this.filterSet.querySelector(Selectors.data.typeList);

                filterRow.forEach(contentNode => {
                    const contentTypeList = contentNode.querySelector(Selectors.filter.fields.type);

                    if (contentTypeList) {
                        contentTypeList.innerHTML = typeList.innerHTML;
                    }
                });

                return filterRow;
            })
            .then(filterRow => {
                this.updateFiltersOptions();

                return filterRow;
            })
            .then(result => {
                pendingPromise.resolve();

                if (Object.keys(filterdata).length !== 0) {
                    result.forEach(filter => {
                        this.addFilter(filter, filterdata.filtertype, filterdata.values,
                            filterdata.jointype, filterdata.rangetype);
                    });
                }
                return result;
            })
            .catch(Notification.exception);
    }

    async addFilter(filterRow, filterType, initialFilterValues, filterJoin, filterRange) {
        // Name the filter on the filter row.
        filterRow.dataset.filterType = filterType;

        const filterDataNode = this.getFilterDataSource(filterType);

        // Instantiate the Filter class.
        let Filter = GenericFilter;
        if (filterDataNode.dataset.filterTypeClass) {
            Filter = await import(filterDataNode.dataset.filterTypeClass);
        }
        this.activeFilters[filterType] = new Filter(filterType, this.filterSet, initialFilterValues, filterRange);
        // Disable the select.
        const typeField = filterRow.querySelector(Selectors.filter.fields.type);
        typeField.value = filterType;
        typeField.disabled = 'disabled';
        // Update the join list.
        this.updateJoinList(filterDataNode.dataset.joinList, filterRow);
        // Update the list of available filter types.
        this.updateFiltersOptions();

        return this.activeFilters[filterType];
    }

    updateJoinList(filterJoinData, filterRow) {
        const regularJoinList = [0, 1, 2];
        const filterJoinList = JSON.parse(filterJoinData);
        // eslint-disable-next-line no-console
        console.log(regularJoinList);
        // eslint-disable-next-line no-console
        console.log(filterJoinList);
        let toRemove = [];
        regularJoinList.forEach((joinType) => {
            if (!filterJoinList.includes(joinType)) {
                toRemove.push(joinType);
            }
        });
        // eslint-disable-next-line no-console
        console.log(toRemove);
        // Re-construct join type and list.
        if (toRemove.length !== 0) {
            const joinField = filterRow.querySelector(Selectors.filter.fields.join);
            toRemove.forEach((joinType) => {
                joinField.options.remove(joinType);
            });
            // joinField.value = filterJoin;
        }
    }
}