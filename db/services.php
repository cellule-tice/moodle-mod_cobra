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


$functions = array(
    'mod_cobra_add_to_glossary' => array(
        'classname' => 'mod_cobra_external',
        'methodname' => 'add_to_glossary',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Add entry to personal glossary',
        'type' => 'write',
        'ajax' => true
    ),
    'mod_cobra_remove_from_glossary' => array(
        'classname' => 'mod_cobra_external',
        'methodname' => 'remove_from_glossary',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Remove entry from personal glossary',
        'type' => 'write',
        'ajax' => true
    ),
    'mod_cobra_load_glossary' => array(
        'classname' => 'mod_cobra_external',
        'methodname' => 'load_glossary',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Load glossary entries for current text',
        'type' => 'read',
        'ajax' => true
    )
);

$services = array(
    'cobraservices' => array(
        'shortname' => 'cobra',
        'functions' => array(
            'mod_cobra_add_to_glossary',
            'mod_cobra_remove_from_glossary',
            'mod_cobra_load_glossary'
        ),
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);