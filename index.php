<?php
/*
Plugin Name:  Gitdown
Author:       Maxim Maeder
Author URI:   https://maximmaeder.com
Plugin URI:   https://github.com/Maximinodotpy/Gitdown
Description:  Use this Plugin to create, update, delete and manage markdown articles hosted on a remote repository.
Version:      0.2
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  gitdown
Domain Path:  /languages
*/

class Gitdown
{
    private $articleCollection;
    private $logger;
    private $newURL;
    
    public function __construct() {
        require_once 'php/vendor/autoload.php';
        require_once 'php/helpers.php';
        require_once 'php/ArticleCollection.php';
        require_once 'php/Logger.php';

        // The Root path of this Plugin Directory
        define('GD_ROOT_PATH', __DIR__.'/');
        define('GD_ROOT_URL', plugins_url('', __FILE__ ).'/');

        // The Plugin name is used sometimes when the name appears somewhere.
        define('GD_PLUGIN_NAME', 'Gitdown');

        // The Plugin prefix is used for slugs and settings names to avoid naming collisions.
        define('GD_PLUGIN_PREFIX', 'gd');

        // Option names
        define('GD_SETTING_GLOB', GD_PLUGIN_PREFIX.'_glob_setting');
        define('GD_SETTING_REPO', GD_PLUGIN_PREFIX.'_repo_setting');
        define('GD_SETTING_DEBUG', GD_PLUGIN_PREFIX.'_debug_setting');
        define('GD_SETTING_RESOLVER', GD_PLUGIN_PREFIX.'_resolver_setting');

        // Admin Menu Slugs
        define('GD_ARTICLES_SLUG', GD_PLUGIN_PREFIX.'-article-manager');
        define('GD_SETTINGS_SECTION',  GD_PLUGIN_PREFIX.'-settings-section');
        define('GD_SETTINGS_PAGE',  'reading');
        
        // Where the current Repository is located depends on the repo url.
        define('GD_MIRROR_PATH', WP_CONTENT_DIR.'/'.GD_PLUGIN_PREFIX.'_mirror/'.gd_stringToSlug(get_option(GD_SETTING_REPO)).'/');
        
        define('GD_REMOTE_IS_CLONED', is_dir(GD_MIRROR_PATH.'.git'));

        // Key names for each article object later on.
        define('GD_REMOTE_KEY', 'remote');
        define('GD_LOCAL_KEY', 'local');

        // Debug Mode
        define('GD_DEBUG', boolval(get_option(GD_SETTING_DEBUG)));


        // Logging
        $logLocation = GD_ROOT_PATH.'logs/log-'.date("d-m-y_h-i-s").'.json';
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

        $this->articleCollection = new GD_ArticleCollection();
        
        $resolverFunctions = [
            'simple' => function($path) {
                if (!file_exists($path)) return;

                $fileContent = file_get_contents($path);

                $parser = new Mni\FrontYAML\Parser;
                $postData = [];
                $document = $parser->parse($fileContent, false);
                
                $postData = $document->getYAML() ?? [];

                $postData['raw_content'] = $document->getContent();
                $postData['featured_image'] = dirname($path).'/preview.png';
                
                if ( !array_key_exists( 'slug', $postData ) ) {
                    $postData['slug'] = gd_stringToSlug($postData['name']);
                }

                return $postData;
            },
            'custom' => ''
        ];

        $this->articleCollection->parseDirectory(GD_MIRROR_PATH, get_option(GD_SETTING_GLOB), $resolverFunctions['simple']);
        $this->logger->info('Populating Article Collection', 'Count: '.count($this->articleCollection->get_all()));

        // Setting up the Action Hooks
        $this->setupActions();
        $this->setupCustomAction();
        $this->logger->info('Set up Action Hooks');
    }
    
