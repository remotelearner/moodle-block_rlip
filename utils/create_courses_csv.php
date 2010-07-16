<?php
print("action, category, format, fullname, guest, idnumber, lang, maxbytes, metacourse, newsitems, notifystudents, numsections,password,shortname, showgrades, showreports, sortorder, startdate, summary,timecreated, visible, link\n");
$user_cnt = 100;
for( $i = 0; $i < $user_cnt; $i++) {
	$sort = $user_cnt - $i;
	print("create, Miscellaneous, topics, Sample Course $i, yes, idnumber$i , en_utf8, 32767, no, 5, yes, 5,password,course$i, 1, yes, $sort, 03/01/2010, This is sample course $i ,06/23/2010, yes,\n");
}// for
?>
