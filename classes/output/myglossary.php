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
 * Myglossary renderable definition class.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class myglossary
 */
class myglossary implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */

    /**
     * @var $entries - the list of entries
     */
    private $entries;

    /**
     * @var $course - the current course id
     */
    private $course;

    /**
     * myglossary constructor.
     *
     * @param array $entries
     * @param int $course
     * @param initials_bar $initialsbar
     * @param string $initial
     */
    public function __construct($entries, $course, $initialsbar, $initial) {
        $this->entries = $entries;
        $this->course = $course;
        $this->initialsbar = $initialsbar;
        $this->initial = $initial;
    }

    /**
     * Export this class data for rendering in a template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {

        $data = array();
        $data['entries'] = $this->entries;
        $data['course'] = $this->course;
        $data['initialsbar'] = $this->initialsbar;
        $data['initial'] = $this->initial;
        $data['all'] = $this->initial == 'all';

        return $data;
    }
}