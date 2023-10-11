<?php

function ab_test_redirect()
{
  $post_id = get_the_ID();
  $run_test = get_post_meta($post_id, "run_test", true);
  
  if (is_single() && $run_test) {
    $fields_list = get_post_meta($post_id, "fields_list", true);
    $fields_list = array_filter($fields_list, function ($item) {
      return !empty($item['path']) || !empty($item['ratio']);
    });
    $fields_list = array_values($fields_list);
    $root_path = get_page_uri($post_id);
    $error = get_post_meta($post_id, "error", true);
    if ($error > 0) {
      return;
    }
    
    $total_ratio = 0;
    for ($i = 0; $i < count($fields_list); $i++) {
      $ratio = $fields_list[$i]['ratio'];
      if (!empty($ratio)) {
        $total_ratio += $ratio;
      }
    }
    
    $rand_num = mt_rand(1, $total_ratio);
    $current_ratio = 0;
    $current_query_string = $_SERVER['QUERY_STRING'];
    if (!empty($current_query_string)) {
      $current_query_string = '?' . $current_query_string;
    }
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
}
add_action('template_redirect', 'ab_test_redirect');
