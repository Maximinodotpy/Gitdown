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
    
    public function __construct() {
        require_once 'includes/scripts/vendor/autoload.php';
        require_once 'includes/scripts/helpers.php';
        require_once 'includes/scripts/ArticleCollection.php';

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
        define('GTW_SETTING_RESOLVER', PLUGIN_PREFIX.'_resolver_setting');
        
        // Admin Menu Slugs
        define('GTW_ARTICLES_SLUG', PLUGIN_PREFIX.'-article-manager');
        
        // Where the current Repository is located depends on the repo url.
        define('MIRROR_ABS_PATH', WP_CONTENT_DIR.'/'.PLUGIN_PREFIX.'_mirror/'.stringToSlug(get_option(GTW_SETTING_REPO)).'/');
        define('TEMP_ARTICLE_DATA_ABS_PATH', GTW_ROOT_PATH.'tempdata.json');

        define('GTW_REMOTE_IS_CLONED', is_dir(MIRROR_ABS_PATH.'.git'));

        // Key names for each article object later on.
        define('GTW_REMOTE_KEY', 'remote');
        define('GTW_LOCAL_KEY', 'local');

        // Create the Directory where the files are stored in case it does not exist.
        if (!is_dir(MIRROR_ABS_PATH)) {
            mkdir(MIRROR_ABS_PATH, 0777, true);
        }

        $this->articleCollection = new GTWArticleCollection();
        if (file_exists(TEMP_ARTICLE_DATA_ABS_PATH) && false) {
            $this->articleCollection->set_all(json_decode(file_get_contents(TEMP_ARTICLE_DATA_ABS_PATH), true));
        } else {
            $this->refreshTempData();
        }

        $this->setupActions();
        $this->setupCustomAction();
    }
    
    /**
     * Setup admin actions and hooks.
     */
    private function setupActions() {
        // Activation and Deactivation Hook
        register_activation_hook(__FILE__, function () { $this->activate(); });
        register_deactivation_hook(__FILE__, 'deactivate');
    
        add_action('admin_init', function () {
            
            $settingsSectionSlug = PLUGIN_PREFIX.'_settings_section';
            $page = 'reading';
    
            register_setting($page, GTW_SETTING_GLOB);
            register_setting($page, GTW_SETTING_REPO);
    
            add_settings_section(
                $settingsSectionSlug,
                PLUGIN_NAME.' Settings',
                function () {
                    $this->view(GTW_ROOT_PATH.'views/settings_head.php');
                },
                $page
            );
        
    
            add_settings_field(
                GTW_SETTING_GLOB,
                'Glob Pattern',
                function () {
                    ?>
                        <input class="regular-text code" type="text" name="<?php echo GTW_SETTING_GLOB?>" value="<?php echo get_option(GTW_SETTING_GLOB)?>">
                        <p class="description">Where are the markdown files that are your articles located? Use a php <a target="_blank" href="https://www.php.net/manual/de/function.glob.php">glob pattern</a> to search for files.</p>
                    <?php
                },
                $page,
                $settingsSectionSlug
            );
    
            add_settings_field(
                GTW_SETTING_REPO,
                'Repository Location',
                function () {
                    ?>
                        <input class="regular-text" type="url" name="<?php echo GTW_SETTING_REPO?>" value="<?php echo get_option(GTW_SETTING_REPO)?>">
                        <p class="description">Where is the <code>.git</code> file of your repository located? example: <code>https://github.com/Maximinodotpy/articles.git</code></p>
                    <?php
                },
                $page,
                $settingsSectionSlug
            );
            
            add_settings_field(
                GTW_SETTING_RESOLVER,
                'Resolver',
                function () {
                    ?>
                       <fieldset disabled="true" style="opacity: 0.5">
                            <label for="resolver_simple">
                                <input type="radio" name="" id="resolver_simple" value="simple">
                                <span>Simple</span>
                                <p class="description">The simple resolver will take the Markdown Meta Info and use it to create / update the posts.</p>
                            </label>
                            <br>
    
                            <label for="resolver_custom">
                                <input type="radio" name="" id="resolver_custom" value="simple">
                                <span>Custom</span>
                                <p class="description">This custom resolver function should return an associative array with the following members: </p>
                                <br>
    
                                <textarea name="" id="" cols="30" rows="10" style="width: 100%"></textarea>
                            </label>
                            <br>
                       </fieldset>
                    <?php
                },
                $page,
                $settingsSectionSlug
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

                        $this->view(GTW_ROOT_PATH.'views/articles.php', $this->articleCollection->get_all());
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

        // Custom Actions

        // Publishing and Updating
        add_action(PLUGIN_PREFIX.'_publish', function () {$this->publishOrUpdateArticle($_GET['slug']);});
        add_action(PLUGIN_PREFIX.'_update', function () {$this->publishOrUpdateArticle($_GET['slug']);});

        add_action(PLUGIN_PREFIX.'_publish_all', function () {
            foreach (array_reverse($this->articleCollection->get_all()) as $article) {
                $this->publishOrUpdateArticle($article['slug']);
            }
        });

        // Fetching the Repository
        add_action(PLUGIN_PREFIX.'_fetch_repository', function () {
            $out = [];
            
            chdir(MIRROR_ABS_PATH);
            /* TODO: Delete Contents of mirror and re clone `git remote get-url origin` */
            if (!GTW_REMOTE_IS_CLONED) {
                exec('git clone '.get_option(GTW_SETTING_REPO).' .', $out);
            } else {
                exec('git pull', $out);
    
                $remoteLink = '';
                exec('git remote get-url origin', $remoteLink);
    
                if ($remoteLink[0] != get_option(GTW_SETTING_REPO)) {
                    // Remove all files and folders from Mirror
    
                    // TODO: Remove files from mirror and clone the new Repository
                }
            }
        });
    

        // Deleting a post
        add_action(PLUGIN_PREFIX.'_delete', function() {
            $this->deleteArticle($_GET['slug']);
        });
        add_action(PLUGIN_PREFIX.'_delete_all', function () {
            foreach ($this->articleCollection->get_all() as $article) {
                $this->deleteArticle($article['slug']);
            }
        });
    
    
        // Run a custom action if there is the `action` get parameter defined.
        add_action('init', function () use ($possible_actions) {

            if (array_key_exists('action', $_GET) && $_GET['page'] == GTW_ARTICLES_SLUG) {

                if (in_array($_GET['action'], $possible_actions)) {
                    $customActionName = PLUGIN_PREFIX.'_'.$_GET['action'];
                    do_action($customActionName);
                }

                $adminArea = admin_url().'?page='.GTW_ARTICLES_SLUG;

                header('Location: '.esc_url($adminArea));
            }
        });
    }

    private function activate () {
        add_option(GTW_SETTING_GLOB, '**/_blog/article.md');
        add_option(GTW_SETTING_REPO, 'https://github.com/Maximinodotpy/articles.git');
    }

    private function deactivate() {
        delete_option(GTW_SETTING_GLOB);
        delete_option(GTW_SETTING_REPO);
    }

    private function view($path, $input= []) {
        $gtw_data = $input;

        include($path);
    }

    /**
     * Refresh the JSON Data that is temporarily stored in a file.
     */
    private function refreshTempData() {
        $resolverFunctions = [
            'simple' => function($path) {
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

        file_put_contents(TEMP_ARTICLE_DATA_ABS_PATH, json_encode($this->articleCollection->get_all(), JSON_PRETTY_PRINT));
    }

    /**
     * Publish or Update article by slug
     * 
     * @param string $slug Slug of the article matched in remote.
     */
    private function publishOrUpdateArticle($slug) {

        $post_data = $this->articleCollection->get_by_slug($slug);

        $Parsedown = new Parsedown();

        $post_status = $post_data[GTW_REMOTE_KEY]['status'] ?? 'draft';

        $category_id = 0;
        if (!get_category_by_slug($post_data[GTW_REMOTE_KEY]['category'])) {
            $category_id = wp_insert_term($post_data[GTW_REMOTE_KEY]['category'], 'category')['term_id'];
        } else {
            $category_id = get_category_by_slug($post_data[GTW_REMOTE_KEY]['category'])->term_id;
        }

        $post_data = array(
            'post_title'    => $post_data[GTW_REMOTE_KEY]['name'],
            'post_name'    => $post_data[GTW_REMOTE_KEY]['slug'],
            'post_excerpt' => $post_data[GTW_REMOTE_KEY]['description'],
            'post_content'  => wp_kses_post($Parsedown->text($post_data[GTW_REMOTE_KEY]['raw_content'])),
            'post_status'   => $post_status,
            'post_category' => [$category_id],
        );

        /* Add the ID in case it is already published */
        if ($post_data['_is_published']) {
            $post_data['ID'] = $post_data[GTW_LOCAL_KEY]['ID'];
        }
        
        // Insert the post into the database
        $post_id = wp_insert_post( $post_data );

        
        // Uploading the Image
        $imagePath = MIRROR_ABS_PATH.$post_data[GTW_REMOTE_KEY]['featured_image'];

        if (!is_file($imagePath)) return;

        $uploadPath = wp_upload_dir()['path'].'/'.$post_data['post_name'].'.png';

        copy($imagePath, $uploadPath);

        $thumbnailId = get_post_thumbnail_id($post_id);
    
        $attachment_data = array(
            'ID' => $thumbnailId,
            'post_mime_type' => wp_check_filetype( $uploadPath, null )['type'],
            'post_title' => $post_data['post_title'],
            'post_content' => '',
            'post_status' => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment_data, $uploadPath, $post_id );
        set_post_thumbnail($post_id, $attach_id);

        /* if (function_exists('wp_create_image_subsizes')) {
            wp_create_image_subsizes($uploadPath, $attach_id);
        } */

        $this->refreshTempData();
    }

    private function deleteArticle($slug) {
        $article = $this->articleCollection->get_by_slug($slug);

        $post_id = $article[GTW_LOCAL_KEY]['ID'];

        // Remove Thumbnail Image
        wp_delete_attachment(get_post_thumbnail_id($post_id));
        
        // Remove the Post itself
        wp_delete_post($post_id, true);

        $this->refreshTempData();
    }

    private function outpour($info) {
        echo '<pre style="position: absolute; right: 200px; z-index: 100; background-color: black; padding: 1rem; white-space: pre-wrap; width: 500px; height: 300px; overflow-y: auto;">';
        echo esc_html(print_r($info, true));
        echo '</pre>';
    }
};


if(is_admin()) {
    $gtw = new Gitdown();
}