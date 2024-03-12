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
 * Privacy provider tests
 *
 * @package    mod_cobra
 * @category   test
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use mod_cobra\privacy\provider;

/**
 * Unit tests for mod/cobra/classes/privacy/
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group mod_cobra
 */
class mod_cobra_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {
    /** @var stdClass The first student object. */
    protected $student1;

    /** @var stdClass The second student object. */
    protected $student2;

    /** @var stdClass[] The first cobra object. */
    protected $cobra1;

    /** @var stdClass[] The second cobra object. */
    protected $cobra2;

    /** @var stdClass The course object. */
    protected $course;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
        global $DB;

        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $this->cobra1 = $this->getDataGenerator()->create_module('cobra', ['course' => $this->course->id]);
        $this->cobra2 = $this->getDataGenerator()->create_module('cobra', ['course' => $this->course->id]);

        // Create a student who will add entries to their personal glossary.
        $this->student1 = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($this->student1->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->student2->id, $this->course->id, $studentrole->id);

        self::setUser($this->student1);
        $this->cobra1->encodeclic = true;
        $this->cobra2->encodeclic = true;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_cobra');

        $generator->init_local_data();
        $generator->init_user_data($this->student1->id, $this->cobra1);
        $generator->init_user_data($this->student1->id, $this->cobra2);
        $generator->init_user_data($this->student2->id, $this->cobra1);
        $generator->init_user_data($this->student2->id, $this->cobra2);
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('mod_cobra');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(1, $itemcollection);

        $table = array_shift($itemcollection);
        $this->assertEquals('cobra_click', $table->get_name());
        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('course', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('lingentity', $privacyfields);
        $this->assertArrayHasKey('textid', $privacyfields);
        $this->assertArrayHasKey('nbclicks', $privacyfields);
        $this->assertArrayHasKey('timecreated', $privacyfields);
        $this->assertArrayHasKey('inglossary', $privacyfields);
        $this->assertEquals('privacy:metadata:cobra_click', $table->get_summary());
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $cms = [
            get_coursemodule_from_instance('cobra', $this->cobra1->id),
            get_coursemodule_from_instance('cobra', $this->cobra2->id),
        ];
        $expectedctxs = [
            context_module::instance($cms[0]->id),
            context_module::instance($cms[1]->id),
        ];

        $expectedctxids = [];
        foreach ($expectedctxs as $ctx) {
            $expectedctxids[] = $ctx->id;
        }
        $contextlist = provider::get_contexts_for_userid($this->student1->id)->get_contextids();

        $this->assertCount(2, $contextlist);
        $uctxids = [];
        foreach ($contextlist as $uctx) {
            $uctxids[] = $uctx;
        }
        $this->assertEmpty(array_diff($expectedctxids, $uctxids));
        $this->assertEmpty(array_diff($uctxids, $expectedctxids));
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context() {
        $cms = [
            get_coursemodule_from_instance('cobra', $this->cobra1->id),
        ];
        $ctxs = [
            context_module::instance($cms[0]->id),
        ];
        // Export all of the data for the context.
        $this->export_context_data_for_user($this->student1->id, $ctxs[0], 'mod_cobra');
        $writer = \core_privacy\local\request\writer::with_context($ctxs[0]);
        $this->assertTrue($writer->has_any_data());
    }


    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;

        $cms = [
            get_coursemodule_from_instance('cobra', $this->cobra1->id),
            get_coursemodule_from_instance('cobra', $this->cobra2->id),
        ];
        $ctxs = [];
        foreach ($cms as $cm) {
            $ctxs[] = context_module::instance($cm->id);
        }

        // Before deletion, we should have 6 items for both student1 and student2.
        $this->assertEquals(6, $DB->count_records('cobra_click', ['userid' => $this->student1->id]));
        $this->assertEquals(6, $DB->count_records('cobra_click', ['userid' => $this->student2->id]));

        // Delete the data for student.
        $contextlist = new \core_privacy\local\request\approved_contextlist($this->student1, 'cobra',
                                                                            [$ctxs[0]->id, $ctxs[1]->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion, we should have no items for student1 but still 6 for student2.
        $this->assertEquals(0, $DB->count_records('cobra_click', ['userid' => $this->student1->id]));
        $this->assertEquals(6, $DB->count_records('cobra_click', ['userid' => $this->student2->id]));
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        // Before deletion, we should have 12 entries in cobra_click table,
        // 6 for student1 (3 cobra1, 3 cobra2) and 6 for student2 (same).
        $this->assertEquals(12, $DB->count_records('cobra_click'));
        $this->assertEquals(6, $DB->count_records('cobra_click', ['userid' => $this->student1->id]));
        $this->assertEquals(6, $DB->count_records('cobra_click', ['userid' => $this->student2->id]));
        $this->assertEquals(3, $DB->count_records('cobra_click', ['userid' => $this->student1->id, 'cobra' => $this->cobra1->id]));
        $this->assertEquals(3, $DB->count_records('cobra_click', ['userid' => $this->student2->id, 'cobra' => $this->cobra1->id]));
        $this->assertEquals(3, $DB->count_records('cobra_click', ['userid' => $this->student1->id, 'cobra' => $this->cobra2->id]));
        $this->assertEquals(3, $DB->count_records('cobra_click', ['userid' => $this->student2->id, 'cobra' => $this->cobra2->id]));

        // Delete data from the first checklist.
        $cm = get_coursemodule_from_instance('cobra', $this->cobra1->id);
        $cmcontext = context_module::instance($cm->id);
        provider::delete_data_for_all_users_in_context($cmcontext);
        // After deletion, there should be no items for cobra1
        // but still 3 items for student1 in cobra2 and 3 items for student2 in cobra2.
        $this->assertEquals(0, $DB->count_records('cobra_click', ['cobra' => $this->cobra1->id]));
        $this->assertEquals(3, $DB->count_records('cobra_click', ['userid' => $this->student1->id, 'cobra' => $this->cobra2->id]));
        $this->assertEquals(3, $DB->count_records('cobra_click', ['userid' => $this->student2->id, 'cobra' => $this->cobra2->id]));
    }
}
