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
 * Page that shows the users that have filled the surveys or not.
 *
 * @package    SIE
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/blocklib.php');

$afgidlms = required_param('afg_id_lms', PARAM_INT);
$filled = required_param('filled', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

global $CFG, $PAGE, $OUTPUT, $DB;
$systemcontext = context_system::instance();
require_login();
$PAGE->set_url('/blocks/siesurvey/userslist.php?afg_id_lms='.$afgidlms.'&filled=0'.'&courseid='.$courseid);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('title', 'block_siesurvey'));

$coursename = $DB->get_record('course', array('id' => $afgidlms));
$PAGE->navbar->add($coursename->shortname, new moodle_url('/course/view.php', array('id' => $afgidlms)));
$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/blocks/siesurvey/styles.css'));
echo $OUTPUT->header();

$content  = html_writer::start_tag('div', array('id' => 'siesurveycontent', 'class' => 'block'));
$content .= html_writer::start_tag('div', array('class' => 'header'));
$content .= html_writer::start_tag('div', array('class' => 'title'));
$content .= html_writer::tag('h2', get_string('title', 'block_siesurvey'));
$content .= html_writer::end_tag('div');
$content .= html_writer::end_tag('div');

$coursecontext = context_course::instance($courseid);
if (has_capability('block/siesurvey:viewlistofsurveysfilled', $coursecontext, $USER, true)) {
    $sieconfig = get_config('package_sie');
    $baseurl = $sieconfig->baseurl;
    if (substr($baseurl, -1, 1) != '/') {
        $baseurl .= '/';
    }

    $result = file_get_contents($baseurl.'inc/surveyrequests.php?afg_id_lms='.$afgidlms);

    $queryparams = array($courseid);

    $content .= html_writer::start_tag('div');
    if ($filled == 0) {
        $content .= html_writer::start_tag('h3').get_string('listofuserswithoutfilledsurvey', 'block_siesurvey');
        $content .= html_writer::tag('a', get_string('listofuserswithfilledsurvey', 'block_siesurvey'),
                array(
                    'href' => $CFG->wwwroot."/blocks/siesurvey/userslist.php?afg_id_lms=$afgidlms&filled=1&courseid=$courseid",
                    'class' => 'btn btn-default',
                    'style' => 'float: right')
        );
        list($insql, $inparams) = $DB->get_in_or_equal(explode(',', $result), SQL_PARAMS_QM, 'param', false);
        $content .= html_writer::end_tag('h3');
    } else {
        $content .= html_writer::start_tag('h3').get_string('listofuserswithfilledsurvey', 'block_siesurvey');
        $content .= html_writer::tag('a', get_string('listofuserswithoutfilledsurvey', 'block_siesurvey'),
                array(
                    'href' => $CFG->wwwroot."/blocks/siesurvey/userslist.php?afg_id_lms=$afgidlms&filled=0&courseid=$courseid",
                    'class' => 'btn btn-default',
                    'style' => 'float: right')
        );
        list($insql, $inparams) = $DB->get_in_or_equal(explode(',', $result));
        $content .= html_writer::end_tag('h3');
    }
    $content .= html_writer::end_tag('div');

    $sql = " SELECT DISTINCT u.id, u.firstname, u.lastname
               FROM {user} u, {role_assignments} r, {context} cx, {course} c
              WHERE u.id = r.userid
                    AND r.contextid = cx.id
                    AND cx.instanceid = c.id
                    AND r.roleid = 5
                    AND cx.contextlevel = 50
                    AND c.id = ?
                    AND u.id ".$insql;

    $params = array_merge($queryparams, $inparams);
    $studentsrecords = $DB->get_recordset_sql($sql, $params);
    $content .= html_writer::start_tag('table', array('class' => 'table'));
    $content .= html_writer::start_tag('tr');
    $content .= html_writer::tag('th', get_string('firstname', 'block_siesurvey'));
    $content .= html_writer::tag('th', get_string('lastname', 'block_siesurvey'));
    $content .= html_writer::end_tag('tr');
    if (count($studentsrecords) > 0) {
        foreach ($studentsrecords as $studentrecord) {
            $content .= html_writer::start_tag('tr');
            $content .= html_writer::tag('td', $studentrecord->firstname);
            $content .= html_writer::tag('td', $studentrecord->lastname);
            $content .= html_writer::end_tag('tr');
        }
    } else {
        $content .= html_writer::tag('td', get_string('nodatafound', 'block_siesurvey'),
                array('colspan' => '100%', 'class' => 'block_siesurvey_nodatafound'));
    }
    $content .= html_writer::end_tag('table');
} else {
    $content .= html_writer::tag('h3', get_string('permission_denied', 'block_siesurvey'));
}
$content .= html_writer::end_tag('div');
echo $content;
echo $OUTPUT->footer();