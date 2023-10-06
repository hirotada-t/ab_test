<?php
function ab_test_redirect()
{
  if (is_single()) {
    $post_id = get_the_ID();
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

    $rand_num = mt_rand(1, $total_ratio);
    $current_ratio = 0;
    
    foreach ($redirects as $redirect) {
      $current_ratio += $redirect['ratio'];
      if ($rand_num <= $current_ratio) {
        wp_redirect(home_url($redirect['path']));
        exit;
      }
    }
  }
}
add_action('template_redirect', 'ab_test_redirect');
