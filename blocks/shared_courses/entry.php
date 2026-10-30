<?php
require('../../config.php');

$id = required_param('id', PARAM_INT);

echo "You returned successfully from SAML for record ID: " . $id;
exit;