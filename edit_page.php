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
  $error = 0;

  for ($i = 1; $i <= 5; $i++) {
    $path = get_post_meta($post->ID, "path_$i", true);
    $ratio = get_post_meta($post->ID, "ratio_$i", true);
    $probability = $denominator > 0 ? round(intval($ratio) / $denominator * 100) : 0;

    $fields .= <<<EOM
    <div style="margin:20px 0;">
      <label for="path_$i">Path $i: </label>
      <input type="text" id="path_$i" name="path_$i" value="$path">/
      <input type="number" id="probability_$i" name="ratio_$i" value="$ratio" step="1" min="0" max="10">($probability%)
    </div>
    EOM;

    if (empty($path) xor empty($ratio)) {
      $error++;
    }
  }
  if ($error) {
    echo '<span style="color:red;">※未入力の項目が' . $error . '箇所あります。</span><br>';
  }
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
