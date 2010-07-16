<?php
print("action,idnumber,username,password,email,firstname,lastname,city, mi, country\n");
$user_cnt = 50000;
for( $i = 0; $i < $user_cnt; $i++) {
	print("disable,idnumber$i,username$i,password,email$i@sample.com,firstname$i,lastname$i,Raleigh,mi,United States\n");
}// for
?>
