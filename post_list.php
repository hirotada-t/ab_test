<?php
function add_ab_test_column($columns)
{
  $columns['custom_column'] = 'テスト状況';
  return $columns;
}
add_filter('manage_posts_columns', 'add_ab_test_column');

function ab_test_column_content($column_name, $post_id)
{
  if ($column_name == 'custom_column') {
    $fields_list = get_post_meta($post_id, "fields_list", true);
    if (!is_array($fields_list)) {
      $fields_list = [];
    }
    $fields_list = array_filter($fields_list, function ($item) {
      return !empty($item['path']) || !empty($item['ratio']);
    });
    $fields_list = array_values($fields_list);

    $run_test = get_post_meta($post_id, "run_test", true);
    $error = get_post_meta($post_id, "error", true);

    if ($run_test && $error <= 0 && count($fields_list) > 0) {
      echo '<span style="color:green;">実施中</span>';
    } elseif (!$run_test) {
      echo '停止中';
    } elseif ($error > 0) {
      echo '停止中<br><span style="color:red;">(' . $error . '個のエラー)</span>';
    } elseif (count($fields_list) <= 0) {
      echo '停止中<br>(パス未設定)';
    } else {
      echo '不明（システム管理者に連絡ください）';
    }
  }
}
add_action('manage_posts_custom_column', 'ab_test_column_content', 10, 2);

function add_ab_test_column_styles()
{
  echo '<style>
          .column-custom_column {
              width: 10%;
          }
        </style>';
}
add_action('admin_head', 'add_ab_test_column_styles');

function quick_edit_ab_test_box($column_name, $post_type)
{
  if ($column_name != 'custom_column') return;
?>
  <fieldset class="inline-edit-col-right">
    <div class="inline-edit-col">
      <div class="inline-edit-group wp-clearfix">
        <label class="alignleft">
          <span class="title">テスト実施</span>
          <input type="checkbox" name="run_test" value="1">
        </label>
      </div>
    </div>
  </fieldset>
<?php
}
add_action('quick_edit_custom_box', 'quick_edit_ab_test_box', 10, 2);

function quick_edit_ab_test_javascript()
{
?>
  <script type="text/javascript">
    jQuery(document).ready(function($) {
      $('#the-list').on('click', 'a.editinline', function() {
        var post_id = $(this).closest('tr').attr('id').replace('post-', '');
        var run_test_val = $('#run_test_value_' + post_id).val();
        $('input[name="run_test"]').prop('checked', run_test_val == "1");
      });
    });
  </script>
<?php
}
add_action('admin_footer-edit.php', 'quick_edit_ab_test_javascript');

function expand_quick_edit_ab_test_link($actions, $post)
{
  $run_test = get_post_meta($post->ID, 'run_test', true);
  $actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="';
  $actions['inline hide-if-no-js'] .= esc_attr('クイック編集') . '"';
  $actions['inline hide-if-no-js'] .= " onclick=\"document.getElementById('run_test_value_{$post->ID}').value = '{$run_test}'; return true;\">";
  $actions['inline hide-if-no-js'] .= 'クイック編集';
  $actions['inline hide-if-no-js'] .= '</a>';
  $actions['inline hide-if-no-js'] .= '<input type="hidden" id="run_test_value_' . $post->ID . '" value="' . esc_attr($run_test) . '">';
  return $actions;
}
add_filter('post_row_actions', 'expand_quick_edit_ab_test_link', 10, 2);

function save_quick_edit_ab_test_data($post_id, $post)
{
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if ($post->post_type != 'post') return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (isset($_REQUEST['run_test'])) {
    update_post_meta($post_id, 'run_test', 1);
  } else {
    update_post_meta($post_id, 'run_test', 0);
  }
}
add_action('save_post', 'save_quick_edit_ab_test_data', 10, 2);
