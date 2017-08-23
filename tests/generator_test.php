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

defined('MOODLE_INTERNAL') || die();


/**
 * PHPUnit cobra generator testcase
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_cobra_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('cobra'));

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('url', array('course' => $course->id)));
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_cobra');
        $this->assertInstanceOf('mod_cobra_generator', $generator);
        $this->assertEquals('cobra', $generator->get_modulename());

        $generator->create_instance(array('course' => $course->id));
        $generator->create_instance(array('course' => $course->id));
        $cobra = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(3, $DB->count_records('cobra'));

        $cm = get_coursemodule_from_instance('cobra', $cobra->id);
        $this->assertEquals($cobra->id, $cm->instance);
        $this->assertEquals('cobra', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($cobra->cmid, $context->instanceid);
    }
}
