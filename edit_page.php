<?php

function add_custom_fields_meta_box()
{
  add_meta_box('custom_fields_meta_box', 'Custom Fields', 'custom_fields_callback', 'post');
}
add_action('add_meta_boxes', 'add_custom_fields_meta_box');

// メタボックスのコールバック関数
function custom_fields_callback($post)
{
  wp_nonce_field('save_ab_test_fields_data', 'custom_fields_meta_box_nonce');

  $denominator = calc_total_value($post->ID);
  $fields = '';
  $not_entered_error = 0;
  $path_error = 0;
  $run_test = get_post_meta($post->ID, "run_test", true);
  $run_text = $run_test ? '実施中' : '未実施';
  $checked = $run_test ? 'checked' : '';

  for ($i = 1; $i <= 5; $i++) {
    $path = get_post_meta($post->ID, "path_$i", true);
    $ratio = get_post_meta($post->ID, "ratio_$i", true);
    $probability = $denominator > 0 ? round(intval($ratio) / $denominator * 100) : 0;
    $page_exists = get_page_by_path($path, OBJECT, "post") !== null;
    if (!$page_exists) {
      $path_error++;
    }

    $fields .= <<<EOM
    <div style="margin:20px 0;">
      <label for="path_$i">Path $i: </label>
      <input type="text" id="path_$i" name="path_$i" value="$path">/
      <input type="number" id="ratio_$i" name="ratio_$i" value="$ratio" step="1" min="0" max="10">($probability%)
    </div>
    EOM;

    if (empty($path) xor empty($ratio)) {
      $not_entered_error++;
    }
  }

  if ($not_entered_error) {
    echo '<span style="color:red;">※未入力の項目が' . $not_entered_error . '箇所あります。</span><br>';
  }
  if ($path_error) {
    echo '<span style="color:red;">※パスの入力ミスが' . $path_error . '箇所あります。</span><br>';
  }
  $fields .= <<<EOM
    <div style="margin:20px 0;">
      テストを実施しますか？ - $run_text
      <input id="run_test" name="run_test" class="toggle_input" type="checkbox" $checked />
      <label for="run_test" class="toggle_label"/>
    </div>
    <style>
    .toggle_input {
      opacity: 0;
    }
    .toggle_label {
      width: 65px;
      height: 25px;
      background: #ccc;
      position: relative;
      display: inline-block;
      border-radius: 40px;
      transition: 0.4s;
      box-sizing: border-box;
    }
    .toggle_label:after {
      content: "";
      position: absolute;
      width: 25px;
      height: 25px;
      border-radius: 100%;
      left: 0;
      top: 0;
      z-index: 2;
      background: #fff;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
      transition: 0.4s;
    }
    .toggle_input:checked + .toggle_label {
      background-color: #4BD865;
    }
    .toggle_input:checked + .toggle_label:after {
      left: 40px;
    }
    </style>
    EOM;
  $error = $not_entered_error + $path_error;
  update_post_meta($post->ID, "error", $error);
  $fields .= '<input type="hidden" name="error" value="' . $error . '">';
  echo $fields;
}

// カスタムフィールドのデータ保存
function save_ab_test_fields_data($post_id)
{
  if (!isset($_POST['custom_fields_meta_box_nonce'])) return;
  if (!wp_verify_nonce($_POST['custom_fields_meta_box_nonce'], 'save_ab_test_fields_data')) return;

  for ($i = 1; $i <= 5; $i++) {
    if (array_key_exists("path_$i", $_POST)) {
      update_post_meta($post_id, "path_$i", sanitize_text_field($_POST["path_$i"]));
    }
    if (array_key_exists("ratio_$i", $_POST)) {
      update_post_meta($post_id, "ratio_$i", sanitize_text_field($_POST["ratio_$i"]));
    }
  }
  update_post_meta($post_id, 'run_test', isset($_POST['run_test']) ? 1 : 0);
  update_post_meta($post_id, 'error', intval($_POST['error']));
}
add_action('save_post', 'save_ab_test_fields_data');

function calc_total_value($post_id)
{
  $total_value = 0;
  for ($i = 1; $i <= 5; $i++) {
    $ratio_value = get_post_meta($post_id, "ratio_$i", true);
    $total_value += intval($ratio_value);
  }
  return $total_value;
}
