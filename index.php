<?php
/*
Plugin Name:  Gitdown
Author:       Maxim Maeder
Author URI:   https://maximmaeder.com
Plugin URI:   https://github.com/Maximinodotpy/Gitdown
Description:  Use this Plugin to create, update, delete and manage markdown articles hosted on a remote repository.
Version:      0.2
Text Domain:  gitdown
*/

class Gitdown
{
    private $articleCollection;
    private $logger;

    public function __construct()
    {
        require_once 'php/vendor/autoload.php';
        require_once 'php/helpers.php';
        require_once 'php/ArticleCollection.php';
        require_once 'php/Logger.php';

        // The Root path of this Plugin Directory
        define('GD_ROOT_PATH', __DIR__ . '/');
        define('GD_ROOT_URL', plugins_url('', __FILE__) . '/');

        // The Plugin name is used sometimes when the name appears somewhere.
        define('GD_PLUGIN_NAME', 'Gitdown');

        // The Plugin prefix is used for slugs and settings names to avoid naming collisions.
        define('GD_PLUGIN_PREFIX', 'gd');

        // Option names
        define('GD_SETTING_GLOB', GD_PLUGIN_PREFIX . '_glob_setting');
        define('GD_SETTING_REPO', GD_PLUGIN_PREFIX . '_repo_setting');
        define('GD_SETTING_DEBUG', GD_PLUGIN_PREFIX . '_debug_setting');
        define('GD_SETTING_RESOLVER', GD_PLUGIN_PREFIX . '_resolver_setting');

        // Admin Menu Slugs
        define('GD_ARTICLES_SLUG', GD_PLUGIN_PREFIX . '-article-manager');
        define('GD_SETTINGS_SECTION',  GD_PLUGIN_PREFIX . '-settings-section');
        define('GD_SETTINGS_PAGE',  'reading');

        // Where the current Repository is located depends on the repo url.
        define('GD_MIRROR_PATH', WP_CONTENT_DIR . '/' . GD_PLUGIN_PREFIX . '_mirror/' . gd_stringToSlug(get_option(GD_SETTING_REPO)) . '/');
        define('GD_MIRROR_URL', WP_CONTENT_URL . '/' . GD_PLUGIN_PREFIX . '_mirror/' . gd_stringToSlug(get_option(GD_SETTING_REPO)) . '/');

        define('GD_REMOTE_IS_CLONED', is_dir(GD_MIRROR_PATH . '.git'));

        // Key names for each article object later on.
        define('GD_REMOTE_KEY', 'remote');
        define('GD_LOCAL_KEY', 'local');

        // Debug Mode
        define('GD_DEBUG', boolval(get_option(GD_SETTING_DEBUG)));


        // Logging
        $logLocation = GD_ROOT_PATH . 'logs/log-' . date("d-m-y_h-i-s") . '.json';
        $this->logger = new GD_Logger($logLocation);

        $this->logger->info('Start');
        $this->logger->info('Start Meta Data', [
            'GD_DEBUG' => GD_DEBUG,
            'GD_ROOT_PATH' => GD_ROOT_PATH,
            'GD_REMOTE_IS_CLONED' => GD_REMOTE_IS_CLONED,
            'GD_PLUGIN_NAME' => GD_PLUGIN_NAME,
            'GD_PLUGIN_PREFIX' => GD_PLUGIN_PREFIX,
            'GD_SETTING_GLOB' => GD_SETTING_GLOB,
            'GD_SETTING_REPO' => GD_SETTING_REPO,
            'GD_SETTING_DEBUG' => GD_SETTING_DEBUG,
            'GD_SETTING_RESOLVER' => GD_SETTING_RESOLVER,
            'GD_ARTICLES_SLUG' => GD_ARTICLES_SLUG,
            'GD_SETTINGS_SECTION' => GD_SETTINGS_SECTION,
            'GD_SETTINGS_PAGE' => GD_SETTINGS_PAGE,
            'GD_MIRROR_PATH' => GD_MIRROR_PATH,
            'GD_ARTICLES_SLUG' => GD_ARTICLES_SLUG,
            'GD_REMOTE_KEY' => GD_REMOTE_KEY,
            'GD_LOCAL_KEY' => GD_LOCAL_KEY,
            'GD_ROOT_URL' => GD_ROOT_URL,
        ]);


        // Create the Directory where the files are stored in case it does not exist.
        if (!is_dir(GD_MIRROR_PATH)) {
            mkdir(GD_MIRROR_PATH, 0777, true);
        }

        chdir(GD_MIRROR_PATH);
        if (!GD_REMOTE_IS_CLONED) {
            exec('git clone ' . get_option(GD_SETTING_REPO) . ' .', $out);
        } else {
            exec('git pull', $out);
        }

        $this->articleCollection = new GD_ArticleCollection();
        $this->articleCollection->logger = $this->logger;

        $this->articleCollection->parseDirectory(GD_MIRROR_PATH, get_option(GD_SETTING_GLOB));
        $this->logger->info('Populating Article Collection', 'Count: ' . count($this->articleCollection->get_all()));

        // Setting up the Action Hooks
        $this->setupActions();
        $this->setupCustomAction();
        $this->logger->info('Set up Action Hooks');
    }

