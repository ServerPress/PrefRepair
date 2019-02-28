# PrefRepair
Tool to recreate or restore entries in the DesktopServer preferences file

Place file in DesktopServer folder 

Mac - Applications > XAMPP<br>
Open Terminal and go to Applications > XAMPP<br>
Type: prefrepair.php

Windows - c:\xampplite<br>
Open Terminal and go to C:\xampplite\ds-plugins\ds-cli\platform\win32<br>
Type: boot<br>
Type: php c:\xampplite\prefrepair.php

switches:<br>
-b ( By default the script will set it to Safari, but you can change this. )<br>
-f ( Will force update of site information )<br>
-h ( Presents help and switches )<br>
-t ( By defult sets the TLD to .dev.cc but can be changed )<br>
-v ( Outputs verbose )<br>

Example: c:\xampplite\prefrepair.php -b:Chrome -f -t:local -v