    /**
     * Setup admin actions and hooks.
     */
    private function setupActions() {
        // Activation and Deactivation Hook
        register_activation_hook(__FILE__, function () { $this->activate(); });
        register_deactivation_hook(__FILE__, function () { $this->deactivate(); });
    
        add_action('admin_init', function () {
    
            register_setting(GD_SETTINGS_PAGE, GD_SETTING_GLOB);
            register_setting(GD_SETTINGS_PAGE, GD_SETTING_REPO);
            register_setting(GD_SETTINGS_PAGE, GD_SETTING_DEBUG);
    
            add_settings_section(
                GD_SETTINGS_SECTION,
                GD_PLUGIN_NAME.' Settings',
                function () {$this->view(GD_ROOT_PATH.'views/settings_head.php');},
                GD_SETTINGS_PAGE
            );
        
    
            add_settings_field(
                GD_SETTING_GLOB,
                'Glob Pattern',
                function () {$this->view(GD_ROOT_PATH.'views/settings_glob.php');},
                GD_SETTINGS_PAGE,
                GD_SETTINGS_SECTION
            );
            
            add_settings_field(
                GD_SETTING_REPO,
                'Repository Location',
                function () {$this->view(GD_ROOT_PATH.'views/settings_repo.php');},
                GD_SETTINGS_PAGE,
                GD_SETTINGS_SECTION
            );
            
            add_settings_field(
                GD_SETTING_DEBUG,
                'Debug Mode',
                function () {$this->view(GD_ROOT_PATH.'views/settings_debug.php');},
                GD_SETTINGS_PAGE,
                GD_SETTINGS_SECTION
            );

            add_settings_field(
                GD_SETTING_RESOLVER,
                'Resolver',
                function () {$this->view(GD_ROOT_PATH.'views/settings_resolver.php');},
                GD_SETTINGS_PAGE,
                GD_SETTINGS_SECTION
            );
        });
    
        // Adding the Admin Menu
        add_action('admin_menu',
            function ()
            {
                add_menu_page(
                    GD_PLUGIN_NAME,
                    GD_PLUGIN_NAME,
                    'manage_options',
                    GD_ARTICLES_SLUG,
                    function () {
                        wp_enqueue_style( GD_PLUGIN_PREFIX.'_styles', GD_ROOT_URL.'css/gitdown.css' );

                        $this->view(GD_ROOT_PATH.'views/articles.php', ['articles'=>$this->articleCollection->get_all()]);
                    },
                    'data:image/svg+xml;base64,'.base64_encode(file_get_contents(GD_ROOT_PATH.'images/icon.svg')),
                    20,
                );
            }
        );

        // Custom Column for Post List
        add_filter('manage_post_posts_columns', function($columns) {
            return array_merge($columns, ['gitdown' => 'Gitdown']);
        });

        add_action('manage_post_posts_custom_column', function($column_key, $post_id) {
            if ($column_key != 'gitdown') return;

            $postData = $this->articleCollection->get_by_id($post_id);

            if (!$postData['_is_published']) return;
            
            $symbol = count($_GET) == 0 ? '?' : '&';

            ?>
                <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . $symbol. 'gd_action=update&gd_slug=' . $postData[GD_REMOTE_KEY]['slug']) ?>" class="button">Update</a>
            <?php

        }, 10, 2);

        // Custom Action for Post List Bulk Actions
        add_filter('bulk_actions-edit-post', function($bulk_actions) {
            $bulk_actions['gd_update'] = 'Gitdown: Update';
            return $bulk_actions;
        });

        add_filter('handle_bulk_actions-edit-post', function($redirect_url, $action, $post_ids) {
            if ($action == 'gd_update') {

                $count = 0;

                foreach ($post_ids as $post_id) { 
                    $postData = $this->articleCollection->get_by_id($post_id);

                    if ($postData['_is_published']) {
                        $count++;
                        $this->publishOrUpdateArticle($postData[GD_REMOTE_KEY]['slug']);
                    };

                }
                
                $redirect_url = add_query_arg('gd_notice', 'Gitdown: Updated '.$count.' Posts', $redirect_url);
            }
            return $redirect_url;
        }, 10, 3);


