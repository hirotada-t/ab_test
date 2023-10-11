<?php

function should_redirect($post_id)
{
  return is_single() && get_post_meta($post_id, "run_test", true) && get_post_meta($post_id, "error", true) <= 0;
}

function fields_list_filter($post_id)
{
  $fields_list = get_post_meta($post_id, "fields_list", true);
  $fields_list = array_filter($fields_list, function ($item) {
    return !empty($item['path']) || !empty($item['ratio']);
  });
  $fields_list = array_values($fields_list);
  return $fields_list;
}

function ab_test_redirect()
{
  $post_id = get_the_ID();
  // 投稿ページではないorテスト実施しないorエラーがある場合は終了
  if (!should_redirect($post_id)) {
    return;
  }
  
  $fields_list = fields_list_filter($post_id);
  $root_path = get_page_uri($post_id);
  $current_ratio = 0;
  $total_ratio = array_sum(array_column($fields_list, 'ratio'));
  $rand_num = mt_rand(1, $total_ratio);
  $current_query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';

  foreach ($fields_list as $fields) {
    $current_ratio += intval($fields['ratio']);
    if ($rand_num <= $current_ratio) {
      if ($fields['path'] === $root_path) {
        return;
      } else {
        wp_redirect(home_url($fields['path'] . $current_query_string));
        exit;
      }
    }
  }
}

add_action('template_redirect', 'ab_test_redirect');
