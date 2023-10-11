<?php

function ab_test_redirect()
{
  $post_id = get_the_ID();
  $run_test = get_post_meta($post_id, "run_test", true);

  if (is_single() && $run_test) {
    $root_path = get_page_uri($post_id);
    $error = get_post_meta($post_id, "error", true);
    if ($error > 0) {
      return;
    }

    $redirects = [];
    $total_ratio = 0;

    for ($i = 1; $i <= 5; $i++) {
      $path = get_post_meta($post_id, "path_$i", true);
      $ratio = get_post_meta($post_id, "ratio_$i", true);

      if (!empty($path) && !empty($ratio)) {
        $redirects[] = [
          'path' => $path,
          'ratio' => $ratio
        ];
        $total_ratio += $ratio;
      }
    }

    $rand_num = mt_rand(0, $total_ratio);
    $current_ratio = 0;

    foreach ($redirects as $redirect) {
      $current_ratio += $redirect['ratio'];
      if ($rand_num <= $current_ratio) {
        if ($redirect['path'] !== $root_path) {
          wp_redirect(home_url($redirect['path']));
          exit;
        }
      }
    }
  }
}
add_action('template_redirect', 'ab_test_redirect');
