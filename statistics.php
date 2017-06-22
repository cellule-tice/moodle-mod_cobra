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
require_once(__DIR__ . '/lib/cobraremoteservice.php');
require_once(__DIR__ . '/lib/cobracollectionwrapper.php');


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

// Print the page header.

$PAGE->set_url('/mod/cobra/statistics.php', array('id' => $cm->id, 'view' => $view));
$PAGE->set_title(format_string($cobra->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/cobra/css/cobra.css');

echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading('Lecture de textes');

echo $OUTPUT->box_start('Statistics generalbox box-content' );

$out = '';

if (!is_null($cmd)) {
    if ($cmd == 'cleanstats') {
        // Clean Stats.
         $out .= get_string('cleanclickstats', 'cobra');
         $out .= '<span class="warning"> ' . get_string('warningdeletepermanent', 'cobra') . '</span>';
         $thisform = new cobra_clean_statistics_form($_SERVER['PHP_SELF'].'?id=' .$id . '&cmd=exDelete');
         $out = $thisform->display();
    } else if ($cmd == 'exDelete') {
        // Delete Stats.
        $acceptedscopelist = array( 'ALL', 'BEFORE' );

        $scope = optional_param('scope', null, PARAM_ALPHANUM);
        if (!in_array($scope, $acceptedscopelist)) {
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
                if (cobra_clean_stats_before_date ($course->id, $beforedate)) {
                    $mydate = $_REQUEST['before_date']['day'] . '/'.  $_REQUEST['before_date']['month']
                            . '/'. $_REQUEST['before_date']['year'];
                    $out .= '<span class="pre"> Clic stats deleted before ' . $mydate . '</span>';
                }
            } else {
                $out .= '<span class="pre"> date non valide</span>';
            }
        } else if ('ALL' == $scope) {
            // Delete all stats.
            if (cobra_clean_all_stats($course->id)) {
                 $out .= '<span class="pre"> All click stats deleted</span>';
            }
        }
    } // End of exDelete.
}

if ( !is_null( $view ) ) {
    switch ($view) {
        case '1' :
            $out .= html_writer::label(get_string('topclickedentries', 'cobra'), 'topclickedentries');
            $mytable = new html_table();
            $mytable->attributes = array('class' => ' table table-condensed table-hover table-striped');
            $mytable->head = array(
                get_string('totalclicnumber', 'cobra'),
                get_string('entry', 'cobra'),
                get_string('translation', 'cobra'),
                get_string('category')
            );
            $list = cobra_get_clicked_entries ($course->id, 20);
            foreach ($list as $lingentityid => $nb) {
                if ($lingentityid > 0) {
                    list($conceptid, $construction, $entrytype, $category) = cobra_get_concept_info_from_ling_entity($lingentityid);
                    $row = new html_table_row();
                    $row->cells[] = $nb;
                    $row->cells[] = $construction;
                    $row->cells[] = cobra_get_translations( $conceptid, $entrytype );
                    $row->cells[] = $category;
                    $mytable->data[] = $row;
                }
            }
            $out .= html_writer::table($mytable);
            break;

        case '2' :
            $out .= html_writer::label( get_string( 'topclickedentriespertext', 'cobra'), 'topclickedentriespertext');
            $collectionlist = cobra_get_registered_collections( 'all' );
            foreach ($collectionlist as $collection) {
                $textlist = cobra_load_text_list( $collection->id_collection, 'all' );
                $out .= html_writer::start_div();
                $out .= html_writer::label(get_string( 'collection', 'cobra' ) . ' ' . $collection->local_label,
                        $collection->local_label, true, array('class' => 'cobralabel'));
                $table = new html_table();
                $table->attributes = array('style' => 'width:100%');
                $table->head = array(
                    get_string('text', 'cobra'),
                    get_string('clicnumber', 'cobra'),
                    get_string('entry', 'cobra'),
                    get_string('translation', 'cobra'),
                    get_string('category')
                );
                $table->headspan = array(1, 1, 1, 1, 1);

                foreach ($textlist as $textinfo) {
                    $textid = $textinfo->id_text;
                    $texttitle = strip_tags($textinfo->title);
                    $cliclist = $DB->get_records_select('cobra_clic',
                            "course='$course->id' AND id_text='$textid' AND nbclicsstats >= 10",  array(),
                            'nbclicsstats DESC LIMIT 10', 'id_entite_ling, nbclicsstats');

                    $nbmots = 0;
                    foreach ($cliclist as $info2) {
                        $lingentityid = $info2->id_entite_ling;
                        $nbclics  = $info2->nbclicsstats;
                        $nbmots++;
                        $row = new html_table_row();
                        if ($nbmots == 1) {
                            $row->cells[] = $texttitle;
                        } else {
                            $row->cells[] = '&nbsp;';
                        }

                        list($conceptid, $construction, $entrytype, $category)
                                = cobra_get_concept_info_from_ling_entity($lingentityid);
                        $row->cells[] = $nbclics;
                        $row->cells[] = $construction;
                        $row->cells[] = cobra_get_translations($conceptid, $entrytype);
                        $row->cells[] = $category;
                        $table->data[] = $row;
                    }
                }
                $out .= html_writer::table($table);
                $out .= html_writer::end_div();
            }
            break;

        case '3' :
            $out .= html_writer::label(get_string( 'topclickedtexts', 'cobra' ), 'topclickedtexts');
            $collectionlist = cobra_get_registered_collections( 'all' );
            foreach ($collectionlist as $collection) {
                $textlist = cobra_load_text_list( $collection->id_collection, 'all' );
                $textinfo = array();
                foreach ($textlist as $text) {
                    $textinfo[$text->id_text] = $text->title;
                }
                $out .= html_writer::start_div();
                $out .= html_writer::label(get_string( 'collection', 'cobra' ) . ' ' . $collection->local_label,
                        $collection->local_label, true, array('class' => 'cobralabel'));
                $table = new html_table();
                $table->attributes = array('class' => 'emphaseLine');
                $table->head = array(get_string('totalclicnumber', 'cobra'), get_string('text', 'cobra'));
                $nbclicslist = cobra_get_clicked_texts_frequency($course->id);
                foreach ($nbclicslist as $textid => $nbtotalclics) {
                    if ( isset( $textinfo[$textid] ) ) {
                        $row = new html_table_row();
                        $cell = new html_table_cell();
                        $cell->text = $nbtotalclics;
                        $cell->style = "text-align:center";
                        $row->cells[] = $cell;
                        $row->cells[] = strip_tags($textinfo[$textid]);
                        $table->data[] = $row;
                    }
                }
                $out .= html_writer::table($table);
                $out .= html_writer::end_div();
            }
            break;
        case '4' :
            $out .= html_writer::label(get_string( 'statisticspertext', 'cobra' ), 'statisticspertext');
            $collectionlist = cobra_get_registered_collections( 'all' );
            foreach ($collectionlist as $collection) {
                $out .= html_writer::start_div();
                $out .= html_writer::label(get_string( 'collection', 'cobra' ) . ' ' . $collection->local_label,
                        $collection->local_label, true, array('class' => 'cobralabel'));
                $table = new html_table();
                $table->head = array( get_string( 'text', 'cobra'), get_string( 'clickablewordscount', 'cobra'),
                    get_string( 'uniqueusers', 'cobra'),  get_string( 'clickcount', 'cobra'));

                $textlist = cobra_load_text_list( $collection->id_collection, 'all' );
                foreach ($textlist as $text) {
                    $row = new html_table_row();
                    $row->cells[] = strip_tags( $text->title);
                    $row->cells[] = cobra_get_nb_tags_in_text ($text->id_text);
                    $row->cells[] = count(cobra_get_distinct_access_for_text($text->id_text));
                    $row->cells[] = cobra_get_nb_clics_for_text($text->id_text);
                    $table->data[] = $row;
                }
                $out .= html_writer::table($table);
                $out .= html_writer::end_div();
            }
            break;
        case '5' :
             $out .= html_writer::label(get_string( 'statisticsperuser', 'cobra' ), 'statisticsperuse');
            $usercliclist = cobra_get_user_list_for_clic ();
            if (!empty($usercliclist)) {
                $table = new html_table();
                $table->attributes = array('class' => 'emphaseLine textList');
                $table->head = array(get_string( 'user' ), get_string( 'textcount', 'cobra' ), get_string( 'clickcount', 'cobra' ));

                foreach ($usercliclist as $userinfo) {
                    $row = new html_table_row();
                    $cell = new html_table_cell();
                    $cell->text = $userinfo['lastName'] . ' ' . $userinfo['firstName'];
                    $cell->attributes = array('align' => 'left');
                    $row->cells[] = $cell;
                    $cell = new html_table_cell();
                    $cell->text = cobra_get_nb_texts_for_user($userinfo['userId']);
                    $cell->attributes = array('align' => 'center');
                    $row->cells[] = $cell;
                    $cell = new html_table_cell();
                    $cell->text = cobra_get_nb_clic_for_user ($userinfo['userId']);
                    $cell->attributes = array('align' => 'center');
                    $row->cells[] = $cell;
                    $table->data[] = $row;
                }
                if (cobra_has_anonymous_clic()) {
                    $row = new html_table_row();
                    $row->cells[] = get_string('Anonymous', 'cobra');
                    $row->cells[] = cobra_get_nb_texts_for_user('0');
                    $row->cells[] = cobra_get_nb_clic_for_user ('0');
                }
                $out .= html_writer::table($table);
            }
            break;
    }
}

echo $out;

echo $OUTPUT->box_end();

// Finish the page.
echo $OUTPUT->footer();