        add_action('admin_notices', function() {
            if (!empty($_REQUEST['gd_notice'])) {
                $notification_text = $_REQUEST['gd_notice'];

                echo '<div id="message" class="updated notice is-dismissable"><p>' . esc_html($notification_text) . '</p></div>';
            }
        });
    }

    private function setupCustomAction() {
        $possible_actions = [
            'publish', 'delete', 'fetch_repository', 'publish_all', 'delete_all', 'update'
        ];


        // Custom Actions

        // Publishing and Updating
        add_action(GD_PLUGIN_PREFIX.'_publish', function () {$this->publishOrUpdateArticle($_GET['gd_slug']);});
        add_action(GD_PLUGIN_PREFIX.'_update', function () {$this->publishOrUpdateArticle($_GET['gd_slug']);});

        add_action(GD_PLUGIN_PREFIX.'_publish_all', function () {
            foreach (array_reverse($this->articleCollection->get_all()) as $article) {
                $this->publishOrUpdateArticle($article[GD_REMOTE_KEY]['slug']);
            }
        });

        // Fetching the Repository
        add_action(GD_PLUGIN_PREFIX.'_fetch_repository', function () {
            $out = [];
            
            chdir(GD_MIRROR_PATH);
            
            if (!GD_REMOTE_IS_CLONED) {
                exec('git clone '.get_option(GD_SETTING_REPO).' .', $out);
            } else {
                exec('git pull', $out);
    
                $remoteLink = '';
                exec('git remote get-url origin', $remoteLink);
    
                if ($remoteLink[0] != get_option(GD_SETTING_REPO)) {}
            }
        });

        // Deleting a post
        add_action(GD_PLUGIN_PREFIX.'_delete', function() {
            $this->deleteArticle($_GET['gd_slug']);
        });
        add_action(GD_PLUGIN_PREFIX.'_delete_all', function () {
            foreach ($this->articleCollection->get_all() as $article) {
                $this->deleteArticle($article[GD_REMOTE_KEY]['slug']);
            }
        });
        

        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script('jföasldkjföalskjd', GD_ROOT_URL.'js/admin.js');
            wp_add_inline_script(
                "jföasldkjföalskjd",
                "const PHPVARS = " . json_encode(array(
                    "ajaxurl" => admin_url("admin-ajax.php"),
                    "nonce" => wp_create_nonce(),
                )),
                "before"
              );
        });

        add_action("wp_ajax_nopriv_get_time", "ajax_get_time");
        add_action("wp_ajax_get_time", "ajax_get_time");
        
        function ajax_get_time() {
            $time = date("d.m.Y H:i:s");
            $id = uniqid();
            $result = array(
                "time" => $time,
                "focking" => 'fasdlkfjaösdlk',
                "id" => $id
            );
            echo json_encode($result);
            die();
        }

        // Run a custom action if there is the `action` get parameter defined.
        add_action('init', function () use ($possible_actions) {
            
            if (!array_key_exists('gd_action', $_GET)) return;
            if (!in_array($_GET['gd_action'], $possible_actions)) return;
            
            $this->newURL = esc_url($_SERVER['REQUEST_URI']);
            $this->newURL = remove_query_arg('gd_action', $this->newURL);
            $this->newURL = remove_query_arg('gd_slug', $this->newURL);
            $this->newURL = remove_query_arg('gd_notice', $this->newURL);
            
            // Run the Given Action
            $customActionName = GD_PLUGIN_PREFIX.'_'.$_GET['gd_action'];
            do_action($customActionName);

            if (GD_DEBUG) return;

            wp_redirect(sanitize_url($this->newURL));
            exit;
        });
    }

    public function activate () {
        add_option(GD_SETTING_GLOB, '**/_blog/article.md');
        add_option(GD_SETTING_REPO, 'https://github.com/Maximinodotpy/articles.git');
        add_option(GD_SETTING_DEBUG, '0');
    }

    public function deactivate() {
        delete_option(GD_SETTING_GLOB);
        delete_option(GD_SETTING_REPO);
        delete_option(GD_SETTING_DEBUG);
    }

    private function view($path, $input= []) {
        $gtw_data = $input;

        include($path);
    }

    /**
     * Publish or Update article by slug
     * 
     * @param string $slug Slug of the article matched in remote.
     */
    private function publishOrUpdateArticle($slug) {
        $this->logger->info('Updating Post ...');
        
        $post_data = $this->articleCollection->get_by_slug($slug);

        $Parsedown = new Parsedown();

        $post_status = $post_data[GD_REMOTE_KEY]['status'] ?? 'publish';

        $category_id = 0;
        if (!get_category_by_slug($post_data[GD_REMOTE_KEY]['category'])) {
            $category_id = wp_insert_term($post_data[GD_REMOTE_KEY]['category'], 'category')['term_id'];
        } else {
            $category_id = get_category_by_slug($post_data[GD_REMOTE_KEY]['category'])->term_id;
        }

        $new_post_data = array(
            'post_title'    => $post_data[GD_REMOTE_KEY]['name'],
            'post_name'    => $post_data[GD_REMOTE_KEY]['slug'],
            'post_excerpt' => $post_data[GD_REMOTE_KEY]['description'],
            'post_content'  => wp_kses_post($Parsedown->text($post_data[GD_REMOTE_KEY]['raw_content'])),
            'post_status'   => $post_status,
            'post_category' => [$category_id],
        );

        /* Add the ID in case it is already published */
        if ($post_data['_is_published']) {
            $new_post_data['ID'] = $post_data[GD_LOCAL_KEY]['ID'];
        }
        
        // Insert the post into the database
        $post_id = wp_insert_post( $new_post_data );
        
        // Uploading the Image
        $imagePath = GD_MIRROR_PATH.$post_data[GD_REMOTE_KEY]['featured_image'];

        if (!is_file($imagePath)) return;

        $uploadPath = wp_upload_dir()['path'].'/'.$new_post_data['post_name'].'.png';

        copy($imagePath, $uploadPath);

        $thumbnailId = get_post_thumbnail_id($post_id);
    
        $attachment_data = array(
            'ID' => $thumbnailId,
            'post_mime_type' => wp_check_filetype( $uploadPath, null )['type'],
            'post_title' => $new_post_data['post_title'],
            'post_content' => '',
            'post_status' => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment_data, $uploadPath, $post_id );
        set_post_thumbnail($post_id, $attach_id);

        // Using the WP Cli to regenerate the image sizes.
        $out = [];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {           
            $command = GD_ROOT_PATH.'php/vendor/wp-cli/wp-cli/bin/wp media regenerate '.$attach_id.' --only-missing > nul';
        } else {
            $command = GD_ROOT_PATH.'php/vendor/wp-cli/wp-cli/bin/wp media regenerate '.$attach_id.' --only-missing > /dev/null &';
        }

        exec($command, $out);

        $this->newURL = add_query_arg('gd_notice', 'Updated '.$post_data[GD_REMOTE_KEY]['name'].'.', $this->newURL);

        $this->logger->info('Post Updated');
    }

    private function deleteArticle($slug) {
        $article = $this->articleCollection->get_by_slug($slug);

        if (!$article['_is_published']) return;

        $post_id = $article[GD_LOCAL_KEY]['ID'];

        // Remove Thumbnail Image
        wp_delete_attachment(get_post_thumbnail_id($post_id));
        
        // Remove the Post itself
        $result = wp_delete_post($post_id, true);
    
        $this->logger->info('Post Deleted', 'fölaksjdf');

        $this->newURL = add_query_arg('gd_notice', 'Deleted "'.$article[GD_REMOTE_KEY]['name'].'".', $this->newURL);
    }

    private function outpour($info) {
        echo '<pre style="position: absolute; right: 200px; z-index: 100; background-color: grey; padding: 1rem; white-space: pre-wrap; width: 500px; height: 300px; overflow-y: auto;">';
        echo esc_html(print_r($info, true));
        echo '</pre>';
    }
};


if(is_admin()) {
    $gtw = new Gitdown();
}