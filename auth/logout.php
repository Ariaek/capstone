<?php
session_start();
session_unset();
session_destroy();
header("Location: /PTMS_CAPS/auth/login.php");
exit();