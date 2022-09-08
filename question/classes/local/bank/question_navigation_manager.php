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
 * Manager class to handle question navigation, url and its associated operations.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_navigation_manager {

    /**
     * Gets the question edit url.
     *
     * @param object $context a context
     * @return string|bool A URL for editing questions in this context.
     */
    public static function question_edit_url($context) {
        global $CFG, $SITE;
        if (!has_any_capability(\core_question\local\bank\question_edit_contexts::question_get_question_capabilities(), $context)) {
            return false;
        }
        $baseurl = $CFG->wwwroot . '/question/edit.php?';
        $defaultcategory = \core_question\question_categories_manager::question_get_default_category($context->id);
        if ($defaultcategory) {
            $baseurl .= 'cat=' . $defaultcategory->id . ',' . $context->id . '&amp;';
        }
        switch ($context->contextlevel) {
            case CONTEXT_SYSTEM:
                return $baseurl . 'courseid=' . $SITE->id;
            case CONTEXT_COURSECAT:
                // This is nasty, becuase we can only edit questions in a course
                // context at the moment, so for now we just return false.
                return false;
            case CONTEXT_COURSE:
                return $baseurl . 'courseid=' . $context->instanceid;
            case CONTEXT_MODULE:
                return $baseurl . 'cmid=' . $context->instanceid;
        }

    }

    /**
     * Adds question bank setting links to the given navigation node if caps are met
     * and loads the navigation from the plugins.
     * Qbank plugins can extend the navigation_plugin_base and add their own navigation node,
     * this method will help to autoload those nodes in the question bank navigation.
     *
     * @param \navigation_node $navigationnode The navigation node to add the question branch to
     * @param \context $context
     * @param string $baseurl the url of the base where the api is implemented from
     * @param bool $default If uses the default navigation or needs id as parameter.
     * @return \navigation_node|void Returns the question branch that was added
     */
    public static function question_extend_settings_navigation(\navigation_node $navigationnode, $context, $baseurl = '/question/edit.php',
        $default = true) {

        global $PAGE;

        if ($context->contextlevel == CONTEXT_MODULE) {
            $params = ['cmid' => $context->instanceid];
            if ($default) {
                $paramqbank = ['cmid' => $context->instanceid];
            } else {
                $paramqbank = ['id' => $context->instanceid];
            }
        } else {
            return;
        }

        if (($cat = $PAGE->url->param('cat')) && preg_match('~\d+,\d+~', $cat)) {
            $params['cat'] = $cat;
            $paramqbank['cat'] = $cat;
        }

        $questionnode = $navigationnode->add(get_string('questionbank', 'question'),
            new \moodle_url($baseurl, $paramqbank), \navigation_node::TYPE_CONTAINER, null, 'questionbank');

        $corenavigations = [
            'questions' => [
                'title' => get_string('questions', 'question'),
                'url' => new \moodle_url($baseurl)
            ],
            'categories' => [
                'title' => get_string('categories', 'question'),
                'url' => new \moodle_url('/question/category.php')
            ],
            'import' => [
                'title' => get_string('import', 'question'),
                'url' => new \moodle_url('/question/import.php')
            ],
            'export' => [
                'title' => get_string('export', 'question'),
                'url' => new \moodle_url('/question/export.php')
            ]
        ];

        $plugins = \core_component::get_plugin_list_with_class('qbank', 'plugin_feature', 'plugin_feature.php');
        foreach ($plugins as $componentname => $plugin) {
            $pluginentrypoint = new $plugin();
            $pluginentrypointobject = $pluginentrypoint->get_navigation_node();
            // Don't need the plugins without navigation node.
            if ($pluginentrypointobject === null) {
                unset($plugins[$componentname]);
                continue;
            }
            foreach ($corenavigations as $key => $corenavigation) {
                if ($pluginentrypointobject->get_navigation_key() === $key) {
                    unset($plugins[$componentname]);
                    if (!\core\plugininfo\qbank::is_plugin_enabled($componentname)) {
                        unset($corenavigations[$key]);
                        break;
                    }
                    $corenavigations[$key] = [
                        'title' => $pluginentrypointobject->get_navigation_title(),
                        'url'   => $pluginentrypointobject->get_navigation_url()
                    ];
                }
            }
        }

        // Mitigate the risk of regression.
        foreach ($corenavigations as $node => $corenavigation) {
            if (empty($corenavigation)) {
                unset($corenavigations[$node]);
            }
        }

        // Community/additional plugins have navigation node.
        $pluginnavigations = [];
        foreach ($plugins as $componentname => $plugin) {
            $pluginentrypoint = new $plugin();
            $pluginentrypointobject = $pluginentrypoint->get_navigation_node();
            // Don't need the plugins without navigation node.
            if ($pluginentrypointobject === null || !\core\plugininfo\qbank::is_plugin_enabled($componentname)) {
                unset($plugins[$componentname]);
                continue;
            }
            $pluginnavigations[$pluginentrypointobject->get_navigation_key()] = [
                'title' => $pluginentrypointobject->get_navigation_title(),
                'url'   => $pluginentrypointobject->get_navigation_url(),
                'capabilities' => $pluginentrypointobject->get_navigation_capabilities()
            ];
        }

        $contexts = new \core_question\local\bank\question_edit_contexts($context);
        foreach ($corenavigations as $key => $corenavigation) {
            if ($contexts->have_one_edit_tab_cap($key)) {
                $questionnode->add($corenavigation['title'], new \moodle_url(
                    $corenavigation['url'], $params), \navigation_node::TYPE_SETTING, null, $key);
            }
        }

        foreach ($pluginnavigations as $key => $pluginnavigation) {
            if (is_array($pluginnavigation['capabilities'])) {
                if (!$contexts->have_one_cap($pluginnavigation['capabilities'])) {
                    continue;
                }
            }
            $questionnode->add($pluginnavigation['title'], new \moodle_url(
                $pluginnavigation['url'], $params), \navigation_node::TYPE_SETTING, null, $key);
        }

        return $questionnode;
    }

    /**
     * Helps call file_rewrite_pluginfile_urls with the right parameters.
     *
     * @package  core_question
     * @category files
     * @param string $text text being processed
     * @param string $file the php script used to serve files
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea filearea
     * @param array $ids other IDs will be used to check file permission
     * @param int $itemid item ID
     * @param array $options options
     * @return string
     */
    public static function question_rewrite_question_urls($text, $file, $contextid, $component, $filearea, array $ids, $itemid,
        array $options = null): string {
        $idsstr = '';
        if (!empty($ids)) {
            $idsstr .= implode('/', $ids);
        }
        if ($itemid !== null) {
            $idsstr .= '/' . $itemid;
        }
        return file_rewrite_pluginfile_urls($text, $file, $contextid, $component,
            $filearea, $idsstr, $options);
    }

    /**
     * Rewrite the PLUGINFILE urls in part of the content of a question, for use when
     * viewing the question outside an attempt (for example, in the question bank
     * listing or in the quiz statistics report).
     *
     * @param string $text the question text.
     * @param int $questionid the question id.
     * @param int $filecontextid the context id of the question being displayed.
     * @param string $filecomponent the component that owns the file area.
     * @param string $filearea the file area name.
     * @param int|null $itemid the file's itemid
     * @param int $previewcontextid the context id where the preview is being displayed.
     * @param string $previewcomponent component responsible for displaying the preview.
     * @param array $options text and file options ('forcehttps'=>false)
     * @return string $questiontext with URLs rewritten.
     */
    public static function question_rewrite_question_preview_urls($text, $questionid, $filecontextid, $filecomponent, $filearea, $itemid,
        $previewcontextid, $previewcomponent, $options = null): string {

        $path = "preview/$previewcontextid/$previewcomponent/$questionid";
        if ($itemid) {
            $path .= '/' . $itemid;
        }

        return file_rewrite_pluginfile_urls($text, 'pluginfile.php', $filecontextid,
            $filecomponent, $filearea, $path, $options);
    }

}
