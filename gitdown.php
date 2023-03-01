<?php
/*
Plugin Name:  Gitdown
Plugin URI:   https://github.com/Maximinodotpy/Gitdown
Description:  Use this Plugin to create, update, delete and manage markdown articles hosted on a remote repository.
Version:      1.0.0
Author:       Maxim Maeder
Author URI:   https://maximmaeder.com
Text Domain:  gitdown
*/

namespace WP\Plugin\Gitdown;

defined('ABSPATH') or die('No direct script access allowed.');

/* http://localhost/git-to-wordpress/wordpress/wp-admin/admin.php */
/* maximmaeder */
/* fjöalsjfölasjfsjö*ç */

class Gitdown
{
    private $articleCollection;

    public function __construct()
    {
        require_once 'php/vendor/autoload.php';
        require_once 'php/helpers.php';
        require_once 'php/ArticleCollection.php';

        // The Root path of this Plugin Directory
        define('MGD_ROOT_PATH', __DIR__ . '/');
        define('MGD_ROOT_URL', plugins_url('', __FILE__) . '/');

        // The Plugin name is used sometimes when the name appears somewhere.
        define('MGD_PLUGIN_NAME', 'Gitdown');

        // The Plugin prefix is used for slugs and settings names to avoid naming collisions.
        define('MGD_PLUGIN_PREFIX', 'gd');

        // Option names
        define('MGD_SETTING_GLOB', MGD_PLUGIN_PREFIX . '_glob_setting');
        define('MGD_SETTING_REPO', MGD_PLUGIN_PREFIX . '_repo_setting');
        define('MGD_SETTING_DEBUG', MGD_PLUGIN_PREFIX . '_debug_setting');
        define('MGD_SETTING_RESOLVER', MGD_PLUGIN_PREFIX . '_resolver_setting');

        // Admin Menu Slugs
        define('MGD_ARTICLES_SLUG', MGD_PLUGIN_PREFIX . '-article-manager');
        define('MGD_SETTINGS_SECTION',  MGD_PLUGIN_PREFIX . '-settings-section');
        define('MGD_SETTINGS_PAGE',  'reading');

        // Where the current Repository is located depends on the repo url.
        define('MGD_MIRROR_PATH', WP_CONTENT_DIR . '/' . MGD_PLUGIN_PREFIX . '_mirror/' . MGD_stringToSlug(get_option(MGD_SETTING_REPO)) . '/');
        define('MGD_MIRROR_URL', WP_CONTENT_URL . '/' . MGD_PLUGIN_PREFIX . '_mirror/' . MGD_stringToSlug(get_option(MGD_SETTING_REPO)) . '/');

        define('MGD_REMOTE_IS_CLONED', is_dir(MGD_MIRROR_PATH . '.git'));

        // Key names for each article object later on.
        define('MGD_REMOTE_KEY', 'remote');
        define('MGD_LOCAL_KEY', 'local');

        // Debug Mode
        define('MGD_DEBUG', boolval(get_option(MGD_SETTING_DEBUG)));

        // Create the Directory where the files are stored in case it does not exist.
        if (!is_dir(MGD_MIRROR_PATH)) {
            mkdir(MGD_MIRROR_PATH, 0777, true);
        }

        chdir(MGD_MIRROR_PATH);
        if (!MGD_REMOTE_IS_CLONED) {
            exec('git clone ' . get_option(MGD_SETTING_REPO) . ' .', $out);
        } else {
            exec('git pull', $out);
        }

        $this->articleCollection = new MGD_ArticleCollection(MGD_MIRROR_PATH, get_option(MGD_SETTING_GLOB));

        // Setting up the Action Hooks
        $this->setupActions();
        $this->setupCustomAction();
    }

