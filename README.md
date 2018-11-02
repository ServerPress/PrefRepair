# PrefRepair
Tool to recreate or restore entries in the DesktopServer preferences file

Place file in DesktopServer folder ( Mac - /applications/XAMPP - Windows - c:\xampplite )
Open Terminal and go to the DesktopServer folder/directory
Type php c:\xampplite\prefrepair.php -h

switches:
-b ( By default the script will set it to Safari, but you can change this. )
-f ( Will force update of site information )
-h ( Presents help and switches )
-t ( By defult sets the TLD to .dev.cc but can be changed )
-v ( Outputs verbose )

Example: c:\xampplite\prefrepair.php -b:Chrome -f -t:local -v
