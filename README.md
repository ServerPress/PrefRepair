# PrefRepair
Tool to recreate or restore entries in the DesktopServer preferences file

Place file in DesktopServer folder 

Mac - Applications > XAMPP 
Open Terminal and go to Applications > XAMPP
Type: prefrepair.php

Windows - c:\xampplite
Open Terminal and go to C:\xampplite\ds-plugins\ds-cli\platform\win32
Type: boot
Type: c:\xampplite\prefrepair.php

switches:
-b ( By default the script will set it to Safari, but you can change this. )
-f ( Will force update of site information )
-h ( Presents help and switches )
-t ( By defult sets the TLD to .dev.cc but can be changed )
-v ( Outputs verbose )

Example: c:\xampplite\prefrepair.php -b:Chrome -f -t:local -v
