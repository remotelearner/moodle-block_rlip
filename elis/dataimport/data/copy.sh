if [ $# -gt 0 ]
then
    for file_name in $@
    do
        if [ -f $file_name ]
        then
            if [ `echo $file_name | grep enrol` ]
            then
                cp $file_name enroll.csv
                continue
            fi
            
            if [ `echo $file_name | grep course` ]
            then
                cp $file_name course.csv
                continue
            fi
            
            if [ `echo $file_name | grep user` ]
            then
                cp $file_name user.csv
                continue
            fi
            
            echo $file_name
        fi
    done
fi
