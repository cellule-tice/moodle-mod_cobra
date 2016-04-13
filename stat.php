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

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/cobraremoteservice.class.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.class.php');


$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
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

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

if (!has_capability('mod/cobra:edit', $context)) {
      redirect('view.php?id='.$cm->id);
}

$view = optional_param('view', null, PARAM_ALPHANUM);
$cmd = optional_param('cmd', null, PARAM_ALPHANUM);

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
$PAGE->requires->css('/mod/cobra/css/cobra.css');

echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading('Lecture de textes');

echo $OUTPUT->box_start('Statistics generalbox box-content' );

$out = '';

if (!is_null($cmd)) {
    if ($cmd == 'cleanStats') {
        // Clean Stats.
         $out .= get_string('Clean_Clic_Stats', 'cobra');
         $out .= '<span class="warning"> ' . get_string('Delete_is_definitive._No_way_to_rollback', 'cobra') . '</span>';
         $thisform = new mod_cleanStats_form($_SERVER['PHP_SELF'].'?id=' .$id . '&cmd=exDelete');
         $out = $thisform->display();
    } else if ($cmd == 'exDelete') {
        // Delete Stats.
        $acceptedscopelist = array( 'ALL', 'BEFORE' );

        $scope = optional_param('scope', null, PARAM_ALPHANUM);
        if (!in_array($sope, $acceptedscopelist)) {
            $scope = null;
        }
        // Get date.
        if ( isset($_REQUEST['beforeDate'])
            && is_array($_REQUEST['beforeDate'])
            && array_key_exists('day', $_REQUEST['beforeDate']) && array_key_exists('month', $_REQUEST['beforeDate'])
            && array_key_exists('year', $_REQUEST['beforeDate'])
            && (bool) checkdate( $_REQUEST['beforeDate']['month'], $_REQUEST['beforeDate']['day'], $_REQUEST['beforeDate']['year'] )
            ) {
            $beforedate = mktime(0, 0, 0, $_REQUEST['beforeDate']['month'], $_REQUEST['beforeDate']['day'],
                    $_REQUEST['beforeDate']['year'] );
            $mydate = $_REQUEST['beforeDate']['day'] . '/'.  $_REQUEST['beforeDate']['month']
                    . '/'. $_REQUEST['beforeDate']['year'];
        } else {
            $beforedate = null;
        }

        if ('BEFORE' == $scope) {
            $beforedate = null;
            if (isset($_REQUEST['before_date'])) {
                 $beforedate = mktime(0, 0, 0, $_REQUEST['before_date']['month'], $_REQUEST['before_date']['day'],
                         $_REQUEST['before_date']['year'] );
            }
            if ( !is_null($beforedate) ) {
                // Execute delete before date.
                if (clean_stats_before_date ($course->id, $beforedate)) {
                    $mydate = $_REQUEST['before_date']['day'] . '/'.  $_REQUEST['before_date']['month']
                            . '/'. $_REQUEST['before_date']['year'];
                    $out .= '<span class="pre"> Clic stats deleted before ' . $mydate . '</span>';
                }
            } else {
                $out .= '<span class="pre"> date non valide</span>';
            }
        } else if ('ALL' == $scope) {
            // Delete all stats.
            if (clean_all_stats($course->id)) {
                 $out .= '<span class="pre"> All click stats deleted</span>';
            }
        }
    } // End of exDelete.
} else {

    $out .= '<ul>';
    $out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=1">'
            . get_string( 'Display_entries_clicked_at_least_20_times' , 'cobra') . '</a></li>';
    $out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=2">'
            . get_string( 'Display_the_10_most_frequently_clicked_entries_per_text', 'cobra' ) . '</a></li>';
    $out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=3">'
            . get_string( 'Display_the_most_frequently_analysed_texts', 'cobra' ) . '</a></li>';
    $out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=4">'
            . get_string( 'Display_statistics_by_text' , 'cobra') . '</a></li>';
    $out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&view=5">'
            . get_string( 'Display_statistics_by_user', 'cobra' ) . '</a></li>';
    $out .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?id='.$id.'&cmd=cleanStats">'
            . get_string('Clean_Clic_Stats', 'cobra') .'</a></li>';
    $out .= '</ul>';
}

