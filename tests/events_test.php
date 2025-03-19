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
 * Unit tests for cobra events.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines PHPUnit cobra_events testcase.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group mod_cobra
 */

namespace mod_cobra;

use context_module;

/**
 * Defines PHPUnit events testcase.
 *
 * @package    mod_cobra
 * @author     Jean-Roch Meurisse
 * @copyright  2016 onwards - Cellule TICE - Universite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group mod_cobra
 * @covers \mod_cobra\event\course_module_viewed
 */
final class events_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test course_module_viewed event.
     */
    public function test_course_module_viewed(): void {
        global $DB;
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.

        $course = $this->getDataGenerator()->create_course();
        $cobra = $this->getDataGenerator()->create_module('cobra', ['course' => $course->id]);

        $dbcourse = $DB->get_record('course', ['id' => $course->id]);
        $dbcobra = $DB->get_record('cobra', ['id' => $cobra->id]);
        $context = context_module::instance($cobra->cmid);

        $event = \mod_cobra\event\course_module_viewed::create([
            'objectid' => $dbcobra->id,
            'context' => $context,
        ]);

        $event->add_record_snapshot('course', $dbcourse);
        $event->add_record_snapshot('cobra', $dbcobra);

        // Triggering and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_cobra\event\course_module_viewed', $event);
        $this->assertEquals(CONTEXT_MODULE, $event->contextlevel);
        $this->assertEquals($cobra->cmid, $event->contextinstanceid);
        $this->assertEquals($cobra->id, $event->objectid);
        $expected = [$course->id, 'cobra', 'view', 'view.php?id=' . $cobra->cmid,
            $cobra->id, $cobra->cmid];
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEquals(new \core\url('/mod/cobra/view.php', ['id' => $cobra->cmid]), $event->get_url());
        $this->assertEventContextNotUsed($event);
    }
}
