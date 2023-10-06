<?php
/*
Plugin Name: AB Test Field
Description: This plugin adds custom field at edit page to set AB test.
Version: 1.0.0
Author: sizebook
Author URI: https://sizebook.co.jp/
*/

if (!defined('ABSPATH')) exit;

function add_custom_fields_meta_box()
{
  add_meta_box('custom_fields_meta_box', 'Custom Fields', 'custom_fields_callback', 'post');
}
add_action('add_meta_boxes', 'add_custom_fields_meta_box');

// メタボックスのコールバック関数
function custom_fields_callback($post)
{
  wp_nonce_field('save_custom_fields_data', 'custom_fields_meta_box_nonce');

  for ($i = 1; $i <= 5; $i++) {
    $path_or_url = get_post_meta($post->ID, "path_or_url_$i", true);
    $probability = get_post_meta($post->ID, "probability_$i", true);

    $field = <<<EOM
    <label for="path_or_url_$i">Path or URL $i: </label>
    <input type="text" id="path_or_url_$i" name="path_or_url_$i" value="$path_or_url" required>
    <label for="probability_$i" style="margin-left:20px;">Probability $i: </label>
    <input type="number" id="probability_$i" name="probability_$i" value="$probability" step="1" min="0" max="10"><br><br>
    EOM;

    echo $field;
  }
}

// カスタムフィールドのデータ保存
function save_custom_fields_data($post_id)
{
  if (!isset($_POST['custom_fields_meta_box_nonce'])) return;
  if (!wp_verify_nonce($_POST['custom_fields_meta_box_nonce'], 'save_custom_fields_data')) return;

  for ($i = 1; $i <= 5; $i++) {
    if (array_key_exists("path_or_url_$i", $_POST)) {
      update_post_meta($post_id, "path_or_url_$i", sanitize_text_field($_POST["path_or_url_$i"]));
    }
    if (array_key_exists("probability_$i", $_POST)) {
      update_post_meta($post_id, "probability_$i", sanitize_text_field($_POST["probability_$i"]));
    }
  }
}
add_action('save_post', 'save_custom_fields_data');
