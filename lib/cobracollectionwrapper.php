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
 * Library of interface functions and constants for module cobra
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the cobra specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_cobra
 * @copyright  2016 - Cellule TICE - Unversite de Namur
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class cobra_collection_wrapper {
    private $id = 0;
    private $language = '';
    private $remoteid = 0;
    private $remotename = '';
    private $localname = '';
    private $position = 1;
    private $visibility = true;

    public function __construct($id = 0) {
        $this->set_id($id);
    }

    public function set_id($id) {
        $this->id = $id;
    }

    public function get_id() {
        return (int)$this->id;
    }

    public function set_language($language) {
        $this->language = $language;
    }

    public function get_language() {
        return $this->language;
    }

    public function set_remote_id($remoteid) {
        $this->remoteid = (int)$remoteid;
    }

    public function get_remote_id() {
        return $this->remoteid;
    }

    public function set_remote_name($name) {
        $this->remotename = $name;
    }

    public function get_remote_name() {
        return $this->remotename;
    }

    public function set_local_name($name) {
        $this->localname = $name;
    }

    public function get_local_name() {
        return $this->localname;
    }

    public function set_position($index) {
        $this->position = (int)$index;
    }

    public function get_position() {
        return $this->position;
    }

    public function set_visibility($value) {
        $this->visibility = $value;
    }

    public function is_visible() {
        return true === $this->visibility ? true : false;
    }

    public function load() {
        global $DB, $course;
        if (!$this->get_id()) {
            return false;
        }

        $list = $DB->get_records_select('cobra_registered_collections',
                "course='$course->id' AND id_collection='".(int)$this->get_id()."'");
        $data = $DB->get_record('cobra_registered_collections', array('id' => $this->get_id()), '*', MUST_EXIST);
        /*if (empty($list)) {
            return false;
        }*/
        $this->set_language($data->language);
        $this->set_remote_id($data->id_collection);
        $this->set_remote_name($data->label);
        $this->set_local_name($data->local_label);
        $this->set_position($data->position);
        $this->set_visibility($data->visibility ? true : false);
        /*foreach ($list as $collection) {
            $this->set_language($collection->language);
            $this->set_remote_name($collection->label);
            $this->set_local_name($collection->local_label);
            $this->set_position($collection->position);
            $this->set_visibility($collection->visibility ? true : false);
            return true;
        }*/
        return true;
    }

    public function save() {
        global $DB, $course;
        $exists = $DB->record_exists('cobra_registered_collections',
                array('course' => $course->id, 'id_collection' =>  $this->get_remote_id()));
        if ($exists) {
            return $this->update();
        }

        $visibility = (true === $this->is_visible() ? '1' : '0');
        $dataobject = new stdClass();
        $dataobject->course = $course->id;
        $dataobject->id_collection = $this->get_remote_id();
        $dataobject->label = $this->get_remote_name();
        $dataobject->local_label = $this->get_local_name();
        $dataobject->position = $this->get_position();
        $dataobject->visibility = $visibility;

        $id = $DB->insert_record('cobra_registered_collections', $dataobject);
        if ($id) {
            $this->set_id($id);
            return 'saved';
        } else {
            return 'error';
        }
    }

    public function update() {
        global $DB, $course;
        if ($this->get_id()) {
            $visibility = (true === $this->is_visible() ? '1' : '0');
            $dataobject = new stdClass();
            $dataobject->id = $this->get_id();
            $dataobject->course = $course->id;
            $dataobject->id_collection = $this->get_remote_id();
            $dataobject->label = $this->get_remote_name();
            $dataobject->local_label = $this->get_local_name();
            $dataobject->position = $this->get_position();
            $dataobject->visibility = $visibility;

            if ($DB->update_record('cobra_registered_collections', $dataobject)) {
                return 'updated';
            } else {
                return 'error';
            }
        } else {
            return false;
        }
    }

    public function wrapremote($remoteid) {
        $params = array('id_collection' => (int)$remoteid);
        $remotecollection = cobra_remote_service::call('getCollection', $params);

        $this->set_remote_id($remoteid);
        $this->set_language($remotecollection->language);
        $this->set_remote_name($remotecollection->label);
        $this->set_local_name($remotecollection->label);
        $this->set_position(self::getmaxposition() + 1);
        return true;
    }

    public function remove() {
        global $DB, $course;
        return $DB->delete_records('cobra_registered_collections',
                array('id' => $this->get_id()));
    }

    public static function getmaxposition() {
        global $DB, $course;
        $list = $DB->get_records_select('cobra_registered_collections', "course='$course->id'", null, 'position DESC', 'POSITION');
        if (!empty($list)) {
            foreach ($list as $elt) {
                $value = $elt->position;
                return $value;
            }
        } else {
            return '0';
        }
    }
}