if ( !is_null( $view ) ) {
    switch ($view) {
        case '1' :
            $out .= '<h3><small>' . get_string( 'Display_entries_clicked_at_least_20_times', 'cobra' ) . '</small></h3>';
            $out .= '<table class="table table-condensed table-hover table-striped">'
                 .  '<thead>'
                 .  '<tr class="headerX">'
                 .  '<th> Nombre total de clics </th>'
                 .  '<th>' . get_string( 'Lemma', 'cobra' ) . '</th>'
                 .  '<th>' . get_string( 'Translation', 'cobra' ) . '</th>'
                 .  '<th>' . get_string( 'Category', 'cobra' ) . '</th>'
                 .  '</tr>'
                 .  '</thead>';

            $list = get_clicked_entries ($course->id, 20);
            foreach ($list as $lingentityid => $nb) {
                list( $conceptid, $construction, $entrytype, $category ) = get_concept_info_from_ling_entity($lingentityid);
                $out .= '<tr>'
                     .  '<td>' . $nb . '</td>'
                     .  '<td>' . $construction . '</td>'
                     .  '<td>' . get_translations( $conceptid, $entrytype ) . '</td>'
                     .  '<td>' . $category . '</td>'
                     .  '</tr>';
            }
            $out .= '</table>';
            break;

        case '2' :
            $out .= '<h3><small>' . get_string( 'Display_the_10_most_frequently_clicked_entries_per_text', 'cobra' )
                . '</small></h3>';
            $collectionlist = get_registered_collections( 'all' );
            foreach ($collectionlist as $collection) {
                $textlist = load_text_list( $collection['id_collection'], 'all' );

                $out .= '<table>'
                     .  '<thead>'
                     .  '<tr class="superHeader"><th colspan="5">' . get_string( 'Collection', 'cobra' ) . '&nbsp;:&nbsp;'
                        . $collection['local_label'] . '</th></tr>'
                     .  '<tr class="headerX">'
                     .  '<th> Texte </th>'
                     .  '<th> Nombre de clics </th>'
                     .  '<th>' . get_string( 'Lemma', 'cobra' ) . '</th>'
                     .  '<th>' . get_string( 'Translation', 'cobra' ) . '</th>'
                     .  '<th>' . get_string( 'Category', 'cobra' ) . '</th>'
                     .  '</tr>'
                     .  '</thead>';


                foreach ($textlist as $textinfo) {
                    $textid = $textinfo->id_text;
                    $texttitle = $textinfo->title;
                    $cliclist = $DB->get_records_select('cobra_clic',
                            "course='$course->id' AND id_text='$textid' AND nb_clics >= 10",  array(),
                            'nb_clics DESC LIMIT 10', 'id_entite_ling, nb_clics');

                    $nbmots = 0;
                    foreach ($cliclist as $info2) {
                        $lingentityid = $info2->id_entite_ling;
                        $nbclics  = $info2->nb_clics;
                        $nbmots++;
                        $out .= '<tr><td>';
                        if ($nbmots == 1) {
                            $out .= $texttitle;
                        }
                        $out .= '&nbsp; </td>';

                        list( $conceptid, $construction, $entrytype, $category ) = get_concept_info_from_ling_entity($lingentityid);

                        $out .= '<td>' . $nbclics  . '</td>'
                             .  '<td>' . $construction . '</td>'
                             .  '<td>' . get_translations($conceptid, $entrytype) . '</td>'
                             .  '<td>' . $category . '</td>'
                             .  '</tr>';
                    }
                }
                $out .= '</table>';
            }
            break;

        case '3' :
            $out .= '<h3><small>' . get_string( 'Display_the_most_frequently_analysed_texts', 'cobra' ) . '</small></h3>';
            $collectionlist = get_registered_collections( 'all' );
            foreach ($collectionlist as $collection) {
                $textlist = load_text_list( $collection['id_collection'], 'all' );
                $textinfo = array();
                foreach ($textlist as $text) {
                    $textinfo[$text->id_text] = $text->title;
                }

                $out .= '<table class="claroTable emphaseLine">'
                     .  '<thead>'
                     .  '<tr class="superHeader"><th colspan="2">' . get_string( 'Collection', 'cobra' ) . '&nbsp;:&nbsp;'
                        . $collection['local_label'] . '</th></tr>'
                     .  '<tr class="headerX">'
                     .  '<th> Nombre total de clics </th>'
                     .  '<th> Texte </th>'
                     .  '</tr>'
                     .  '</thead>';
                $nbclicslist = get_clicked_texts_frequency($course->id);
                foreach ($nbclicslist as $textid => $nbtotalclics) {
                    if ( isset( $textinfo[$textid] ) ) {
                        $out .= '<tr>'
                             .  '<td>' . $nbtotalclics . '</td>'
                             .  '<td>' . $textinfo[$textid] . '</td>'
                             .  '</tr>';
                    }
                }
                $out .= '</table>';
            }
            break;
        case '4' :
            $collectionlist = get_registered_collections( 'all' );
            foreach ($collectionlist as $collection) {
                $out .= '<table class="claroTable emphaseLine textList" width="100%" border="0" '.
                        'cellspacing="2" style="margin-bottom:20px;">'
                      . "\n"
                .  '<thead>' . "\n"
                .  '<tr class="superHeader" align="center" valign="top"><th colspan="4">' . $collection['local_label']
                . '</th></tr>' . "\n"
                .  '<tr class="headerX" align="center" valign="top">' . "\n"
                .  '<th>' . get_string( 'Text', 'cobra' ) . '</th>' . "\n"
                .  '<th>' . get_string( 'Nb_of_clickable_words', 'cobra' ) . '</th>' . "\n"
                .  '<th>' . get_string( 'Different_users', 'cobra' ) . '</th>' . "\n"
                .  '<th>' . get_string( 'Total_clic', 'cobra' ) . '</th>' . "\n";
                $textlist = load_text_list( $collection['id_collection'], 'all' );
                foreach ($textlist as $text) {
                    $out .= '<tr> <td>' . $text->title. '</td>' . "\n"
                            . '<td>' . get_nb_tags_in_text ($text->id_text) . '</td>' . "\n"
                            . '<td> ' . count(get_distinct_access_for_text($text->id_text)).' </td>' . "\n"
                            . '<td> ' . get_nb_clics_for_text($text->id_text). '</td> </tr>' . "\n";
                }
                $out .= '</table>';
            }
            break;
        case '5' :
            $usercliclist = get_user_list_for_clic ();
            if (!empty($usercliclist)) {
                $out .= '<table class="claroTable emphaseLine textList" width="100%" border="0" '
                      . 'cellspacing="2" style="margin-bottom:20px;">' . "\n"
                .  '<thead>' . "\n"
                .  '<tr class="headerX" align="center" valign="top">' . "\n"
                .  '<th>' . get_string( 'User', 'cobra' ) . '</th>' . "\n"
                .  '<th>' . get_string( 'Nb_Texts', 'cobra' ) . '</th>' . "\n"
                .  '<th>' . get_string( 'Total_clic', 'cobra' ) . '</th>' . "\n";

                foreach ($usercliclist as $userinfo) {
                    $out .= '<tr> <td> '. $userinfo['lastName'] . ' ' . $userinfo['firstName'] . '</td>' . "\n"
                         . '<td> ' . get_nb_texts_for_user($userinfo['userId']) . '</td>' . "\n"
                         . '<td> ' . get_nb_clic_for_user ($userinfo['userId']) . '</td></tr>'  . "\n";
                }
                if (has_anonymous_clic()) {
                    $out .= '<tr> <td> '. get_string('Anonymous', 'cobra') . '</td>' . "\n"
                         . '<td> ' . get_nb_texts_for_user('0') . '</td>' . "\n"
                         . '<td> ' . get_nb_clic_for_user ('0') . '</td></tr>'  . "\n";
                }
                $out .= '</table>';
            }
            break;
    }
}

echo $out;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();