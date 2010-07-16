<?php
print("action,context,instance,username,role,useridnumber,timestart,timeend\n");
// example 
// add,course,GAT,studentrole01,student,studentrole01,08/01/10,
$user_cnt = 100;
//Create enrollments for user username0
for( $i = 0; $i < 10; $i++) {
	print("delete,course,course$i,username0,student,useridnumber$i,06/22/2010,06/22/2010\n");
}// for
for( $i = 0; $i < $user_cnt; $i++) {
	if( $i % 2 ) {
		print("delete,course,course1,username$i,student,useridnumber$i,06/22/2010,06/22/2010\n");
		print("delete,course,course2,username$i,student,useridnumber$i,06/22/2010,06/22/2010\n");

	}// if
	else {
		print("delete,course,course2,username$i,student,useridnumber$i,06/22/2010,06/22/2010\n");
		print("delete,course,course1,username$i,student,useridnumber$i,06/22/2010,06/22/2010\n");
	}// else
}// for
?>
