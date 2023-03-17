<?php
/*
Plugin Name:  Gitdown
Plugin URI:   https://github.com/Maximinodotpy/Gitdown
Description:  Use this Plugin to create, update, delete and manage markdown articles hosted on a remote repository.
Version:      1.0.7
Author:       Maxim Maeder
Author URI:   https://maximmaeder.com
Text Domain:  gitdown
*/

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

class Gitdown
{
    private $article_collection;

    public function __construct()
    {
        require_once 'vendor/autoload.php';
        /* require_once 'inc/Helpers.php';
        require_once 'inc/ArticleCollection.php';
        require_once 'inc/Resolver.php'; */

        // The Root path of this Plugin Directory
        define('MGD_ROOT_PATH', __DIR__ . '/');
        define('MGD_ROOT_URL', plugins_url('', __FILE__) . '/');

        // The Plugin prefix is used for slugs and settings names to avoid naming collisions.
        define('MGD_PLUGIN_PREFIX', 'gd');

        // Option names
        define('MGD_SETTING_GLOB', MGD_PLUGIN_PREFIX . '_glob_setting');
        define('MGD_SETTING_REPO', MGD_PLUGIN_PREFIX . '_repo_setting');
        define('MGD_SETTING_DEBUG', MGD_PLUGIN_PREFIX . '_debug_setting');
        define('MGD_SETTING_RESOLVER', MGD_PLUGIN_PREFIX . '_resolver_setting');
        define('MGD_SETTING_CRON', MGD_PLUGIN_PREFIX . '_cron_setting');

        // Admin Menu Slugs
        define('MGD_ARTICLES_SLUG', MGD_PLUGIN_PREFIX . '-article-manager');
        define('MGD_SETTINGS_SECTION',  MGD_PLUGIN_PREFIX . '-settings-section');
        define('MGD_SETTINGS_PAGE',  'reading');

        // Where the current Repository is located depends on the repo url.
        $repo_nice_name =
            Inc\Helpers::string_to_slug(basename(dirname(get_option(MGD_SETTING_REPO))))
            .'-'.
            Inc\Helpers::string_to_slug(rtrim(basename(get_option(MGD_SETTING_REPO)), '.git'));

        define('MGD_MIRROR_PATH', WP_CONTENT_DIR . '/' . MGD_PLUGIN_PREFIX . '_mirror/' . $repo_nice_name . '/');
        define('MGD_MIRROR_URL', WP_CONTENT_URL . '/' . MGD_PLUGIN_PREFIX . '_mirror/' . $repo_nice_name . '/');

        define('MGD_REMOTE_IS_CLONED', is_dir(MGD_MIRROR_PATH . '.git'));

        // Create the Directory where the files are stored in case it does not exist.
        if (!is_dir(MGD_MIRROR_PATH)) {
            mkdir(MGD_MIRROR_PATH, 0777, true);
        }

        $this->article_collection = new Inc\ArticleCollection(MGD_MIRROR_PATH, get_option(MGD_SETTING_GLOB));

        // Setting up the Action Hooks
        $this->setupActions();
    }

