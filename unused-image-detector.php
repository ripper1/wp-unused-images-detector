/*
Plugin Name: Unused Image Detector
Description: Detects and bulk deletes unused images from the Media Library.
Version: 1.0
Author: [Mirsad Hamustafic]
*/

<?php
function detect_used_images() {
  $used_images = array();
  $content = get_post_field('post_content', get_the_ID(), 'raw');
  if ($content === false) {
    trigger_error('Failed to get post content', E_USER_WARNING);
    return;
  }
  preg_match_all('/<img [^>]*src=["\']([^"\']+)/i', $content, $matches);
  if ($matches === false) {
    trigger_error('Failed to match img tags', E_USER_WARNING);
    return;
  }
  foreach ($matches[1] as $url) {
    $used_images[] = $url;
  }
  update_option('used_images', $used_images);
}

/*To create a new menu item in the WordPress dashboard for deleting unused images*/

function unused_images_menu() {
  add_submenu_page('tools.php', 'Unused Images', 'Unused Images', 'manage_options', 'unused-images', 'unused_images_page');
}
add_action('admin_menu', 'unused_images_menu');


function unused_images_page() {
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  if (isset($_POST['delete_unused_images'])) {
    $used_images = get_option('used_images', array());
    if ($used_images === false) {
      trigger_error('Failed to get used images', E_USER_WARNING);
      return;
    }
    $all_images = get_posts(array(
      'post_type' => 'attachment',
      'posts_per_page' => -1,
      'post_status' => 'inherit',
      'fields' => 'ids',
    ));
    if ($all_images === false) {
      trigger_error('Failed to get all images', E_USER_WARNING);
      return;
    }

    $unused_images = array_diff($all_images, $used_images);

    foreach ($unused_images as $image_id) {
      if (wp_delete_attachment($image_id, true) === false) {
        trigger_error('Failed to delete attachment: ' . $image_id, E_USER_WARNING);
      }
    }

    echo '<div class="updated"><p>Unused images deleted successfully!</p></div>';
  }

  echo '<div class="wrap">';
  echo '<h1>Unused Images</h1>';
  echo '<p>This tool will delete all images from the Media Library that are  not used on any page or post.</p>';
  echo '<form method="post">';
  echo '<p><input type="submit" name="delete_unused_images" value="Delete Unused Images" class="button button-primary"></p>';
  echo '</form>';
  echo '</div>';
}

function unused_images_styles() {
  echo '<style>
    .wrap {
      max-width: 800px;
      margin: 0 auto;
    }
    h1 {
      margin-top: 30px;
      margin-bottom: 20px;
    }
    p.submit {
      margin-top: 20px;
    }
  </style>';
}
add_action('admin_head', 'unused_images_styles');
?>
