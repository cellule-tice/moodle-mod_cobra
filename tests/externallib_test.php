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
 * External cobra functions unit tests
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra;

use context_module;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Defines PHPUnit cobra external testcase.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group mod_cobra
 * @covers \mod_cobra\mod_cobra_external
 */
final class externallib_test extends \externallib_advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/cobra/externallib.php');
        parent::setUp();
        $this->resetAfterTest();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course();
        $this->cobra = $this->getDataGenerator()->create_module('cobra', ['course' => $this->course->id]);
        $this->context = context_module::instance($this->cobra->cmid);
        $this->cm = get_coursemodule_from_instance('cobra', $this->cobra->id);

        // Create users.
        $this->student = self::getDataGenerator()->create_user();

        // Users enrolments.
        $this->studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $this->studentrole->id, 'manual');
    }

    public function test_remote_services(): void {

        $this->resetAfterTest(true);

        self::setUser($this->student);
        $this->cobra->encodeclic = true;
        $this->cobra->user = $this->student->id;

        $errors = [];
        try {
            $textdescription = \mod_cobra_external::get_text_returns();
            $result1 = \mod_cobra_external::get_text($this->cobra->text);
            $result1 = \external_api::clean_returnvalue($textdescription, $result1);
            $entrydescription = \mod_cobra_external::get_entry_returns();
            $result2 = \mod_cobra_external::get_entry(22356, false, json_encode($this->cobra));
            $result2 = \external_api::clean_returnvalue($entrydescription, $result2);
            $result3 = \mod_cobra_external::get_entry(23927, true, json_encode($this->cobra));
            $result3 = \external_api::clean_returnvalue($entrydescription, $result3);
            $concordancedesc = \mod_cobra_external::get_full_concordance_returns();
            $result4 = \mod_cobra_external::get_full_concordance(77796);
            $result4 = \external_api::clean_returnvalue($concordancedesc, $result4);
            $collectionlistdesc = \mod_cobra_external::get_collection_list_returns();
            $result5 = \mod_cobra_external::get_collection_list($this->cobra->language);
            $result5 = \external_api::clean_returnvalue($collectionlistdesc, $result5);
            $textlistdesc = \mod_cobra_external::get_text_list_returns();
            $result6 = \mod_cobra_external::get_text_list($this->cobra->collection);
            $result6 = \external_api::clean_returnvalue($textlistdesc, $result6);
        } catch (\invalid_response_exception $e) {
            $errors[] = $e->debuginfo;
        } catch (cobra_remote_access_exception $e) {
            mtrace("Unable to test remote services! Your platform is not registered with CoBRA");
            $site = get_site();
            mtrace($site->shortname);
        }
        $this->assertEmpty($errors, implode('\n', $errors));
    }

    public function test_add_to_glossary(): void {
        global $DB;
        $this->resetAfterTest(true);

        self::setUser($this->student);
        $this->cobra->encodeclic = true;
        $this->cobra->user = $this->student->id;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_cobra');

        $generator->init_local_data();
        $generator->init_user_data($this->student->id, $this->cobra);
        $glossarycount = $DB->count_records('cobra_click',
                [
                    'userid' => $this->student->id,
                    'textid' => $this->cobra->text,
                    'inglossary' => 1,
                ]
        );

        $errors = [];
        try {
            $addtoglossarydesc = \mod_cobra_external::add_to_glossary_returns();

            $result = \mod_cobra_external::add_to_glossary(36638, $this->cobra->text, $this->cobra->course, $this->student->id);

            $result = \external_api::clean_returnvalue($addtoglossarydesc, $result);
            $newglossarycount = $DB->count_records('cobra_click',
                    [
                        'userid' => $this->student->id,
                        'textid' => $this->cobra->text,
                        'inglossary' => 1,
                    ]
            );
            $this->assertEquals($glossarycount + 1, $newglossarycount);
        } catch (\invalid_response_exception $e) {
            $errors[] = $e->debuginfo;
        }
        $this->assertEmpty($errors, implode('\n', $errors));
    }

    public function test_remove_from_glossary(): void {
        global $DB;
        $this->resetAfterTest(true);

        self::setUser($this->student);
        $this->cobra->encodeclic = true;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_cobra');

        $generator->init_local_data();
        $generator->init_user_data($this->student->id, $this->cobra);
        $glossarycount = $DB->count_records('cobra_click',
                ['userid' => $this->student->id, 'textid' => $this->cobra->text, 'inglossary' => 1]);

        $errors = [];
        try {
            $removefromglossdesc = \mod_cobra_external::remove_from_glossary_returns();
            $result = \mod_cobra_external::remove_from_glossary(36515, $this->cobra->course, $this->student->id);

            $result = \external_api::clean_returnvalue($removefromglossdesc, $result);
            $newglossarycount = $DB->count_records('cobra_click',
                    ['userid' => $this->student->id, 'textid' => $this->cobra->text, 'inglossary' => 1]);
            $this->assertEquals($glossarycount - 1, $newglossarycount);
        } catch (\invalid_response_exception $e) {
            $errors[] = $e->debuginfo;
        }
        $this->assertEmpty($errors, implode('\n', $errors));
    }
}
