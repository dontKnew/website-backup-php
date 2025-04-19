<?php
echo shell_exec("php db_backup.php");
echo "\n";
echo "\n";
echo shell_exec("php file_backup.php");