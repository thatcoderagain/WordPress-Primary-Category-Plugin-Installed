<?php
/**
 * Plugin Name:       Primary Category For Posts
 * Description:       This plugin helps to set a primary category for a post.
 * Plugin URI:        https://github.com/thatcoderagain/WordPress-Primary-Category-Plugin
 * Version:           1.0.0
 * Requires PHP:      7.0
 * PHP Version:       7.0
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
 * @package  CreatePrimaryCategoryForPosts
 * @author   Gaurav Kumar <gaurav.officialsites@gmail.com>
 * @license  GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://github.com/thatcoderagain
 */
class PrimaryCategoryForPosts
{

    use Singleton;

    /**
     * PrimaryCategoryForPost constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'adminPage']);
        add_action('admin_init', [$this, 'settings']);

        $postTypeKey = '_pcfp_'.sanitize_text_field($_GET['post_type'] ?? 'post');
        if (get_option($postTypeKey)) {
            add_action('init', [$this, 'meta_box_register_meta']);
            add_action('enqueue_block_editor_assets', [ $this, 'create_meta_box']);
            add_action('save_post', [$this, 'save_meta_box'], 10, 2);
        }

        if (get_option('_pcfp_update_no_result_search_box') === "1") {
            add_filter('get_search_form', [$this, 'update_search_bar']);
        }

        if (get_option('_pcfp_display_header_search_box') === "1") {
            add_action('get_header', [$this, 'update_search_bar']);
        }

        add_filter('pre_get_posts', [$this, 'update_search_query']);
    }

    /**
     * Method adds a page in admin section for managing plugin options.
     *
     * @return void
     */
    function adminPage()
    {
        add_options_page(
            'Primary Category', 'Primary Category', 'manage_options',
            'primary-category-for-posts', [$this, 'adminPageHTML']
        );
    }

    /**
     * Method generates the plugin setting page markup
     *
     * @return void
     */
    function adminPageHTML()
    {
        ?>
        <div class="wrap">
            <h1>Primary Category Settings</h1>
            <form action="options.php" method="POST">
                <?php
                settings_fields('pcfp_section_fields');
                do_settings_sections('primary-category-for-posts');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Methods generates a dynamic checkbox
     *
     * @param array $args Checkbox options
     *
     * @return void
     */
    function checkboxFieldHTML($args)
    {
        echo '<input type="checkbox" name="' . $args['name'] . '" value="1" ' .
             checked(get_option($args['name']), '1', false) .'/>';
    }

    /**
     * Methods renders the setting options available for the page
     *
     * @return void
     */
    function settings()
    {
        add_settings_section(
            'pcfp_section_1',
            __('Display Options'), null, 'primary-category-for-posts'
        );

        add_settings_field(
            '_pcfp_display_header_search_box', 'Show new search box in header',
            [$this, 'checkboxFieldHTML'], 'primary-category-for-posts',
            'pcfp_section_1', [
                'name' => '_pcfp_display_header_search_box'
            ]
        );

        register_setting(
            'pcfp_section_fields', '_pcfp_display_header_search_box',
            ['sanitize_callback' => 'sanitize_text_field', 'default' => '0']
        );

        add_settings_field(
            '_pcfp_update_no_result_search_box', 'Update no result found search box',
            [$this, 'checkboxFieldHTML'], 'primary-category-for-posts',
            'pcfp_section_1', [
                'name' => '_pcfp_update_no_result_search_box'
            ]
        );

        register_setting(
            'pcfp_section_fields', '_pcfp_update_no_result_search_box',
            ['sanitize_callback' => 'sanitize_text_field', 'default' => '0']
        );

        add_settings_section(
            'pcfp_section_2',
            __('Enable primary category for custom post type'), null,
            'primary-category-for-posts'
        );

        // fetch available custom post types to show checkboxes to
        // enable or disable primary category
        $postTypes = get_post_types(['public' => true], 'names');
        foreach ( $postTypes as $postType ) {
            // ignore if post type is attachment
            if ($postType === 'attachment') {
                continue;
            }

            add_settings_field(
                "_pcfp_$postType", "$postType", [$this, 'checkboxFieldHTML'],
                'primary-category-for-posts', 'pcfp_section_2',
                ['name' => "_pcfp_$postType"]
            );

            register_setting(
                'pcfp_section_fields', "_pcfp_$postType",
                ['sanitize_callback' => 'sanitize_text_field', 'default' => '0']
            );
        }
    }

    /**
     * Methods updated the search query to search post by adding primary category
     * clause with existing query
     *
     * @param WP_Query $query Search query
     *
     * @return void
     */
    function update_search_query($query)
    {
        $category = sanitize_text_field($_GET['primary_category'] ?? '');
        if ($query->is_search && !empty($category)) {
            $meta_query_args = [
                'relationship'=>'AND',
                [
                    'key' => 'pcfp_primary_category',
                    'value' => $category,
                    'compare' => '=',
                ]
            ];
            $query->set('meta_query', $meta_query_args);
        }
    }

    /**
     * Methods updates the search box and add a dropdown to select
     * primary category to search with
     *
     * @param string | Null $form Search box form
     *
     * @return string
     */
    function update_search_bar($form)
    {
        $categories = get_categories(
            [
            'hide_empty' => 0,
            'pad_counts' => true
            ]
        );
        $searchText = sanitize_text_field($_GET['s'] ?? '');
        $selectedCategory = sanitize_text_field($_GET['primary_category'] ?? '');
        $searchBox = '
        <div align="center" style="margin: 2rem;">
            <section id="block-2" class="widget widget_block widget_search">
                <form role="search" method="get" action="/" class="wp-block-search
                __button-outside wp-block-search__text-button wp-block-search">
                    <div class="wp-block-search__inside-wrapper">
                        <input type="search" id="wp-block-search__input-1"
                        class="wp-block-search__input" name="s" required=""
                        value="'.esc_attr__($searchText).'" placeholder="">
                        <select name="primary_category" class="wp-block-categories">
                            <option value="">Any</option>';
        foreach ($categories as $category) {
            $isSelected = $selectedCategory === $category->slug ? 'selected' : '';
            $searchBox .= '<option value="'.esc_attr__($category->slug).'"
            '.$isSelected.'>'.esc_attr__($category->name).'</option>';
        }
                        $searchBox .= '</select>
                        <button type="submit" class="wp-block-search__button ">'.
                                      esc_attr__('Search').'</button>
                    </div>
                </form>
            </section>
        </div>';

        if ($form) {
            return $searchBox;
        }

        echo $searchBox;
    }

    /**
     * This method renders the UI of the meta box on the editor page.
     *
     * @return void;
     */
    public function create_meta_box()
    {
        // load block element and register.
        register_block_type(__DIR__);
    }

    /**
     * This method registers meta box on the editor page.
     *
     * @return void
     */
    function meta_box_register_meta()
    {
        // register meta box in order to receive it's data in request on post save.
        register_meta(
            'post', 'pcfp_primary_category', [
            'show_in_rest' => true,
            'type' => 'string',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function () {
                    return current_user_can('edit_posts');
            }]
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

        // checking for authenticated role authorization to perform the action.
        if(!current_user_can($edit_capability, $post_id)) {
            return;
        }

        // checking for key existence in post data.
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
