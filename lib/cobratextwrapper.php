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

class cobra_text_wrapper {
    // Locally stored data plugin.
    private $id = 0;
    private $textid = 0;
    private $collectionid = 0;
    private $position = 1;
    private $visibility = true;

    // Remotely stored data (CoBRA system).
    private $title = '';
    private $content = array();
    private $source = '';

    public function __construct($id = 0) {
        $this->set_id($id);
    }

    public function set_id($id) {
        $this->id = $id;
    }

    public function get_id() {
        return (int)$this->id;
    }

    public function set_text_id($id) {
        $this->textid = $id;
    }

    public function get_text_id() {
        return (int)$this->textid;
    }

    public function set_collection_id($id) {
        $this->collectionid = $id;
    }

    public function get_collection_id() {
        return (int)$this->collectionid;
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

    public function set_title($title) {
        $this->title = $title;
    }

    public function get_title() {
        return $this->title;
    }

    public function set_content($content) {
        $this->content = $content;
    }

    public function get_content() {
        return $this->content;
    }

    public function set_source($source) {
        $this->source = $source;
    }

    public function get_source() {
        return $this->source;
    }

    public function load() {
        global $DB, $course;
        if (!$this->get_text_id()) {
            return false;
        }
        $text = $DB->get_record_select('cobra_texts_config', "course='$course->id' AND id_text= ".$this->get_text_id());

        $this->set_id($text->id);
        $this->set_text_id($text->id_text);
        $this->set_collection_id($text->id_collection);
        $this->set_position($text->position);
        $this->set_visibility($text->visibility ? true : false);
        return true;
    }

    public function load_remote_data() {
        $params = array('id_text' => (int)$this->get_text_id());
        $jsonobj = cobra_remote_service::call('getTextData', $params);

        $this->set_source(utf8_decode($jsonobj->source));
        $this->set_title(utf8_decode($jsonobj->title));
        $content = array();
        foreach ($jsonobj->content as $item) {
            $content[$item->num] = array('content' => utf8_decode($item->content));
        }
        $this->set_content($content);
        return true;
    }

    public function format_html() {
        $params = array('id_text' => (int)$this->get_text_id());
        $html = cobra_remote_service::call('getFormattedText', $params);
        return utf8_encode($html);
    }

    public function get_audio_file_url() {
        $params = array('id_text' => (int)$this->get_text_id());
        $url = cobra_remote_service::call('getAudioFileUrl', $params);
        return str_replace('http://', 'https://', utf8_decode($url));
    }

    public function save() {
        global $DB, $course;
        if ($this->get_id()) {
            $visibility = (true === $this->is_visible() ? '1' : '0');
            $dataobject = new  stdClass();
            $dataobject->id = $this->getiId();
            $dataobject->course = $course->id;
            $dataobject->id_text = $this->get_text_id();
            $dataobject->id_collection = $this->get_collection_id();
            $dataobject->position = $this->get_position();
            $dataobject->visibility = $visibility;
            return  $DB->update_record('cobra_texts_config', $dataobject);
        } else {
            $visibility = (true === $this->is_visible() ? '1' : '0');
            $dataobject = new  stdClass();
            $dataobject->course = $course->id;
            $dataobject->id_text = $this->get_text_id();
            $dataobject->id_collection = $this->get_collection_id();
            $dataobject->position = $this->get_position();
            $dataobject->visibility = $visibility;
            return  $DB->insert_record('cobra_texts_config', $dataobject);
        }
    }

    public function remove() {
        global $DB, $course;
        return $DB->delete_records('cobra_text_config', array('course' => $course->id, 'id' => $this->get_id()));
    }

    public static function getmaxposition() {
        global $DB, $course;
        $list = $DB->get_records_select('cobra_texts_config', "course='$course->id'", null, 'position DESC', 'position');
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