    /**
     * Setup admin actions and hooks.
     */
    private function setupActions()
    {
        // Activation and Deactivation Hook
        register_activation_hook(__FILE__, function () { $this->activate(); });
        register_deactivation_hook(__FILE__, function () { $this->deactivate(); });
        wp_enqueue_style(GD_PLUGIN_PREFIX . '_styles', GD_ROOT_URL . 'css/gitdown.css');

        add_action( 'init', function() {
            load_plugin_textdomain( 'gitdown', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/po/' );
        } );
        
        add_action('admin_init', function () {


            register_setting(GD_SETTINGS_PAGE, GD_SETTING_GLOB);
            register_setting(GD_SETTINGS_PAGE, GD_SETTING_REPO);
            register_setting(GD_SETTINGS_PAGE, GD_SETTING_RESOLVER);
            register_setting(GD_SETTINGS_PAGE, GD_SETTING_DEBUG);

            add_settings_section(
                GD_SETTINGS_SECTION,
                GD_PLUGIN_NAME . ' Settings',
                function () {
                    $this->view(GD_ROOT_PATH . 'views/settings_head.php');
                },
                GD_SETTINGS_PAGE
            );


            add_settings_field(
                GD_SETTING_GLOB,
                'Glob Pattern',
                function () {
                    $this->view(GD_ROOT_PATH . 'views/settings_glob.php');
                },
                GD_SETTINGS_PAGE,
                GD_SETTINGS_SECTION
            );

            add_settings_field(
                GD_SETTING_REPO,
                'Repository Location',
                function () {
                    $this->view(GD_ROOT_PATH . 'views/settings_repo.php');
                },
                GD_SETTINGS_PAGE,
                GD_SETTINGS_SECTION
            );

            add_settings_field(
                GD_SETTING_DEBUG,
                'Debug Mode',
                function () {
                    $this->view(GD_ROOT_PATH . 'views/settings_debug.php');
                },
                GD_SETTINGS_PAGE,
                GD_SETTINGS_SECTION
            );

            add_settings_field(
                GD_SETTING_RESOLVER,
                'Resolver',
                function () {
                    $this->view(GD_ROOT_PATH . 'views/settings_resolver.php');
                },
                GD_SETTINGS_PAGE,
                GD_SETTINGS_SECTION
            );
        });

        // Adding the Admin Menu
        add_action(
            'admin_menu',
            function () {
                add_menu_page(
                    GD_PLUGIN_NAME,
                    GD_PLUGIN_NAME,
                    'manage_options',
                    GD_ARTICLES_SLUG,
                    function () { include(GD_ROOT_PATH . 'views/articles.php'); },
                    'data:image/svg+xml;base64,' . base64_encode(file_get_contents(GD_ROOT_PATH . 'images/icon.svg')),
                    20,
                );

                wp_enqueue_style(GD_PLUGIN_PREFIX . '_styles_tour', GD_ROOT_URL . 'css/tour.css');
                
                add_action('admin_enqueue_scripts', function () {
                    
                    wp_enqueue_script('edit-warning', GD_ROOT_URL . 'js/tour.js');
                    wp_enqueue_script('gd_vuejs', GD_ROOT_URL . 'js/vue.js');
                    wp_enqueue_script('gd_adminjs', GD_ROOT_URL . 'js/admin.js');
                });
            }
        );
        
        add_action('admin_enqueue_scripts', function ($hook) {
            if ('post.php' != $hook) return;
            if (!$this->articleCollection->get_by_id($_GET['post'])['_is_published']) return;
            
            wp_enqueue_script('edit-warning', GD_ROOT_URL . 'js/edit-warning.js');
        });
        
        add_filter('manage_post_posts_columns', function($columns) {
            return array_merge($columns, ['gd_status' => 'Gitdown Status']);
        });
        add_action('manage_post_posts_custom_column', function($column_key, $post_id) {
            if ($column_key == 'gd_status') {
                    $post_data = $this->articleCollection->get_by_id($post_id);
                    
                    if ($post_data['_is_published']) {
                        echo '<div class="tw-font-semibold" >✅ Originates from <br/> Repository</div>';
                    } else {
                        echo '<div class="tw-font-semibold" >❌ Not from Repository</div>';
                    }

            }
        }, 10, 2);

        add_filter( 'post_row_actions', function ( $actions, $post ) {
            $this->logger->info('Inline Actions', $post->ID);
            $postData = $this->articleCollection->get_by_id($post->ID);

            if ($postData['_is_published']) {
                unset( $actions['inline hide-if-no-js'] );
            }
            
            return $actions;
        }, 10, 2 );
        
        // Custom Action for Post List Bulk Actions
        add_filter('bulk_actions-edit-post', function ($bulk_actions) {
            $bulk_actions['gd_update'] = 'Gitdown: Update';
            return $bulk_actions;
        });

        add_filter('handle_bulk_actions-edit-post', function ($redirect_url, $action, $post_ids) {
            if ($action == 'gd_update') {

                $count = 0;

                foreach ($post_ids as $post_id) {
                    $postData = $this->articleCollection->get_by_id($post_id);

                    if ($postData['_is_published']) {
                        $count++;
                        $this->articleCollection->updateArticle($postData[GD_REMOTE_KEY]['slug']);
                    };
                }

                $redirect_url = add_query_arg('gd_notice', 'Gitdown: Updated ' . $count . ' Posts', $redirect_url);
                $redirect_url = remove_query_arg('gd_action');
                $redirect_url = remove_query_arg('gd_slug');
            }
            return $redirect_url;
        }, 10, 3);


        add_action('admin_notices', function () {
            if (!empty($_REQUEST['gd_notice'])) {
                $notification_text = $_REQUEST['gd_notice'];

                echo '<div id="message" class="updated notice is-dismissable"><p>' . esc_html($notification_text) . '</p></div>';
            }
        });

        add_action('admin_head', function () {
            $current_screen = get_current_screen();
            
            if ($current_screen->id != 'toplevel_page_'.GD_ARTICLES_SLUG) return;
            
            $current_screen->add_help_tab(array(
                'id' => 'resolving_help',
                'title' => 'Resolving',
                'callback' => function() { include(GD_ROOT_PATH.'views/help_resolving.php'); },
            ));

            $current_screen->add_help_tab(array(
                'id' => 'ui_help',
                'title' => 'User Interface',
                'callback' => function() { include(GD_ROOT_PATH.'views/help_userinterface.php'); },
            ));
        });
    }

    private function setupCustomAction()
    {
        // Ajax Calls
        add_action("wp_ajax_get_all_articles", function () {
            echo json_encode($this->articleCollection->get_all());
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
        add_option(GD_SETTING_RESOLVER, 'simple');
        add_option(GD_SETTING_GLOB, '**/*.md');
        add_option(GD_SETTING_REPO, 'https://github.com/Maximinodotpy/gitdown-test-repository.git');
        add_option(GD_SETTING_DEBUG, '0');
    }

    public function deactivate()
    {
        delete_option(GD_SETTING_RESOLVER);
        delete_option(GD_SETTING_GLOB);
        delete_option(GD_SETTING_REPO);
        delete_option(GD_SETTING_DEBUG);
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
