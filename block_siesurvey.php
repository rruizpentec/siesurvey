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
 * Block siesurvey implementation.
 *
 * @package    block_siesurvey
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block siesurvey class definition.
 *
 * This block can be added to a course page to display a
 * link for evaluate the satisfaction with the course. This block allow too if
 * you are a teacher, or have capabilities, to see a list of students what completed the
 * survey.
 *
 * @package    SIE
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_siesurvey extends block_base {

    /**
     * Set the initial properties for the block
     */
    public function init() {
        $this->title = get_string('title', 'block_siesurvey');
    }

    /**
     * Gets the content for this block
     *
     * @return object $this->content
     */
    public function get_content() {
        global $COURSE, $USER, $CFG, $PAGE;
        // Initialize the content.
        if (isset($this->content)) {
            if ($this->content !== null) {
                return $this->content;
            }
        } else {
            $this->content = new stdClass();
            $this->content->text = '';
        }
        // The user must be in a course context to be able to see the block content.
        if ($PAGE->pagelayout != "course") {
            $courseid = optional_param('courseid', null, PARAM_INT);
            if ($courseid != null) {
                $this->content->text = html_writer::tag('a', get_string('gobacktocourse', 'block_siesurvey'),
                        array(
                            'href' => $CFG->wwwroot."/course/view.php?id=$courseid",
                            'class' => 'btn btn-default block_siesurvey_button'));
            } else {
                $this->content->text = get_string('accesstocoursemessage', 'block_siesurvey');
            }
            return null;
        }
        if (isloggedin()) {
            $coursecontext = context_course::instance($COURSE->id);
            $canfillin = has_capability('block/siesurvey:fillinsurvey', $coursecontext, $USER, true) && !is_siteadmin();
            $canviewlists = has_capability('block/siesurvey:viewlistofsurveysfilled', $coursecontext, $USER, true);
            if (!$canfillin && !$canviewlists) {
                return null;
            }
            $sieconfig = get_config('package_sie');
            $baseurl = $sieconfig->baseurl;
            $professionalcertificatecategory = $sieconfig->professionalcertificatecategory;
            if ($professionalcertificatecategory == '') {
                $professionalcertificatecategory = 1;
            }

            if (isset($baseurl) && $baseurl != '' && $baseurl != null) {
                $afgidlms = '';
                if ($COURSE->category != $professionalcertificatecategory ) {
                    // Es Propia.
                    $afgidlms = $COURSE->id;
                } else {
                    // Es un certificado CNCP.
                    $afgidlms = $COURSE->category;
                }
                $params = 'afg_id_lms='.$afgidlms.'&courseid='.$COURSE->id;
                if ($canfillin) {
                    $this->content->text .= html_writer::tag('a', get_string('fillsurvey', 'block_siesurvey'),
                            array('href' => $CFG->wwwroot.'/blocks/siesurvey/fillsurvey.php?'.$params));
                }
                if ($canviewlists) {
                    $this->content->text .= html_writer::start_tag('p');
                    $this->content->text .= html_writer::tag('a', get_string('listofuserswithfilledsurvey', 'block_siesurvey'),
                            array('href' => $CFG->wwwroot.'/blocks/siesurvey/userslist.php?filled=1&'.$params));
                    $this->content->text .= html_writer::end_tag('p');
                    $this->content->text .= html_writer::start_tag('p');
                    $this->content->text .= html_writer::tag('a', get_string('listofuserswithoutfilledsurvey', 'block_siesurvey'),
                            array('href' => $CFG->wwwroot.'/blocks/siesurvey/userslist.php?filled=0&'.$params));
                    $this->content->text .= html_writer::end_tag('p');
                }
            } else {
                $this->content = null;
            }
        } else {
            $this->content = null;
        }
        return $this->content;
    }

    /**
     * Set the applicable formats for this block
     * @return array
     */
    public function applicable_formats() {
        return array(
            'all' => true,
            'site' => true,
            'site-index' => true,
            'course-view' => true,
            'course-view-social' => false,
            'mod' => true,
            'mod-quiz' => false);
    }

    /**
     * Allows the block to be added multiple times to a single page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
          return false;
    }

    /**
     * This line tells Moodle that the block has a settings.php file.
     *
     * @return bool
     */
    public function has_config() {
        return false;
    }
}
