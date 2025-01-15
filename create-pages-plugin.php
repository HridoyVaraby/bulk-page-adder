<?php
    /*
    Plugin Name: Create Pages Plugin
    Description: A plugin to create pages from admin settings.
    Version: 1.0
    Author: Bolt
    */

    if (!defined('ABSPATH')) {
      exit; // Exit if accessed directly
    }

    class Create_Pages_Plugin {
      public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_create_pages', array($this, 'create_pages'));
        add_action('admin_notices', array($this, 'display_notices'));
      }

      public function add_settings_page() {
        add_menu_page(
          'Create Pages',
          'Create Pages',
          'manage_options',
          'create-pages',
          array($this, 'settings_page_html')
        );
      }

      public function register_settings() {
        register_setting('create_pages_options_group', 'create_pages_titles');
      }

      public function settings_page_html() {
        ?>
        <div class="wrap">
          <h1>Create Pages</h1>
          <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php
            settings_fields('create_pages_options_group');
            do_settings_sections('create-pages');
            ?>
            <table class="form-table">
              <tr valign="top">
                <th scope="row">Page Titles</th>
                <td>
                  <textarea name="create_pages_titles" rows="10" cols="50"><?php echo esc_textarea(get_option('create_pages_titles')); ?></textarea>
                  <p class="description">Enter one page title per line.</p>
                </td>
              </tr>
            </table>
            <input type="hidden" name="action" value="create_pages">
            <?php submit_button('Create Pages', 'primary', 'create_pages_button'); ?>
          </form>
        </div>
        <?php
      }

      public function create_pages() {
        if (!current_user_can('manage_options')) {
          wp_die('You do not have sufficient permissions to access this page.');
        }

        if (isset($_POST['create_pages_titles'])) {
          $titles = explode("\n", $_POST['create_pages_titles']);
          $feedback = array();

          foreach ($titles as $title) {
            $title = trim($title);
            if (!empty($title)) {
              $page_id = wp_insert_post(array(
                'post_title' => $title,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'page'
              ));

              if (is_wp_error($page_id)) {
                $feedback[] = 'Error creating page "' . esc_html($title) . '": ' . esc_html($page_id->get_error_message());
              } else {
                $feedback[] = 'Page "' . esc_html($title) . '" created successfully.';
              }
            }
          }

          if (!empty($feedback)) {
            set_transient('create_pages_feedback', $feedback, 30);
          }

          // Clear the textarea after processing
          update_option('create_pages_titles', '');
        }

        wp_redirect(admin_url('admin.php?page=create-pages'));
        exit;
      }

      public function display_notices() {
        $feedback = get_transient('create_pages_feedback');
        if ($feedback) {
          foreach ($feedback as $message) {
            if (strpos($message, 'Error') !== false) {
              echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
            } else {
              echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
          }
          delete_transient('create_pages_feedback');
        }
      }
    }

    if (is_admin()) {
      $create_pages_plugin = new Create_Pages_Plugin();
    }
    ?>
