<?php
require_once(dirname(__FILE__) . '/../../config.php');
$itemid  = optional_param('itemid', 0, PARAM_INT);
$return  = optional_param('return', 'pdhstar', PARAM_RAW);

redirect("https://pdhstar.com/access/?itemid={$itemid}&return={$return}");