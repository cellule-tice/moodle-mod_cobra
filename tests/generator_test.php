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
 * PHPUnit cobra generator test
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @author     Laurence Dumortier
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_cobra;

use context_module;

/**
 * Defines PHPUnit cobra_generator testcase.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group mod_cobra
 * @covers \mod_cobra\mod_cobra_generator
 */
class generator_test extends \advanced_testcase {
    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        $config = get_config('mod_cobra');

        $this->assertEquals(0, $DB->count_records('cobra'));

        $course = $this->getDataGenerator()->create_course();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_cobra');
        $this->assertInstanceOf('mod_cobra_generator', $generator);
        $this->assertEquals('cobra', $generator->get_modulename());

        $generator->create_instance(['course' => $course->id]);
        $generator->create_instance(['course' => $course->id]);
        $cobra = $generator->create_instance(['course' => $course->id]);
        $this->assertEquals(3, $DB->count_records('cobra'));
        $this->assertEquals($config->userglossary, $cobra->userglossary);
        $this->assertEquals($config->translations, $cobra->translations);
        $this->assertEquals($config->annotations, $cobra->annotations);
        $this->assertEquals($config->examples, $cobra->examples);
        $this->assertEquals($config->defaultcorpusorderen, $cobra->corpusorder);
        $this->assertEquals($config->audioplayer, $cobra->audioplayer);

        $cm = get_coursemodule_from_instance('cobra', $cobra->id);
        $this->assertEquals($cobra->id, $cm->instance);
        $this->assertEquals('cobra', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($cobra->cmid, $context->instanceid);
    }
}
