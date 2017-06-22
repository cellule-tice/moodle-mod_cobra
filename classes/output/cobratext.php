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
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/webservice/externallib.php");
require_once("$CFG->dirroot/mod/cobra/lib/cobraremoteservice.php");

class cobratext implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */

    private $cobra;

    public function __construct($cobra) {
        $this->cobra = $cobra;
    }

    public function export_for_template(renderer_base $output) {

        $params = array('id_text' => $this->cobra->text);
        $data = array();
        $textobj = new cobra_text_wrapper($this->cobra->text);
        $textobj->set_text_id($this->cobra->text);
        if ($this->cobra->audioplayer) {
            $data['audio'] = $textobj->get_audio_file_url();
        }

        $text = cobra_remote_service::call('getFormattedText', $params, 'json', true);
        $data['text'] = utf8_encode($text);
        $data['userglossary'] = (int)$this->cobra->userglossary;
        $data['entries'] = cobra_get_student_cached_glossary($this->cobra->user, $this->cobra->course, $this->cobra->text);
        $data['cmid'] = $this->cobra->cmid;

        return $data;
    }
}
