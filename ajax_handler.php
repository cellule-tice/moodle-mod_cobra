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

/**
 * Prints a particular instance of cobra
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_cobra
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Load CoBRA main lib and config.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot . '/mod/cobra/lib/glossary.lib.php');
    global $DB, $USER;

// Define accepted commands.
$acceptedcmdlist = array(
    'getDisplayParams',
    'setVisible',
    'setInvisible',
    'moveUp',
    'moveDown',
    'changeType',
    'removeFromGlossary',
    'getTextListForGlossaryEntry');

if (isset($_REQUEST['ajaxcall']) && in_array($_REQUEST['ajaxcall'], $acceptedcmdlist)) {
    $call = $_REQUEST['ajaxcall'];
}

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... cobra instance ID - it should be named as the first character of the module.

if (isset($_REQUEST['courseId'])) {
    $id = $_REQUEST['courseId'];
}

if ($id) {
    $cm         = get_coursemodule_from_id('cobra', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $cobra  = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $cobra  = $DB->get_record('cobra', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cobra->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('cobra', $cobra->id, $course->id, false, MUST_EXIST);
}

$resource = optional_param('resource_id', null, PARAM_ALPHANUM);
$resourcetype = optional_param('resource_type', null, PARAM_ALPHANUM);
$sibling = optional_param('sibling_id', null, PARAM_ALPHANUM);

// Force headers for export.
header('Content-Type: text/html; charset=iso-8859-1'); // Charset
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past.

if ('getDisplayParams' == $call) {
    $displayprefs = get_cobra_preferences();
    $ccorder = get_corpus_type_display_order();
    $order = implode( ',', $ccorder );
    $displayprefs['ccOrder'] = $order;
    echo json_encode( $displayprefs );
}

if ('setVisible' == $call) {
    if (set_visibility($resource, true, $resourcetype, $course->id)) {
        echo 'true';
        return true;
    }
    return false;
}

if ('setInvisible' == $call) {
    if (set_visibility($resource, false, $resourcetype, $course->id)) {
        echo 'true';
        return true;
    }
    return false;
}

if ('moveDown' == $call) {
    $position = optional_param('position', 0, PARAM_INT);
    if ($position && set_position($sibling, $position++, $resourcetype, $course->id)
        && set_position($resource, $position, $resourcetype, $course->id)) {
        echo 'true';
        return true;
    }
    return false;
}

if ('moveUp' == $call) {
    $position = optional_param('position', 0, PARAM_INT);
    if ($position && set_position($sibling, $position--, $resourcetype, $course->id)
        && set_position($resource, $position, $resourcetype, $course->id)) {
        echo 'true';
        return true;
    }
    return false;
}

if ('changeType' == $call) {
    $textid = optional_param('resource_id', 0, PARAM_INT);
    if (change_text_type($textid, $course->id)) {
        $newtype = get_text_type($textid, $course->id);
        echo get_string($newtype, 'cobra');
        return true;
    }
    return false;
}

if ('removeFromGlossary' == $call) {
    $lingentity = optional_param('lingentity', 0, PARAM_INT);
    $courseid = optional_param('courseid', 0, PARAM_INT);

    if ($lingentity && $courseid) {
        echo true == remove_from_glossary($lingentity, $courseid) ? 'true' : 'false';
    }
}