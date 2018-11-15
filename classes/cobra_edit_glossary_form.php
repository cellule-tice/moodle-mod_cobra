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
 * Cobra edit glossary class definition.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra;

use moodleform;

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->libdir . '/formslib.php');

/**
 * Class cobra_edit_glossary_form
 *
 * @package    mod_cobra
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cobra_edit_glossary_form extends moodleform {
    /**
     * Form definition.
     *
     * @throws coding_exception
     */
    public function definition() {
        $mform = $this->_form;
        // 1st argument is group name, 2nd is link text, 3rd is attributes and 4th is original value.
        $this->add_checkbox_controller(1, null, null, 1);
        $textlist = $this->_customdata['textlist'];
        $compare = $this->_customdata['compare'];
        foreach ($textlist as $text) {
            $mform->addElement('advcheckbox', 'text_' . $text->id, '',
                htmlspecialchars(strip_tags($text->name)), array('group' => 1));
            $mform->setDefault('text_' . $text->id, 1);

        }
        if ($compare) {
            $mform->addElement('textarea', 'mytext', get_string('mytext', 'cobra'), array('rows' => 30, 'cols' => 80));
        }
        $this->add_action_buttons(true, get_string('OK', 'cobra'));
    }
}