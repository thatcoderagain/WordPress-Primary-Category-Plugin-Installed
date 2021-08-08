<?php
/**
 * Plugin Name:       Primary Category For Posts
 * Description:       This plugin helps to set a primary category for a post.
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Gaurav Kumar <gaurav.officialsites@gmail.com>
 * Author URI:        https://github.com/thatcoderagain
 */

// security to prevent direct access of php files.
if (!defined('ABSPATH')) {
    exit;
}

require_once "traits/Singleton.php";

/**
 * Class PrimaryCategoryForPosts
 *
 * @category Open_Source
 * @package  CreatePrimaryCategoryMetaBox
 * @author   Gaurav Kumar <gaurav.officialsites@gmail.com>
 * @license  GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/thatcoderagain/WordPress-Primary-Category-Plugin/blob/master/wp-content/plugins/primary-category-for-posts/primary-category-for-posts.php
 */
class PrimaryCategoryForPosts
{

    use Singleton;

    /**
     * PrimaryCategoryForPost constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'meta_box_register_meta']);
        add_action('enqueue_block_editor_assets', [ $this, 'create_meta_box']);
        add_action('save_post', [$this, 'save_meta_box'], 10, 2);
    }

    /**
     * This method renders the UI of the meta box on page loads
     *
     * @return void
     */
    public function create_meta_box()
    {
        // load block element and register.
        register_block_type(__DIR__);
    }

    /**
     * This method registers meta box on the page.
     *
     * @return void
     */
    function meta_box_register_meta()
    {
        // registering meta box in order to receive it's data in request on post save.
        register_meta(
            'post', 'pcfp_primary_category', array(
            'show_in_rest' => true,
            'type' => 'string',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function () {
                    return current_user_can('edit_posts');
            })
        );
    }

    /**
     * This method registers meta box on the page.
     *
     * @param int         $post_id Post id of the post user is editing
     * @param ArrayObject $post    Instance of the post
     *
     * @return void
     */
    public function save_meta_box($post_id, $post)
    {
        $edit_capability = get_post_type_object($post->post_type)->cap->edit_post;

        // checking for authenticated role authorization to perform the action
        if(!current_user_can($edit_capability, $post_id)) {
            return;
        }

        // checking for key existence in post data
        if(array_key_exists('pcfp_primary_category', $_POST)) {
            update_post_meta(
                $post_id,
                'pcfp_primary_category',
                sanitize_text_field($_POST['pcfp_primary_category'])
            );
        }
    }
}

/**
 * Initiating a single instance of the plugin.
 */
$primaryCategoryForPost = PrimaryCategoryForPosts::get_instance();
