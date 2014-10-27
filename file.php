<?php

require 'inc.php';

require_login(EXALIB_COURSE_ID);

block_exalib_send_stored_file(required_param('itemid', PARAM_INT));
