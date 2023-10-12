<?php

$NUM_OF_FIELDS = 6;

function get_fields_meta_list($post_id)
{
  global $NUM_OF_FIELDS;
  $fields_list = array_pad([], $NUM_OF_FIELDS, ['path' => '', 'ratio' => '']);
  if (get_post_meta($post_id, "fields_list", true)) {
    $fields_list = get_post_meta($post_id, "fields_list", true);
  }
  return $fields_list;
}

function check_error_count($fields_list)
{
  $path_error = 0;
  $not_entered_error = 0;
  for ($i = 0; $i < count($fields_list); $i++) {
    $path = $fields_list[$i]['path'];
    $ratio = $fields_list[$i]['ratio'];

    if (get_page_by_path($path, OBJECT, "post") === null) {
      $path_error++;
    }
    if (empty($path) xor empty($ratio)) {
      $not_entered_error++;
    }
  }

  return [$path_error, $not_entered_error];
}

function create_error_message($not_entered_error, $path_error)
{
  $error_message = '';
  if ($not_entered_error > 0) {
    $error_message .= '<span style="color:red;">※未入力の項目が' . $not_entered_error . '箇所あります。</span><br>';
  }
  if ($path_error > 0) {
    $error_message .= '<span style="color:red;">※パスの入力ミスが' . $path_error . '箇所あります。</span><br>';
  }
  return $error_message;
}

function calc_total_ratio($fields_list)
{
  $total = 0;
  for ($i = 0; $i < count($fields_list); $i++) {
    $ratio = $fields_list[$i]['ratio'];
    $total += intval($ratio);
  }
  return $total;
}

function create_fields($fields_list)
{
  $total = calc_total_ratio($fields_list);
  $fields = '';

  for ($i = 0; $i < count($fields_list); $i++) {
    $path = $fields_list[$i]['path'];
    $ratio = $fields_list[$i]['ratio'];
    $probability = $total > 0 ? round(intval($ratio) / $total * 100) : 0;

    $fields .= <<<EOM
      <div style="margin:20px 0;">
        <label for="path_$i">Path $i: </label>
        <input type="text" id="path_$i" name="path_$i" value="$path">/
        <input type="number" id="ratio_$i" name="ratio_$i" value="$ratio" step="1" min="0" max="10">($probability%)
      </div>
      EOM;
  }
  return $fields;
}

function create_confirm_toggle($post_id)
{
  $run_test = get_post_meta($post_id, "run_test", true);
  $run_text = $run_test ? '実施中' : '未実施';
  $checked = $run_test ? 'checked' : '';
  return <<<EOM
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
}

// メタボックスのコールバック関数
function custom_fields_callback($post)
{
  wp_nonce_field('save_ab_test_fields_data', 'custom_fields_meta_box_nonce');

  $fields_list = get_fields_meta_list($post->ID);
  update_post_meta($post->ID, "fields_list", $fields_list);
  
  [$path_error, $not_entered_error] = check_error_count($fields_list);
  $error_message = create_error_message($path_error, $not_entered_error);
  $total_error_count = $not_entered_error + $path_error;
  update_post_meta($post->ID, "error", $total_error_count);
  
  $fields = create_fields($fields_list);
  $fields .= create_confirm_toggle($post->ID);
  $fields .= '<input type="hidden" name="error" value="' . $total_error_count . '">';

  echo $error_message;
  echo $fields;
}

function add_custom_fields_meta_box()
{
  add_meta_box('custom_fields_meta_box', 'Custom Fields', 'custom_fields_callback', 'post');
}

add_action('add_meta_boxes', 'add_custom_fields_meta_box');

// カスタムフィールドのデータ保存
function save_ab_test_fields_data($post_id)
{
  if (!isset($_POST['custom_fields_meta_box_nonce'])) return;
  if (!wp_verify_nonce($_POST['custom_fields_meta_box_nonce'], 'save_ab_test_fields_data')) return;

  $fields_list = get_fields_meta_list($post_id);
  for ($i = 0; $i < count($fields_list); $i++) {
    if (array_key_exists("path_$i", $_POST)) {
      $path = sanitize_text_field($_POST["path_$i"]);
    }
    if (array_key_exists("ratio_$i", $_POST)) {
      $ratio = sanitize_text_field($_POST["ratio_$i"]);
    }
    $fields_list[$i] = [
      'path' => $path,
      'ratio' => $ratio
    ];
  }
  update_post_meta($post_id, 'fields_list', $fields_list);
  update_post_meta($post_id, 'run_test', isset($_POST['run_test']) ? 1 : 0);
  update_post_meta($post_id, 'error', intval($_POST['error']));
}

add_action('save_post', 'save_ab_test_fields_data');
