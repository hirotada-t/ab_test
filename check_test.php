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
  $current_ratio = 0;
  $total_ratio = array_sum(array_column($fields_list, 'ratio'));
  $rand_num = mt_rand(1, $total_ratio);
  $simulation = [0, 0, 0, 0];

  for ($i = 0; $i < count($fields_list); $i++) {
    $current_ratio += intval($fields_list[$i]['ratio']);
    if ($rand_num <= $current_ratio) {
      $simulation[$i]++;
      return $simulation;
    }
  }
}

function ab_test_redirect_simulation()
{
  $test_count = 100000;
  $res = [0, 0, 0, 0];
  for ($i = 0; $i < $test_count; $i++) {
    $arr = ab_test_redirect() ?? [0, 0, 0, 0];
    for ($j = 0; $j < count($arr); $j++) {
      $res[$j] += $arr[$j];
    }
  }
  for ($k = 0; $k < count($res); $k++) {
    $res[$k] = $res[$k] / $test_count * 100;
    $res[$k] = round($res[$k], 2) . "%";
  }
  var_dump($res);
  exit;
}

add_action('template_redirect', 'ab_test_redirect_simulation');
