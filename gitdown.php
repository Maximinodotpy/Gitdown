<?php
/*
Plugin Name:  Gitdown
Plugin URI:   https://github.com/Maximinodotpy/Gitdown
Description:  Use this Plugin to create, update, delete and manage markdown articles hosted on a remote repository.
Version:      __MGD_VERSION__
Author:       Maxim Maeder
Author URI:   https://maximmaeder.com
Text Domain:  gitdown
*/

/*
bn: maximmaeder
pw: $P$BNA.8vIf6NPsAd4qY67U0f7HvXGJce.
https://codebeautify.org/wordpress-password-hash-generator
*/

use Inc\Helpers;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

class Gitdown
{
    private $article_collection;
    private $option_slugs = [];

    public function __construct()
    {
        require_once 'vendor/autoload.php';

        // Plugin Prefix: mgd(_)

        // The Root path of this Plugin Directory
        define('MGD_ROOT_PATH', __DIR__ . '/');
        define('MGD_ROOT_URL', plugins_url('/', __FILE__));

        // Option names, labels, and default values
        $this->option_slugs = (object) [
            'mgd_glob_setting' => (object) [
                'default' => 'simple/*.md',
                'label'   => 'Glob Pattern',
            ],
            'mgd_repo_setting' => (object) [
                'default' => 'https://github.com/Maximinodotpy/gitdown-test-repository.git',
                'label' => 'Repository Location',
            ],
            'mgd_resolver_setting'  => (object) [
                'default' => 'simple',
                'label' => 'Resolver',
            ],
            'mgd_cron_setting' => (object) [
                'default' => false,
                'label' => 'Automatic Updates',
            ],
        ];

        // Where the current Repository is located depends on the repo url
        $repo_nice_name = Inc\Helpers::string_to_slug(
            'mgd_'
            . basename(dirname(get_option('mgd_repo_setting')))
            . '-'
            . rtrim(basename(get_option('mgd_repo_setting')), '.git')
        );

        define('MGD_MIRROR_PATH', WP_CONTENT_DIR . '/mgd_mirror/' . $repo_nice_name . '/');
        define('MGD_MIRROR_URL', WP_CONTENT_URL . '/mgd_mirror/' . $repo_nice_name . '/');

        define('MGD_REMOTE_IS_CLONED', is_dir(MGD_MIRROR_PATH . '.git'));

        // Create the Directory where the files are stored in case it does not exist.
        if (!is_dir(MGD_MIRROR_PATH)) {
            mkdir(MGD_MIRROR_PATH, 0777, true);
        }

        $this->article_collection = new Inc\ArticleCollection();

        // Setting up the Action Hooks
        $this->setupActions();

        // Deleting unneded Mirror folders.
        chdir(dirname(MGD_MIRROR_PATH));
        $d = dir(".");
        while (false !== ($entry = $d->read()))
        {
            if (is_dir($entry) && ($entry != '.') && ($entry != '..') && ($entry != $repo_nice_name)) {
                Helpers::delete_directory($entry);
            }
        }
        $d->close();
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
            if (get_option('mgd_do_activation_redirect', false)) {
                delete_option('mgd_do_activation_redirect');

                wp_redirect(home_url('/wp-admin/admin.php?page=mgd-article-manager&how_to'));
            }

            add_settings_section(
                'mgd-settings-section',
                'Gitdown Settings',
                function () { include(MGD_ROOT_PATH . 'templates/settings/head.php'); },
                'reading'
            );

            // Register the Settings for the reading page.
            foreach ($this->option_slugs as $slug => $slug_meta) {
                register_setting('reading', $slug);

                // Add the settings section
                add_settings_field(
                    $slug,
                    $slug_meta->label,
                    function () use ($slug) { include(MGD_ROOT_PATH . 'templates/settings/'. $slug .'.php'); },
                    'reading',
                    'mgd-settings-section',
                );
            }
        });

        add_action("plugin_action_links_" . plugin_basename(__FILE__), function($links) {
            array_push($links, '<a href="options-reading.php">Settings</a>');
            array_push($links, '<a href="admin.php?page=mgd-article-manager">Overview</a>');
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
                    'mgd-article-manager',
                    function () {
                        if (isset($_GET['how_to'])) include(MGD_ROOT_PATH . 'templates/how_to/how_to.php');

                        else if (isset($_GET['raw_data'])) {

                            $data = isset($_GET['slug'])  ? $this->article_collection->get_by_slug($_GET['slug']) : $this->article_collection->get_all();

                            echo '<div class="wrap"><pre style="white-space: pre-wrap;" id="raw_data_container">';
                            echo '</pre>';
                            echo '
                            <script>
                            const raw_data = ' . json_encode($data) . ';
                            renderJson({ data: raw_data, top_level_root: raw_data_container });
                            var element = document.createElement("link");
                            element.setAttribute("rel", "stylesheet");
                            element.setAttribute("type", "text/css");
                            element.setAttribute("href", "' . MGD_ROOT_URL . 'css/json.css");
                            document.getElementsByTagName("head")[0].appendChild(element);
                            </script>';
                        }

                        else include(MGD_ROOT_PATH . 'templates/articles.php');
                    },
                    'data:image/svg+xml;base64,' . base64_encode(file_get_contents(MGD_ROOT_PATH . 'images/icon.svg')),
                    110,
                );

                add_action('admin_enqueue_scripts', function () {
                    $plugin_version = get_plugin_data(MGD_ROOT_PATH.'gitdown.php')['Version'];
                    wp_enqueue_script('mgd_vuejs', MGD_ROOT_URL . 'js/vue.js', [], $plugin_version);
                    wp_enqueue_script('mgd_jsonjs', MGD_ROOT_URL . 'js/json.js', [], $plugin_version);
                    wp_enqueue_script('mgd_adminjs', MGD_ROOT_URL . 'js/admin.js', [], $plugin_version);
                    wp_enqueue_style('mgd_styles', MGD_ROOT_URL . 'css/gitdown.css', [], $plugin_version);
                });
            }
        );

        add_action('admin_enqueue_scripts', function ($hook) {
            if ('post.php' != $hook) return;
            if (!$this->article_collection->get_by_id($_GET['post'])->_is_published) return;

            add_action('wp_print_scripts', function() {
                ?><script>alert('<?php _e('This Article was added via Gitdown and your specified repository, If you edit the article here it will be overwritten by Gitdown next time you try to update it there.') ?>')</script><?php
            });
        });


        $custom_column_head_callback = function ($columns) {
            return array_merge($columns, ['MGD_status' => 'Gitdown Status']);
        };

        $custom_column_callback = function ($column_key, $post_id) {
            if ($column_key == 'MGD_status') {
                $post_data = $this->article_collection->get_by_id($post_id);

                if ($post_data->_is_published) {
                    echo '<div class="tw-font-semibold" >✅ from Repository <br/>';
                    echo '<code>';
                    echo $post_data->remote->last_commit == $post_data->local->last_commit ?
                    'Up To Date' : 'Outdated';
                    echo '</code>';
                    echo '</div>';
                } else {
                    echo '<div class="tw-font-semibold" >❌ Not from Repository</div>';
                }
            }
        };

        $row_actions = function ( $actions, $post ) {
            $postData = $this->article_collection->get_by_id($post->ID);

            if ($postData->_is_published) {
                unset( $actions['inline hide-if-no-js'] );
            }

            return $actions;
        };

        // Add these filters for Posts and Pages
        add_filter('manage_post_posts_columns', $custom_column_head_callback);
        add_filter('manage_pages_columns', $custom_column_head_callback);

        add_action('manage_post_posts_custom_column', $custom_column_callback, 10, 2);
        add_action('manage_pages_custom_column', $custom_column_callback, 10, 2);

        add_filter('post_row_actions', $row_actions, 10, 2 );
        add_filter('page_row_actions', $row_actions, 10, 2 );


        function verify_ajax() {
            if ( !current_user_can('edit_posts') ) {
                echo json_encode(false);
                die();
            };
        }

        // Ajax Calls
        add_action("wp_ajax_get_all_articles", function () {
            verify_ajax();
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
            verify_ajax();
            echo json_encode($this->article_collection->delete_post($_REQUEST['slug']));
            die();
        });
        add_action("wp_ajax_mgd_get_outdated", function () {
            if (! (bool) get_option('mgd_cron_setting') ) return;

            echo json_encode($this->article_collection->get_outdated());

            die();
        });


        add_action('init', function() {
            if (! (bool) get_option('mgd_cron_setting') ) return;

            add_action('wp_print_scripts', function() {
                ?>
                <script>
                    console.log('MGD Autoupdate starting ...');

                    (async () => {
                        console.log('MGD Autoupdate Request ...');

                        const form_data = new FormData()
                        form_data.append('action', 'mgd_get_outdated')

                        const re = await fetch(ajaxurl, {
                            method: 'POST',
                            body: form_data,
                        })

                        try {
                            const articles = await re.json()
                            
                            console.log(articles);

                            if (articles.constructor != Array) return;

                            for (const article of articles ) {
                                console.log('MGD Autoupdate Updating:', article.remote.slug);
                                const form_data = new FormData()
                                form_data.append('action', 'update_article')
                                form_data.append('slug', article.remote.slug)

                                fetch(ajaxurl, {
                                    method: 'POST',
                                    body: form_data,
                                }).then( re => {
                                    re.json().then(result => {
                                        console.log('MGD Autoupdate Result ...', result)
                                    });
                                })

                            }
                        } catch (error) {
                            console.log(error);
                        }
                    })()
                </script>
                <?php
            });
        });

        // Add a custom API Endpoint which updates outdated articles
        add_action('rest_api_init', function () {
            register_rest_route('mgd/v1', '/update_outdated', [
                'methods' => 'GET',
                'callback' => function () {
                    $outdated = $this->article_collection->get_outdated();

                    foreach ($outdated as $key => $value) {
                        $this->article_collection->update_post($value->remote->slug);
                    }

                    echo sizeof($outdated) . ' Articles Updated';
                    foreach ($outdated as $key => $value) {
                        echo '<br/>- ' . $value->remote->slug;
                    }
                },
            ]);
        });
        // Where do i call this link
        // https://maximmaeder.com/wp-json/mgd/v1/update_outdated
    }

    public function activate() {
        // Loop over all option slugs and add them and their default values
        foreach ($this->option_slugs as $slug => $slug_options) {
            add_option($slug, $slug_options->default);
        }

        // Do this to later show the documentation
        add_option('mgd_do_activation_redirect', true);
    }

    public function deactivate()
    {
        // Deleting all Options
        foreach ($this->option_slugs as $key => $value) {
            delete_option($key);
        }

        // Delete Mirror Directory
        Helpers::delete_directory(dirname(MGD_MIRROR_PATH));

        // Delete mgd_last_updated for every post
        foreach ($this->article_collection->get_all() as $key => $value) {
            delete_post_meta($value->local->id, 'mgd_last_updated');
        }
    }
};

$b8cc4bfd_b866_4956_89db_2f0eeb671e61 = new Gitdown();