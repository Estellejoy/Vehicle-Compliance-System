<?php
// Keep the old login path working by sending it to the main login page.
header('Location: /login');
exit;
