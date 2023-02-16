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
    private $startTime;
    private $timeStamps;
    
    public function __construct() {

        // Performance
        $this->timeStamps = [];
        $this->startTime = round(microtime(true) * 1000);

        $this->debugTime('Start');

        require_once 'includes/scripts/vendor/autoload.php';
        require_once 'includes/scripts/helpers.php';
        require_once 'includes/scripts/ArticleCollection.php';

        $this->debugTime('After Includes');


        // Defining all the constants

        // The Plugin prefix is used for slugs and settings names to avoid naming collisions.
        define('PLUGIN_PREFIX', 'gd');

        // The Root path of this Plugin Directory
        define('GTW_ROOT_PATH', __DIR__.'/');

        // The Plugin name is used sometimes when the name appears somewhere.
        define('PLUGIN_NAME', 'Gitdown');

        // Option names
        define('GTW_SETTING_GLOB', PLUGIN_PREFIX.'_glob_setting');
        define('GTW_SETTING_REPO', PLUGIN_PREFIX.'_repo_setting');
        define('GTW_SETTING_DEBUG', PLUGIN_PREFIX.'_debug_setting');
        define('GTW_SETTING_RESOLVER', PLUGIN_PREFIX.'_resolver_setting');
        
        // Admin Menu Slugs
        define('GTW_ARTICLES_SLUG', PLUGIN_PREFIX.'-article-manager');
        define('GTW_SETTINGS_SECTION',  PLUGIN_PREFIX.'-settings-section');
        define('GTW_SETTINGS_PAGE',  'reading');
        
        // Where the current Repository is located depends on the repo url.
        define('MIRROR_ABS_PATH', WP_CONTENT_DIR.'/'.PLUGIN_PREFIX.'_mirror/'.stringToSlug(get_option(GTW_SETTING_REPO)).'/');
        
        define('GTW_REMOTE_IS_CLONED', is_dir(MIRROR_ABS_PATH.'.git'));

        // Key names for each article object later on.
        define('GTW_REMOTE_KEY', 'remote');
        define('GTW_LOCAL_KEY', 'local');

        // Debug Mode
        define('GD_DEBUG', boolval(get_option(GTW_SETTING_DEBUG)));

        // Create the Directory where the files are stored in case it does not exist.
        if (!is_dir(MIRROR_ABS_PATH)) {
            mkdir(MIRROR_ABS_PATH, 0777, true);
        }

        $this->debugTime('After Constants');

        $this->articleCollection = new GTWArticleCollection();
        
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
                    $postData['slug'] = stringToSlug($postData['name']);
                }

                return $postData;
            },
            'custom' => ''
        ];

        $this->articleCollection->parseDirectory(MIRROR_ABS_PATH, get_option(GTW_SETTING_GLOB), $resolverFunctions['simple']);
        $this->debugTime('Populating Article Collection');

        $this->setupActions();
        $this->debugTime('Setting Up Actions');

        $this->setupCustomAction();
        $this->debugTime('Setting up Custom Actions');
    }
    
    /**
     * Setup admin actions and hooks.
     */
    private function setupActions() {
        // Activation and Deactivation Hook
        register_activation_hook(__FILE__, function () { $this->activate(); });
        register_deactivation_hook(__FILE__, function () { $this->deactivate(); });
    
        add_action('admin_init', function () {
    
            register_setting(GTW_SETTINGS_PAGE, GTW_SETTING_GLOB);
            register_setting(GTW_SETTINGS_PAGE, GTW_SETTING_REPO);
            register_setting(GTW_SETTINGS_PAGE, GTW_SETTING_DEBUG);
    
            add_settings_section(
                GTW_SETTINGS_SECTION,
                PLUGIN_NAME.' Settings',
                function () {$this->view(GTW_ROOT_PATH.'views/settings_head.php');},
                GTW_SETTINGS_PAGE
            );
        
    
            add_settings_field(
                GTW_SETTING_GLOB,
                'Glob Pattern',
                function () {$this->view(GTW_ROOT_PATH.'views/settings_glob.php');},
                GTW_SETTINGS_PAGE,
                GTW_SETTINGS_SECTION
            );
            
            add_settings_field(
                GTW_SETTING_REPO,
                'Repository Location',
                function () {$this->view(GTW_ROOT_PATH.'views/settings_repo.php');},
                GTW_SETTINGS_PAGE,
                GTW_SETTINGS_SECTION
            );
            
            add_settings_field(
                GTW_SETTING_DEBUG,
                'Debug Mode',
                function () {$this->view(GTW_ROOT_PATH.'views/settings_debug.php');},
                GTW_SETTINGS_PAGE,
                GTW_SETTINGS_SECTION
            );

            add_settings_field(
                GTW_SETTING_RESOLVER,
                'Resolver',
                function () {$this->view(GTW_ROOT_PATH.'views/settings_resolver.php');},
                GTW_SETTINGS_PAGE,
                GTW_SETTINGS_SECTION
            );
        });
    
        // Adding the Admin Menu
        add_action('admin_menu',
            function ()
            {
                add_menu_page(
                    PLUGIN_NAME,
                    PLUGIN_NAME,
                    'manage_options',
                    GTW_ARTICLES_SLUG,
                    function () {
                        wp_enqueue_style( PLUGIN_PREFIX.'_styles', '/wp-content/plugins/gitdown/css/gitdown.css' );

                        $this->view(GTW_ROOT_PATH.'views/articles.php', ['articles'=>$this->articleCollection->get_all(), 'time_stamps' => $this->timeStamps]);
                    },
                    'data:image/svg+xml;base64,'.base64_encode(file_get_contents(GTW_ROOT_PATH.'images/icon.svg')),
                    20,
                );
            }
        );
    }

    private function setupCustomAction() {
        $possible_actions = [
            'publish', 'delete', 'fetch_repository', 'publish_all', 'delete_all', 'update'
        ];

        add_filter('manage_post_posts_columns', function($columns) {
            return array_merge($columns, ['gitdown' => 'Gitdown']);
        });

        add_action('manage_post_posts_custom_column', function($column_key, $post_id) {
            if ($column_key != 'gitdown') return;

            $postData = $this->articleCollection->get_by_id($post_id);

            if (!$postData['_is_published']) return;
            
            $symbol = count($_GET) == 0 ? '?' : '&';

            ?>
                <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . $symbol. 'gd_action=update&gd_slug=' . $postData[GTW_REMOTE_KEY]['slug']) ?>" class="button">Update</a>
            <?php

        }, 10, 2);

        // Custom Actions

        // Publishing and Updating
        add_action(PLUGIN_PREFIX.'_publish', function () {$this->publishOrUpdateArticle($_GET['gd_slug']);});
        add_action(PLUGIN_PREFIX.'_update', function () {$this->publishOrUpdateArticle($_GET['gd_slug']);});

        add_action(PLUGIN_PREFIX.'_publish_all', function () {
            foreach (array_reverse($this->articleCollection->get_all()) as $article) {
                $this->publishOrUpdateArticle($article[GTW_REMOTE_KEY]['slug']);
            }
        });

        // Fetching the Repository
        add_action(PLUGIN_PREFIX.'_fetch_repository', function () {
            $out = [];
            
            chdir(MIRROR_ABS_PATH);
            
            if (!GTW_REMOTE_IS_CLONED) {
                exec('git clone '.get_option(GTW_SETTING_REPO).' .', $out);
            } else {
                exec('git pull', $out);
    
                $remoteLink = '';
                exec('git remote get-url origin', $remoteLink);
    
                if ($remoteLink[0] != get_option(GTW_SETTING_REPO)) {}
            }
        });
    

        // Deleting a post
        add_action(PLUGIN_PREFIX.'_delete', function() {
            $this->deleteArticle($_GET['gd_slug']);
        });
        add_action(PLUGIN_PREFIX.'_delete_all', function () {
            foreach ($this->articleCollection->get_all() as $article) {
                $this->deleteArticle($article[GTW_REMOTE_KEY]['slug']);
            }
        });
    
    
        // Run a custom action if there is the `action` get parameter defined.
        add_action('init', function () use ($possible_actions) {
            
            if (!array_key_exists('gd_action', $_GET)) return;
            if (!in_array($_GET['gd_action'], $possible_actions)) return;
            
            
            // Run the Given Action
            $customActionName = PLUGIN_PREFIX.'_'.$_GET['gd_action'];
            do_action($customActionName);
            
            
            // Route Back to Article Page
            $newURL = explode('?', $_SERVER['REQUEST_URI'])[0];
            $newURL .= '?';
            foreach ($_GET as $key => $value) {
                if (in_array($key, ['gd_action', 'gd_slug'])) continue;

                $newURL .= $key.'='.$value.'&';
            }

            $newURL = rtrim($newURL, '&');

            $this->outpour([sanitize_url($newURL), $newURL, esc_url($newURL)]);

            if (GD_DEBUG) return;

            wp_redirect($newURL);
            exit;
        });
    }

    public function activate () {
        add_option(GTW_SETTING_GLOB, '**/_blog/article.md');
        add_option(GTW_SETTING_REPO, 'https://github.com/Maximinodotpy/articles.git');
        add_option(GTW_SETTING_DEBUG, '0');
    }

    public function deactivate() {
        delete_option(GTW_SETTING_GLOB);
        delete_option(GTW_SETTING_REPO);
        delete_option(GTW_SETTING_DEBUG);
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
        $this->debugTime('Publishing: Start');
        
        $post_data = $this->articleCollection->get_by_slug($slug);

        $Parsedown = new Parsedown();

        $post_status = $post_data[GTW_REMOTE_KEY]['status'] ?? 'publish';

        $category_id = 0;
        if (!get_category_by_slug($post_data[GTW_REMOTE_KEY]['category'])) {
            $category_id = wp_insert_term($post_data[GTW_REMOTE_KEY]['category'], 'category')['term_id'];
        } else {
            $category_id = get_category_by_slug($post_data[GTW_REMOTE_KEY]['category'])->term_id;
        }

        $new_post_data = array(
            'post_title'    => $post_data[GTW_REMOTE_KEY]['name'],
            'post_name'    => $post_data[GTW_REMOTE_KEY]['slug'],
            'post_excerpt' => $post_data[GTW_REMOTE_KEY]['description'],
            'post_content'  => wp_kses_post($Parsedown->text($post_data[GTW_REMOTE_KEY]['raw_content'])),
            'post_status'   => $post_status,
            'post_category' => [$category_id],
        );

        /* Add the ID in case it is already published */
        if ($post_data['_is_published']) {
            $new_post_data['ID'] = $post_data[GTW_LOCAL_KEY]['ID'];
        }
        
        // Insert the post into the database
        $post_id = wp_insert_post( $new_post_data );
        $this->debugTime('Publishing: After Post inserted');
        
        // Uploading the Image
        $imagePath = MIRROR_ABS_PATH.$post_data[GTW_REMOTE_KEY]['featured_image'];

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
        $this->debugTime('Publishing: After Image inserted');

        // Using the WP Cli to regenerate the image sizes.
        $out = [];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {           
            $command = GTW_ROOT_PATH.'/includes/scripts/vendor/wp-cli/wp-cli/bin/wp media regenerate '.$attach_id.' --only-missing';
        } else {
            $command = GTW_ROOT_PATH.'/includes/scripts/vendor/wp-cli/wp-cli/bin/wp media regenerate '.$attach_id.' --only-missing > /dev/null &';
        }

        exec($command, $out);

        $this->outpour($out);

        $this->debugTime('Publishing: End');
    }

    private function deleteArticle($slug) {
        $article = $this->articleCollection->get_by_slug($slug);

        $post_id = $article[GTW_LOCAL_KEY]['ID'];

        // Remove Thumbnail Image
        wp_delete_attachment(get_post_thumbnail_id($post_id));
        
        // Remove the Post itself
        wp_delete_post($post_id, true);
    }

    private function outpour($info) {
        echo '<pre style="position: absolute; right: 200px; z-index: 100; background-color: grey; padding: 1rem; white-space: pre-wrap; width: 500px; height: 300px; overflow-y: auto;">';
        echo esc_html(print_r($info, true));
        echo '</pre>';
    }

    function debugTime($handle) {
        $this->timeStamps[$handle] = round(microtime(true) * 1000) - $this->startTime;
    }
};


if(is_admin()) {
    $gtw = new Gitdown();
}