    /**
     * Setup admin actions and hooks.
     */
    private function setupActions()
    {
        // Activation and Deactivation Hook
        register_activation_hook(__FILE__, function () { $this->activate(); });
        register_deactivation_hook(__FILE__, function () { $this->deactivate(); });

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
            register_setting(MGD_SETTINGS_PAGE, MGD_SETTING_CRON);

            add_settings_section(
                MGD_SETTINGS_SECTION,
                ' Settings',
                function () { include(MGD_ROOT_PATH . 'views/settings_head.php'); },
                MGD_SETTINGS_PAGE
            );


            add_settings_field(
                MGD_SETTING_GLOB,
                'Glob Pattern',
                function () { include(MGD_ROOT_PATH . 'views/settings_glob.php'); },
                MGD_SETTINGS_PAGE,
                MGD_SETTINGS_SECTION
            );

            add_settings_field(
                MGD_SETTING_REPO,
                'Repository Location',
                function () { include(MGD_ROOT_PATH . 'views/settings_repo.php'); },
                MGD_SETTINGS_PAGE,
                MGD_SETTINGS_SECTION
            );

            add_settings_field(
                MGD_SETTING_RESOLVER,
                'Resolver',
                function () { include(MGD_ROOT_PATH . 'views/settings_resolver.php'); },
                MGD_SETTINGS_PAGE,
                MGD_SETTINGS_SECTION
            );

            add_settings_field(
                MGD_SETTING_CRON,
                'Automatic Updating',
                function () { include(MGD_ROOT_PATH . 'views/settings_automatic.php'); },
                MGD_SETTINGS_PAGE,
                MGD_SETTINGS_SECTION
            );
        });

        $plg_name = plugin_basename(__FILE__);
        add_action("plugin_action_links_$plg_name", function($links) {
            array_push($links, '<a href="options-reading.php">Settings</a>');
            array_push($links, '<a href="admin.php?page=gd-article-manager">Overview</a>');
            return $links;
        });

        // Adding the Admin Menu
        add_action(
            'admin_menu',
            function () {
                add_menu_page(
                    'Gitdown',
                    'Gitdown',
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
                    wp_enqueue_style(MGD_PLUGIN_PREFIX . '_styles', MGD_ROOT_URL . 'css/gitdown.css');
                });
            }
        );

        add_action('admin_enqueue_scripts', function ($hook) {
            if ('post.php' != $hook) return;
            if (!$this->article_collection->get_by_id($_GET['post'])->_is_published) return;

            wp_enqueue_script('edit-warning', MGD_ROOT_URL . 'js/edit-warning.js');
        });

        add_filter('manage_post_posts_columns', function($columns) {
            return array_merge($columns, ['MGD_status' => 'Gitdown Status']);
        });
        add_action('manage_post_posts_custom_column', function($column_key, $post_id) {
            if ($column_key == 'MGD_status') {
                    $post_data = $this->article_collection->get_by_id($post_id);

                    if ($post_data->_is_published) {
                        echo '<div class="tw-font-semibold" >✅ Originates from <br/> Repository</div>';
                    } else {
                        echo '<div class="tw-font-semibold" >❌ Not from Repository</div>';
                    }

            }
        }, 10, 2);

        add_filter( 'post_row_actions', function ( $actions, $post ) {
            $postData = $this->article_collection->get_by_id($post->ID);

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
                    $postData = $this->article_collection->get_by_id($post_id);

                    if ($postData->_is_published) {
                        $count++;
                        $this->article_collection->update_post($postData->remote->slug);
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
                echo '<div id="message" class="updated notice is-dismissable"><p>' . esc_html($_REQUEST['MGD_notice']) . '</p></div>';
            }
        });


        // Ajax Calls
        add_action("wp_ajax_get_all_articles", function () {
            echo json_encode(array(
                'posts' => $this->article_collection->get_all(),
                'reports' => $this->article_collection->reports
            ));
            die();
        });
        add_action("wp_ajax_update_article", function () {
            echo json_encode($this->article_collection->update_post($_REQUEST['slug']));
            die();
        });
        add_action("wp_ajax_delete_article", function () {
            echo json_encode($this->article_collection->delete_post($_REQUEST['slug']));
            die();
        });
        add_action("wp_ajax_update_oldest", function () {
            if (! (bool) get_option(MGD_SETTING_CRON) ) return;

            $oldest_article = $this->article_collection->get_oldest()[0];

            Inc\Helpers::log(sprintf('Auto Updating: %s', $oldest_article->remote->name));

            echo json_encode($this->article_collection->update_post($oldest_article->remote->slug));

            die();
        });


        add_action('init', function() {
            if (wp_doing_ajax()) return;
            if (! (bool) get_option(MGD_SETTING_CRON) ) return;

            add_action('wp_print_scripts', function() {
                ?>
                <script>
                    console.log('MGD Autoupdate starting ...');

                    let i = 0;
                    (async () => {
                        do {
                            console.log('MGD Autoupdate Request ...');

                            const form_data = new FormData()
                            form_data.append('action', 'update_oldest')

                            const re = await fetch(ajaxurl, {
                                method: 'POST',
                                body: form_data,
                            })

                            try {
                                console.log((await re.json()));
                            } catch (error) {
                                console.log(error);
                            }

                            i++
                        } while (<?php echo is_admin() ? 'true' : 'i < 5' ?>)
                    })()
                </script>
                <?php
            });
        });
    }

    public function activate()
    {
        add_option(MGD_SETTING_RESOLVER, 'simple');
        add_option(MGD_SETTING_GLOB, 'simple/*.md');
        add_option(MGD_SETTING_REPO, 'https://github.com/Maximinodotpy/gitdown-test-repository.git');
        add_option(MGD_SETTING_DEBUG, '0');
        add_option(MGD_SETTING_CRON, false);

        add_option('MGD_do_activation_redirect', true);
    }

    public function deactivate()
    {
        delete_option(MGD_SETTING_RESOLVER);
        delete_option(MGD_SETTING_GLOB);
        delete_option(MGD_SETTING_REPO);
        delete_option(MGD_SETTING_DEBUG);
        delete_option(MGD_SETTING_CRON);
    }
};

$b8cc4bfd_b866_4956_89db_2f0eeb671e61 = new Gitdown();