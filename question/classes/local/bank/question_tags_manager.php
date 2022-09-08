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
 * Manager class to handle question tags and its associated operations.
 *
 * @package    core_question
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_tags_manager {

    /**
     * Sort question tags by course or normal tags.
     *
     * This function also search tag instances that may have a context id that don't match either a course or
     * question context and fix the data setting the correct context id.
     *
     * @param \stdClass[] $tagobjects The tags for the given $question.
     * @param \stdClass $categorycontext The question categories context.
     * @param \stdClass[]|null $filtercourses The courses to filter the course tags by.
     * @return \stdClass $sortedtagobjects Sorted tag objects.
     */
    public static function question_sort_tags($tagobjects, $categorycontext, $filtercourses = null): \stdClass {

        // Questions can have two sets of tag instances. One set at the
        // course context level and another at the context the question
        // belongs to (e.g. course category, system etc).
        $sortedtagobjects = new \stdClass();
        $sortedtagobjects->coursetagobjects = [];
        $sortedtagobjects->coursetags = [];
        $sortedtagobjects->tagobjects = [];
        $sortedtagobjects->tags = [];
        $taginstanceidstonormalise = [];
        $filtercoursecontextids = [];
        $hasfiltercourses = !empty($filtercourses);

        if ($hasfiltercourses) {
            // If we're being asked to filter the course tags by a set of courses
            // then get the context ids to filter below.
            $filtercoursecontextids = array_map(function($course) {
                return \context_course::instance($course->id)->id;
            }, $filtercourses);
        }

        foreach ($tagobjects as $tagobject) {
            $tagcontextid = $tagobject->taginstancecontextid;
            $tagcontext = \context::instance_by_id($tagcontextid);
            $tagcoursecontext = $tagcontext->get_course_context(false);
            // This is a course tag if the tag context is a course context which
            // doesn't match the question's context. Any tag in the question context
            // is not considered a course tag, it belongs to the question.
            $iscoursetag = $tagcoursecontext
                && $tagcontext->id == $tagcoursecontext->id
                && $tagcontext->id != $categorycontext->id;

            if ($iscoursetag) {
                // Any tag instance in a course context level is considered a course tag.
                if (!$hasfiltercourses || in_array($tagcontextid, $filtercoursecontextids)) {
                    // Add the tag to the list of course tags if we aren't being
                    // asked to filter or if this tag is in the list of courses
                    // we're being asked to filter by.
                    $sortedtagobjects->coursetagobjects[] = $tagobject;
                    $sortedtagobjects->coursetags[$tagobject->id] = $tagobject->get_display_name();
                }
            } else {
                // All non course context level tag instances or tags in the question
                // context belong to the context that the question was created in.
                $sortedtagobjects->tagobjects[] = $tagobject;
                $sortedtagobjects->tags[$tagobject->id] = $tagobject->get_display_name();

                // Due to legacy tag implementations that don't force the recording
                // of a context id, some tag instances may have context ids that don't
                // match either a course context or the question context. In this case
                // we should take the opportunity to fix up the data and set the correct
                // context id.
                if ($tagcontext->id != $categorycontext->id) {
                    $taginstanceidstonormalise[] = $tagobject->taginstanceid;
                    // Update the object properties to reflect the DB update that will
                    // happen below.
                    $tagobject->taginstancecontextid = $categorycontext->id;
                }
            }
        }

        if (!empty($taginstanceidstonormalise)) {
            // If we found any tag instances with incorrect context id data then we can
            // correct those values now by setting them to the question context id.
            \core_tag_tag::change_instances_context($taginstanceidstonormalise, $categorycontext);
        }

        return $sortedtagobjects;
    }

    /**
     * This function will handle moving all tag instances to a new context for a
     * given list of questions.
     *
     * Questions can be tagged in up to two contexts:
     * 1.) The context the question exists in.
     * 2.) The course context (if the question context is a higher context.
     *     E.g. course category context or system context.
     *
     * This means a question that exists in a higher context (e.g. course cat or
     * system context) may have multiple groups of tags in any number of child
     * course contexts.
     *
     * Questions in the course category context can be move "down" a context level
     * into one of their child course contexts or activity contexts which affects the
     * availability of that question in other courses / activities.
     *
     * In this case it makes the questions no longer available in the other course or
     * activity contexts so we need to make sure that the tag instances in those other
     * contexts are removed.
     *
     * @param \stdClass[] $questions The list of question being moved (must include
     *                              the id and contextid)
     * @param \context $newcontext The Moodle context the questions are being moved to
     */
    public static function question_move_question_tags_to_new_context(array $questions, \context $newcontext): void {
        // If the questions are moving to a new course/activity context then we need to
        // find any existing tag instances from any unavailable course contexts and
        // delete them because they will no longer be applicable (we don't support
        // tagging questions across courses).
        $instancestodelete = [];
        $instancesfornewcontext = [];
        $newcontextparentids = $newcontext->get_parent_context_ids();
        $questionids = array_map(function($question) {
            return $question->id;
        }, $questions);
        $questionstagobjects = \core_tag_tag::get_items_tags('core_question', 'question', $questionids);

        foreach ($questions as $question) {
            $tagobjects = $questionstagobjects[$question->id] ?? [];

            foreach ($tagobjects as $tagobject) {
                $tagid = $tagobject->taginstanceid;
                $tagcontextid = $tagobject->taginstancecontextid;
                $istaginnewcontext = $tagcontextid == $newcontext->id;
                $istaginquestioncontext = $tagcontextid == $question->contextid;

                if ($istaginnewcontext) {
                    // This tag instance is already in the correct context so we can
                    // ignore it.
                    continue;
                }

                if ($istaginquestioncontext) {
                    // This tag instance is in the question context so it needs to be
                    // updated.
                    $instancesfornewcontext[] = $tagid;
                    continue;
                }

                // These tag instances are in neither the new context nor the
                // question context so we need to determine what to do based on
                // the context they are in and the new question context.
                $tagcontext = \context::instance_by_id($tagcontextid);
                $tagcoursecontext = $tagcontext->get_course_context(false);
                // The tag is in a course context if get_course_context() returns
                // itself.
                $istaginstancecontextcourse = !empty($tagcoursecontext)
                    && $tagcontext->id == $tagcoursecontext->id;

                if ($istaginstancecontextcourse) {
                    // If the tag instance is in a course context we need to add some
                    // special handling.
                    $tagcontextparentids = $tagcontext->get_parent_context_ids();
                    $isnewcontextaparent = in_array($newcontext->id, $tagcontextparentids);
                    $isnewcontextachild = in_array($tagcontext->id, $newcontextparentids);

                    if ($isnewcontextaparent) {
                        // If the tag instance is a course context tag and the new
                        // context is still a parent context to the tag context then
                        // we can leave this tag where it is.
                        continue;
                    } else if ($isnewcontextachild) {
                        // If the new context is a child context (e.g. activity) of this
                        // tag instance then we should move all of this tag instance
                        // down into the activity context along with the question.
                        $instancesfornewcontext[] = $tagid;
                    } else {
                        // If the tag is in a course context that is no longer a parent
                        // or child of the new context then this tag instance should be
                        // removed.
                        $instancestodelete[] = $tagid;
                    }
                } else {
                    // This is a catch all for any tag instances not in the question
                    // context or a course context. These tag instances should be
                    // updated to the new context id. This will clean up old invalid
                    // data.
                    $instancesfornewcontext[] = $tagid;
                }
            }
        }

        if (!empty($instancestodelete)) {
            // Delete any course context tags that may no longer be valid.
            \core_tag_tag::delete_instances_by_id($instancestodelete);
        }

        if (!empty($instancesfornewcontext)) {
            // Update the tag instances to the new context id.
            \core_tag_tag::change_instances_context($instancesfornewcontext, $newcontext);
        }
    }

}
