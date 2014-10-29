<?php

require 'inc.php';

block_exalib_require_open();
block_exalib_send_stored_file(required_param('itemid', PARAM_INT));
