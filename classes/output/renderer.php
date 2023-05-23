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
 * Renderer class for cobra activity.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_cobra\output;

use plugin_renderer_base;

/**
 * Renderer class definition.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Defer to template.
     *
     * @param index_page $page
     *
     * @return string html for the page
     */
    public function render_cobratext($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_cobra/cobratext', $data);
    }

    /**
     * Defer to template.
     *
     * @param page $page
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_myglossary($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_cobra/myglossary', $data);
    }

    /**
     * Defer to template.
     *
     * @param index_page $page
     *
     * @return string html for the page
     */
    public function render_glossary_action_menu($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_cobra/glossary_action_menu', $data);
    }
}