    /**
     * Setup admin actions and hooks.
     */
    private function setupActions()
    {
        // Activation and Deactivation Hook
        register_activation_hook(__FILE__, function () { $this->activate(); });
        register_deactivation_hook(__FILE__, function () { $this->deactivate(); });
        wp_enqueue_style(MGD_PLUGIN_PREFIX . '_styles', MGD_ROOT_URL . 'css/gitdown.css');
        
        add_action('admin_init', function () {

            // Redirect if the plugin has been activated.
            if (get_option('MGD_do_activation_redirect', false)) {
                delete_option('MGD_do_activation_redirect');
                
                wp_redirect(home_url('/wp-admin/admin.php?page=gd-article-manager&how_to'));
            }

            register_setting(MGD_SETTINGS_PAGE, MGD_SETTING_GLOB);
            register_setting(MGD_SETTINGS_PAGE, MGD_SETTING_REPO);
            register_setting(MGD_SETTINGS_PAGE, MGD_SETTING_RESOLVER);
            register_setting(MGD_SETTINGS_PAGE, MGD_SETTING_DEBUG);

            add_settings_section(
                MGD_SETTINGS_SECTION,
                MGD_PLUGIN_NAME . ' Settings',
                function () {
                    $this->view(MGD_ROOT_PATH . 'views/settings_head.php');
                },
                MGD_SETTINGS_PAGE
            );


            add_settings_field(
                MGD_SETTING_GLOB,
                'Glob Pattern',
                function () {
                    $this->view(MGD_ROOT_PATH . 'views/settings_glob.php');
                },
                MGD_SETTINGS_PAGE,
                MGD_SETTINGS_SECTION
            );

            add_settings_field(
                MGD_SETTING_REPO,
                'Repository Location',
                function () {
                    $this->view(MGD_ROOT_PATH . 'views/settings_repo.php');
                },
                MGD_SETTINGS_PAGE,
                MGD_SETTINGS_SECTION
            );

            add_settings_field(
                MGD_SETTING_DEBUG,
                'Debug Mode',
                function () {
                    $this->view(MGD_ROOT_PATH . 'views/settings_debug.php');
                },
                MGD_SETTINGS_PAGE,
                MGD_SETTINGS_SECTION
            );

            add_settings_field(
                MGD_SETTING_RESOLVER,
                'Resolver',
                function () {
                    $this->view(MGD_ROOT_PATH . 'views/settings_resolver.php');
                },
                MGD_SETTINGS_PAGE,
                MGD_SETTINGS_SECTION
            );
        });

        // Adding the Admin Menu
        add_action(
            'admin_menu',
            function () {
                add_menu_page(
                    MGD_PLUGIN_NAME,
                    MGD_PLUGIN_NAME,
                    'manage_options',
                    MGD_ARTICLES_SLUG,
                    function () { 
                        if (isset($_GET['how_to'])) include(MGD_ROOT_PATH . 'views/how_to/how_to.php');
                        else include(MGD_ROOT_PATH . 'views/articles.php');
                    },
                    'data:image/svg+xml;base64,' . base64_encode(file_get_contents(MGD_ROOT_PATH . 'images/icon.svg')),
                    20,
                );
                
                add_action('admin_enqueue_scripts', function () {
                    wp_enqueue_script('MGD_vuejs', MGD_ROOT_URL . 'js/vue.js');
                    wp_enqueue_script('MGD_adminjs', MGD_ROOT_URL . 'js/admin.js');
                });
            }
        );
        
        add_action('admin_enqueue_scripts', function ($hook) {
            if ('post.php' != $hook) return;
            if (!$this->articleCollection->get_by_id($_GET['post'])['_is_published']) return;
            
            wp_enqueue_script('edit-warning', MGD_ROOT_URL . 'js/edit-warning.js');
        });
        
        add_filter('manage_post_posts_columns', function($columns) {
            return array_merge($columns, ['MGD_status' => 'Gitdown Status']);
        });
        add_action('manage_post_posts_custom_column', function($column_key, $post_id) {
            if ($column_key == 'MGD_status') {
                    $post_data = $this->articleCollection->get_by_id($post_id);
                    
                    if ($post_data->_is_published) {
                        echo '<div class="tw-font-semibold" >✅ Originates from <br/> Repository</div>';
                    } else {
                        echo '<div class="tw-font-semibold" >❌ Not from Repository</div>';
                    }

            }
        }, 10, 2);

        add_filter( 'post_row_actions', function ( $actions, $post ) {
            $postData = $this->articleCollection->get_by_id($post->ID);

            if ($postData->_is_published) {
                unset( $actions['inline hide-if-no-js'] );
            }
            
            return $actions;
        }, 10, 2 );
        
        // Custom Action for Post List Bulk Actions
        add_filter('bulk_actions-edit-post', function ($bulk_actions) {
            $bulk_actions['MGD_update'] = 'Gitdown: Update';
            return $bulk_actions;
        });

        add_filter('handle_bulk_actions-edit-post', function ($redirect_url, $action, $post_ids) {
            if ($action == 'MGD_update') {

                $count = 0;

                foreach ($post_ids as $post_id) {
                    $postData = $this->articleCollection->get_by_id($post_id);

                    if ($postData['_is_published']) {
                        $count++;
                        $this->articleCollection->updateArticle($postData[MGD_REMOTE_KEY]['slug']);
                    };
                }

                $redirect_url = add_query_arg('MGD_notice', 'Gitdown: Updated ' . $count . ' Posts', $redirect_url);
                $redirect_url = remove_query_arg('MGD_action');
                $redirect_url = remove_query_arg('MGD_slug');
            }
            return $redirect_url;
        }, 10, 3);


        add_action('admin_notices', function () {
            if (!empty($_REQUEST['MGD_notice'])) {
                $notification_text = $_REQUEST['MGD_notice'];

                echo '<div id="message" class="updated notice is-dismissable"><p>' . esc_html($notification_text) . '</p></div>';
            }
        });
    }

    private function setupCustomAction()
    {
        // Ajax Calls
        add_action("wp_ajax_get_all_articles", function () {
            echo json_encode(array(
                'posts' => $this->articleCollection->get_all(),
                'reports' => $this->articleCollection->reports
            ));
            die();
        });
        add_action("wp_ajax_update_article", function () {
            echo json_encode($this->articleCollection->updateArticle($_REQUEST['slug']));
            die();
        });
        add_action("wp_ajax_delete_article", function () {
            echo json_encode($this->articleCollection->deleteArticle($_REQUEST['slug']));
            die();
        });
    }

    public function activate()
    {
        add_option(MGD_SETTING_RESOLVER, 'simple');
        add_option(MGD_SETTING_GLOB, '**/*.md');
        add_option(MGD_SETTING_REPO, 'https://github.com/Maximinodotpy/gitdown-test-repository.git');
        add_option(MGD_SETTING_DEBUG, '0');

        add_option('MGD_do_activation_redirect', true);
    }

    public function deactivate()
    {
        delete_option(MGD_SETTING_RESOLVER);
        delete_option(MGD_SETTING_GLOB);
        delete_option(MGD_SETTING_REPO);
        delete_option(MGD_SETTING_DEBUG);
    }

    private function view($path, $input = [])
    {
        $gtw_data = $input;

        include($path);
    }
};


if (is_admin()) {
    $gtw = new Gitdown();
}
