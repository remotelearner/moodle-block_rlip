<?php
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
 * Generate a sample course creation CSV file.
 *
 * NOTE: can only be run from the commandline!
 *
 * @package   blocks-rlip
 * @copyright 2010 Remote Learner - http://www.remote-learner.net/
 * @author    Jonathan Moore <jonathan@remote-learner.net>
 * @author    Justin Filip <jfilip@remote-learner.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define('EXPORT_FILENAME', 'create_courses.csv');
define('NUM_COURSES',     100);


if (PHP_SAPI != 'cli') {
    die('No web access' . "\n");
}

if (!$fh = fopen(EXPORT_FILENAME, 'w+')) {
    die('Could not open ' . EXPORT_FILENAME . ' for writing' . "\n");
}

fwrite($fh, "action, category, format, fullname, guest, idnumber, lang, maxbytes, metacourse, newsitems, " .
       "notifystudents, numsections,password,shortname, showgrades, showreports, sortorder, startdate, summary, " .
       "timecreated, visible, link\n");

for ($i = 1; $i <= NUM_COURSES; $i++) {
	$sort = NUM_COURSES - $i + 1;
	fwrite($fh, "create, Miscellaneous, topics, Sample Course $i, yes, idnumber$i , en_utf8, 32767, no, 5, yes, 5, " .
	       "password, course$i, 1, yes, $sort, 03/01/2010, This is sample course $i, 06/23/2010, yes,\n");
}

fclose($fh);

?>