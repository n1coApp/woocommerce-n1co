<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

function check_for_woon1co() {
    do_action('woogpp_check_n1co_gateway');
}

add_action('init', 'check_for_woon1co');

