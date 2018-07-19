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
 * Cobratext renderable definition.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class cobratext
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cobratext implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */

    /**
     * @var stdClass Cobra instance.
     */
    private $cobra;

    /**
     * Cobratext constructor.
     * @param stdClass $cobra
     */
    public function __construct($cobra) {
        $this->cobra = $cobra;
    }

    /**
     * Export this class data for rendering in a template.
     *
     * @param renderer_base $output
     * @return mixed
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output) {

        $params = array('id_text' => $this->cobra->text);

        try {
            $textdata = json_decode(cobra_remote_service::call('get_text', $params));
        } catch (cobra_remote_access_exception $e) {
            global $COURSE;
            redirect(new moodle_url('/course/view.php', array('id' => $COURSE->id)),
                    'CoBRA' . ': ' . get_string($e->debuginfo, 'cobra') . '<br/>' . get_string('pageshouldredirect'),
                    5, \core\output\notification::NOTIFY_ERROR);
        }

        $textdata->userglossary = (int)$this->cobra->userglossary;
        if (!(int)$this->cobra->audioplayer) {
            unset($textdata->audiofile);
        }

        $textdata->entries = cobra_get_student_glossary($this->cobra->user, $this->cobra->course, $this->cobra->text);

        return $textdata;
    }
}
