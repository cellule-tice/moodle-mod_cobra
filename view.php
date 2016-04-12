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

// Replace cobra with the name of your module and remove this line.

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... cobra instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('cobra', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $cobra  = $DB->get_record('cobra', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $cobra  = $DB->get_record('cobra', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cobra->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('cobra', $cobra->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/cobra:view', $context);

$event = \mod_cobra\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $cobra);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/cobra/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('cobra-'.$somevar);
 */
$PAGE->requires->css('/mod/cobra/css/cobra.css');
// Add the ajaxcommand for the form.
$PAGE->requires->jquery();
$PAGE->requires->js('/mod/cobra/js/cobra.js');
$PAGE->requires->js_init_call('M.mod_cobra.TextVisibility');
$PAGE->requires->js_init_call('M.mod_cobra.TextMove');
$PAGE->requires->js_init_call('M.mod_cobra.TextChangeType');
// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading(get_string('textreading', 'cobra'));

echo $OUTPUT->box_start('generalbox box-content');


$content = '';
$isallowedtoedit = false;
if (has_capability('mod/cobra:edit', $context)) {
    $isallowedtoedit = true;
    $content .= '<a href="cobra_settings.php?id=' . $id . '">'. get_string('parameters', 'cobra') . '</a> &nbsp; ' .
                '<a href="glossary.php?id=' . $id . '">' . get_string('glossary', 'cobra') . '</a> &nbsp; ' .
                '<a href="stat.php?id=' . $id . '">' . get_string('statistics', 'cobra') . '</a>&nbsp;&nbsp;&nbsp;';
}

$preferences = get_cobra_preferences();
if ('SHOW' == $preferences['show_glossary']) {
    //$content .= '<a href="myglossary.php">' . get_string('myglossary', 'cobra') . '</a>';
    $content .= '<a href="myglossary.php?id=' . $id . '">' . get_string('myglossary', 'cobra') . '</a>';
}

// For all chosen collections display text in selected order.

$collectionlist = $isallowedtoedit ? get_registered_collections('all') : get_registered_collections('visible');
foreach ($collectionlist as $collection) {
    $content .= '<h3>' . $collection['local_label'] . '</h3>';
    $content .= '<table class="table table-condensed table-hover table-striped" id="textlist">' .
                '<thead>' .
                '<tr align="center">' .
                '<th width="1px"> &nbsp; </th>' .
                '<th>' . get_string('text', 'cobra') . '</th>' .
                '<th>' . get_string('source', 'cobra') . '</th>';

    if ($isallowedtoedit ) {
        $content .= '<th>' . get_string ('type', 'cobra') . '</th>' .
                    '<th>' . get_string ('move') . '</th>' .
                    '<th>' . get_string('visibility' , 'cobra') . '</th>';
    }
    $content .= '</tr>' .
                '</thead>';

    if ($isallowedtoedit) {
        // Load all texts to display for course admin.
        $textlist = load_text_list($collection['id_collection'], 'all');
    } else {
        // Load only visible texts to display for students.
        $textlist = load_text_list( $collection['id_collection'], 'visible' );
    }
    if (!empty($textlist) && is_array($textlist)) {
        $content .= '<tbody>';
        $position = 1;
        foreach ($textlist as $text) {
            // Display title.
            $content .= '<tr id="' . $text->id_text . '#textId" class="row" name="' . $position++ .
                        '#pos"><td style="min-width:50%;">' .
                        '<a href="text.php?id='.$id.'&id_text=' . $text->id_text . '#/' . $text->id_text . '">' .
                        '<i class="fa fa-file-text"></i> ' .
                        trim(strip_tags($text->title)) .
                        '</a>' .
                        '</td>' .
            // Display source.
                        '<td style="width: 350px;" title="' . $text->source . '">' .
                        substr($text->source, 0, 30) . '...' .
                        '</td>';

            if ($isallowedtoedit) {
                // Display text type.
                $content .= '<td align="center">' . "\n" . '<a href="#" class="changeType">' .
                            (!empty($text->text_type) ? get_string($text->text_type, 'cobra') : '&nbsp;') .
                            '</a></td>';
                // Change position commands.
                $content .= '<td align="center">';
                $content .= '<a href="#" class="moveUp"><i class="fa fa-arrow-up"></i></a>&nbsp;';
                $content .= '<a href="#" class="moveDown"><i class="fa fa-arrow-down"></i></a>&nbsp;';
                $content .= '</td>';

                // Change visibility commands.
                $content .= '<td align="center">';
                $content .= '<a href="#" class="setVisible" ' . ($text->visibility ? 'style="display:none"' : '').'><i class="fa fa-eye-slash"></i></a>';

                $content .= '<a href="#" class="setInvisible" ' . (!$text->visibility ? 'style="display:none"' : '').'><i class="fa fa-eye"></i></a>';

                $content .= '</td>';
            }
            $content .= '</tr>';
        }
        $content .= '</tbody>';
    } else {
        $content .= '<tfoot>' .
                    '<tr>' .
                    '<td align="center" colspan="' . ($isallowedtoedit ? '4' : '2') . '">' . get_lang('No text') . '</td>' .
                    '</tr>' .
                    '</tfoot>';
    }
    $content .= '</table>';
}

echo $content;


echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();
