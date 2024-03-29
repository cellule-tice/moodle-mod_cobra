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

namespace mod_cobra\output;

use moodle_url;
use renderer_base;
use templatable;
use renderable;
use url_select;

/**
 * Rendered HTML elements for tertiary nav for glossary actions.
 *
 * @package   mod_cobra
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class glossary_action_menu implements templatable, renderable {
    /** @var moodle_url */
    private $currenturl;

    /**
     * qbank_actionbar constructor.
     *
     * @param moodle_url $currenturl The current URL.
     */
    public function __construct(moodle_url $currenturl) {
        $this->currenturl = $currenturl;
    }

    /**
     * Provides the data for the template.
     *
     * @param renderer_base $output renderer_base object.
     * @return array data for the template
     */
    public function export_for_template(renderer_base $output): array {
        $params = $this->currenturl->params();
        $params['cmd'] = 'rqexport';
        $exportlink = new moodle_url('/mod/cobra/glossary.php', $params);
        $params['cmd'] = 'rqcompare';
        $comparelink = new moodle_url('/mod/cobra/glossary.php', $params);

        $menu = [
            $exportlink->out(false) => get_string('exportglossary', 'mod_cobra'),
            $comparelink->out(false) => get_string('comparetextwithglossary', 'mod_cobra'),
        ];

        $urlselect = new url_select($menu, $this->currenturl->out(false), null, 'cobraaction');
        $urlselect->set_label(get_string('cobranavigation', 'mod_cobra'), ['class' => 'accesshide']);

        return [
            'glossaryactionselect' => $urlselect->export_for_template($output),
        ];
    }
}
