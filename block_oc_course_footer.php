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
 * @package   block_oc_course_footer
 * @copyright 2015 oncampus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_oc_course_footer extends block_base {

    public function init() {
		global $PAGE;
        $this->title = get_string('pluginname', 'block_oc_course_footer');
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_config() {
        return true;
    }

    public function applicable_formats() {
        return array(
                'all' => true
        );
    }

    public function specialization() {
		$this->title = '';
    }

    public function get_content() {
        global $USER, $PAGE, $COURSE, $CFG, $SESSION;
		
        if ($this->content !== null) {
            return $this->content;
        }
		$content = '';
		
		// nur anzeigen, wenn wir in der Kursansicht sind
		$exploded = explode('?', $PAGE->url->out(false));
		if (strpos($exploded[0], '/course/view.php') !== false) {
			//echo '*';die();
			$courseid = optional_param('id', 0, PARAM_INT);
			$chapter = optional_param('chapter', 0, PARAM_INT);
			$selected_week = optional_param('selected_week', 1, PARAM_INT);
			
			$lection = $selected_week;
			if ($lection >= 1000) {
				$lection = $lection / 1000;
			}
			$chpt = $this->get_chapter_for_lection($lection, $courseid);
			
			$oc_prev_week = $selected_week - 1;
			if ($oc_prev_week >= 999) {
				$oc_prev_week = $SESSION->G8_selected_week[$courseid] - 1;
			}
			$oc_next_week = $selected_week + 1;
			if ($oc_next_week >= 1000) {
				$oc_next_week = $SESSION->G8_selected_week[$courseid] + 1;
			}
						
			if ($oc_next_week >= ($chpt['first_lection'] + $chpt['lections'])) {
				$next_div = html_writer::tag('div', '<p>'.get_string('next', 'block_oc_course_footer').'</p>', array('id' => 'oc-div-nextlection-inactive'));
			}
			else {
				$next_url = new moodle_url('/course/view.php', array('id' => $courseid, 'chapter' => $chapter, 'selected_week' => $oc_next_week));
				$next_link = html_writer::link($next_url, '<p>'.get_string('next', 'block_oc_course_footer').'</p>', array('id' => 'oc-btn-nextlection'));
				$next_div = html_writer::tag('div', $next_link, array('id' => 'oc-div-nextlection'));
			}
			
			if ($oc_prev_week < $chpt['first_lection']) {
				$previous_div = html_writer::tag('div', '<p>'.get_string('previous', 'block_oc_course_footer').'</p>', array('id' => 'oc-div-prevlection-inactive'));
			}
			else {
				$previous_url = new moodle_url('/course/view.php', array('id' => $courseid, 'chapter' => $chapter, 'selected_week' => $oc_prev_week));
				$previous_link = html_writer::link($previous_url, '<p>'.get_string('previous', 'block_oc_course_footer').'</p>', array('id' => 'oc-btn-prevlection'));
				$previous_div = html_writer::tag('div', $previous_link, array('id' => 'oc-div-prevlection'));
			}
			
			$up_url = new moodle_url('#anfang');
			$up_link = html_writer::link($up_url, '<p>'.get_string('up', 'block_oc_course_footer').'</p>', array('id' => 'oc-btn-nextlection'));
			$up_div = html_writer::tag('div', $up_link, array('id' => 'oc-div-up'));
			
			$clear_div = html_writer::tag('div', '', array('style' => 'clear:both;'));
			$course_footer_div = html_writer::tag('div', $previous_div.$up_div.$next_div.$clear_div, array('id' => 'oc-div-footerlinks'));
			
			$content .= html_writer::tag('div', $course_footer_div, array('id' => 'oc-div-footerlinks-outer'));
		}
		
        $this->content = new stdClass();
        $this->content->text = $content;

        return $this->content;
    }
	
	function get_chapters($courseid) {
		global $CFG, $DB;
		require_once($CFG->dirroot.'/lib/blocklib.php');

		$coursecontext = context_course::instance($courseid);
		$blockrecord = $DB->get_record('block_instances', array('blockname' => 'oc_mooc_nav', 'parentcontextid' => $coursecontext->id), '*', MUST_EXIST);
		$blockinstance = block_instance('oc_mooc_nav', $blockrecord);
		$chapter_configtext = $blockinstance->config->chapter_configtext;
		
		$lines = preg_split( "/[\r\n]+/", trim($chapter_configtext));
		$chapters = array();
		$number = 0;
		$first = 1;
		foreach ($lines as $line) {
			$elements = explode(';', $line);
			$chapter = array();
			$chapter['number'] = $number;
			$number++;
			$chapter['first_lection'] = $first;
			foreach ($elements as $element) {
				$ex = explode('=', $element);
				$chapter[$ex[0]] = $ex[1];
			}
			$first += $chapter['lections'];
			$chapters[] = $chapter;
		}
		return $chapters;
	}
	
	function get_chapter_for_lection($lection, $courseid) {
		global $CFG;
		$sections = 0;
		$chapters = $this->get_chapters($courseid);
		foreach ($chapters as $chapter) {
			$sections = $sections + $chapter['lections'];
			if ($sections >= $lection) {
				return $chapter;
			}
		}
		return false;
	}
}
