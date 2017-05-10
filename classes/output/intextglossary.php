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
 * Class containing data for index page
 *
 * @package    local_hackfest
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_cobra\output;

require_once("$CFG->dirroot/webservice/externallib.php");

use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Class containing data for index page
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class intextglossary implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */

    private $cmid;
    private $textid;

    public function __construct($cmid, $textid) {
        //parent::__construct();
        $this->cmid = $cmid;
        $this->textid = $textid;
    }

    public function export_for_template(renderer_base $output) {
        //$data = \core_webservice_external::get_site_info();
        global $COURSE;
        $entries = cobra_get_remote_glossary_info_for_student($this->textid, $COURSE->id);
//print_object($entries);
        $data['currenttime'] = userdate(time()) . ' ' . rand();
        $data['course'] = $COURSE->id;
        $data['cmid'] = $this->cmid;
        $data['entries'] = $entries;

        return $data;
    }
}
