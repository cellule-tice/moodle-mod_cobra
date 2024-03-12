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
 * CoBRA external functions and service definitions.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_cobra_get_entry' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'get_entry',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Display entry details and concordances',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_cobra_get_full_concordance' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'get_full_concordance',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Get full text of clicked concordance',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_cobra_add_to_glossary' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'add_to_glossary',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Add entry to personal glossary',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_cobra_remove_from_glossary' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'remove_from_glossary',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Remove entry from personal glossary',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_cobra_load_glossary' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'load_glossary',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Load glossary entries for current text',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_cobra_get_text' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'get_text',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Loads formatted title and text content',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_cobra_get_collection_list' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'get_collection_list',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Collects the list of collections for set language',
        'type' => 'read',
    ],
    'mod_cobra_get_text_list' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'get_text_list',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Collects the list of texts for given collection',
        'type' => 'read',
    ],
    'mod_cobra_get_demo_api_key' => [
        'classname' => 'mod_cobra_external',
        'methodname' => 'get_demo_api_key',
        'classpath' => 'mod/cobra/externallib.php',
        'description' => 'Request a demo API key from Cobra server',
        'type' => 'write',
        'ajax' => true,
    ],
];

$services = [
    'cobraservices' => [
        'shortname' => 'cobra',
        'functions' => [
            'mod_cobra_get_entry',
            'mod_cobra_get_full_concordance',
            'mod_cobra_get_text',
            'mod_cobra_add_to_glossary',
            'mod_cobra_remove_from_glossary',
            'mod_cobra_load_glossary',
            'mod_cobra_get_text_list',
            'mod_cobra_get_collection_list',
        ],
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
