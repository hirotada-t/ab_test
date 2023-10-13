<?php

/*
Plugin Name: AB Test Field
Description: This plugin adds custom field at edit page to set AB test.
Version: 1.0.0
Author: sizebook
Author URI: https://sizebook.co.jp/
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'post_list.php';
require_once plugin_dir_path(__FILE__) . 'edit_page.php';
require_once plugin_dir_path(__FILE__) . 'redirect.php';
// testの制度測定用スクリプト
// require_once plugin_dir_path(__FILE__) . 'check_test.php';
