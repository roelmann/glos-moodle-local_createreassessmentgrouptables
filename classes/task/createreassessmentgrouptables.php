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
 * A scheduled task for populating reassessment groups
 *
 * @package    local_createreassessmentgrouptables - template
 * @copyright  2016 ROelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_createreassessmentgrouptables\task;
use mod_forum\local\exporters\group;
use stdClass;
use coursecat;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/extdb/classes/task/extdb.php');
/**        // Arrays below only prevent duplicates on new - they do not check for existing content of table
        // Therefore - add a truncate, so we are actually dropping teh entire table and refilling it, so
        // the duplicate checks are then relevant.

        $trunc = 'TRUNCATE '.$cohortab;
            $extdb->Execute($trunc);


 *         // Arrays below only prevent duplicates on new - they do not check for existing content of table
        // Therefore - add a truncate, so we are actually dropping teh entire table and refilling it, so
        // the duplicate checks are then relevant.

        $trunc = 'TRUNCATE '.$cohortab;
            $extdb->Execute($trunc);

reating reassessment groups which utilises the extdb plugin.
 *
 * @copyright  2016 ROelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class createreassessmentgrouptables extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_createreassessmentgrouptables');
    }

    /**
     * Run sync.
     */
    public function execute() {

        global $CFG, $DB;
//        require_once($CFG->libdir . "/coursecatlib.php");
        $externaldb = new \local_extdb\extdb();
        $name = $externaldb->get_name();

        $externaldbtype = $externaldb->get_config('dbtype');
        $externaldbhost = $externaldb->get_config('dbhost');
        $externaldbname = $externaldb->get_config('dbname');
        $externaldbencoding = $externaldb->get_config('dbencoding');
        $externaldbsetupsql = $externaldb->get_config('dbsetupsql');
        $externaldbsybasequoting = $externaldb->get_config('dbsybasequoting');
        $externaldbdebugdb = $externaldb->get_config('dbdebugdb');
        $externaldbuser = $externaldb->get_config('dbuser');
        $externaldbpassword = $externaldb->get_config('dbpass');

        // Tables relating to the reassessment groups stored in language file and called here.
        $usrenrolgrouptab = get_string('usr_data_student_assessment', 'local_createreassessmentgrouptables');
        $grouptab = get_string('groupreassessmenttable', 'local_createreassessmentgrouptables');

        // Database connection and setup checks.
        // Check connection and label Db/Table in cron output for debugging if required.
        if (!$externaldbtype) {
            echo 'Database not defined.<br>';
            return 0;
        } else {
            echo 'Database: ' . $externaldbtype . '<br>';
        }
        // Check remote assessments table - usr_data_assessments.
        if (!$usrenrolgrouptab) {
            echo 'Levels Table not defined.<br>';
            return 0;
        } else {
            echo 'Levels Table: ' . $usrenrolgrouptab . '<br>';
        }
        // Check remote student grades table - usr_data_student_assessments.
        if (!$grouptab) {
            echo 'Categories Table not defined.<br>';
            return 0;
        } else {
            echo 'Categories Table: ' . $grouptab . '<br>';
        }

        // DB check.
        echo 'Starting connection...<br>';

        // Report connection error if occurs.
        if (!$extdb = $externaldb->db_init(
            $externaldbtype,
            $externaldbhost,
            $externaldbuser,
            $externaldbpassword,
            $externaldbname)) {
            echo 'Error while communicating with external database <br>';
            return 1;
        }
        // Blank array created for groups.
        $groups = array();

        // Sql statements to get records for reassessment groups.
        $sql = "SELECT * FROM " . $usrenrolgrouptab . " WHERE assessment_idcode LIKE '%-r%'";
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($fields = $rs->FetchRow()) {
                    $fields = array_change_key_case($fields, CASE_LOWER);
                    $fields = $externaldb->db_decode($fields);
                    $groups[] = $fields;
                }
            }
            $rs->Close();
        } else {
            // Report error if required.
            $extdb->Close();
            echo 'Error reading data from the external catlevel table, ' . $usrenrolgrouptab . '<br>';
            return 4;
        }

        // Arrays below only prevent duplicates on new - they do not check for existing content of table
        // Therefore - add a truncate, so we are actually dropping teh entire table and refilling it, so
        // the duplicate checks are then relevant.

        $trunc = 'TRUNCATE '.$grouptab;
            $extdb->Execute($trunc);

        // Array created to store groups added to stored = [];
        // Loop through the groups make sure that they have not already been entered into the group reassassment table
        // If they have not add the reassessment group to the table.
        $groupstored = array();
        foreach ($groups as $group) {
            if (!(in_array($group, $groupstored))) {
                $groupstored[] = $group;
                // Sql query for inserting reassessment group into table in the integrations database.
                $sql = "INSERT INTO " . $grouptab . " (group_name, context_level, active)
                        VALUES ('" . $group['assessment_idcode'] . "','" . "50" . "','" ."1" . "')";
                $extdb->Execute($sql);
            }
        }
    }
}
