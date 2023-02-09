<?php
/*
Plugin Name:  Gitdown
Author:       Maxim Maeder
Author URI:   https://maximmaeder.com
Description:  Use this Plugin to create, update, delete and manage articles hosted on a remote repository.
Version:      0.1
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  gitdown
Domain Path:  /languages
*/

/* http://localhost/git-to-wordpress/wordpress/wp-admin/admin.php */
/* maximmaeder */
/* fjöalsjfölasjfsjö*ç */


class Gitdown {
    
    function __construct() {
        require_once 'includes/scripts/vendor/autoload.php';
        require_once 'includes/scripts/helpers.php';

        // Defining all the constants

        // The Plugin prefix is used for slugs and settings names to avoid naming collisions.
        define('PLUGIN_PREFIX', 'gd');

        // The Plugin name is used sometimes when the name appears somewhere.
        define('PLUGIN_NAME', 'Gitdown');

        // Option names
        define('GTW_SETTING_GLOB', PLUGIN_PREFIX.'_glob_setting');
        define('GTW_SETTING_REPO', PLUGIN_PREFIX.'_repo_setting');
        define('GTW_SETTING_RESOLVER', PLUGIN_PREFIX.'_resolver_setting');
        
        // Where the current Repository is located depends on the repo url.
        define('MIRROR_PATH', 'mirror/'.stringToSlug(get_option(GTW_SETTING_REPO)).'/');

        // Admin Menu Slugs
        define('GTW_ARTICLES_SLUG', PLUGIN_PREFIX.'-article-manager');

        define('GTW_ROOT_PATH', __DIR__.'/');

        // Create the Directory where the files are stored in case it does not exist.
        if (!is_dir(GTW_ROOT_PATH.MIRROR_PATH)) {
            mkdir(GTW_ROOT_PATH.MIRROR_PATH, 0777, true);
        }

        define('GTW_REMOTE_ARTICLES', $this->getRemoteArticles());
        define('GTW_LOCAL_ARTICLES', $this->getLocalArticles());
        define('GTW_REMOTE_ARTICLES_MERGED', $this->mergeArticleData());

        define('GTW_REMOTE_IS_CLONED', is_dir(MIRROR_PATH.'.git'));

        // Activation and Deactivation Hook
        register_activation_hook(__FILE__, function () { $this->__activate(); });
        register_deactivation_hook(__FILE__, '__deactivate');

        add_action('admin_init', function () {
            
            $settingsSectionSlug = PLUGIN_PREFIX.'_settings_section';
            $page = 'reading';

            register_setting($page, GTW_SETTING_GLOB);
            register_setting($page, GTW_SETTING_REPO);

            add_settings_section(
                $settingsSectionSlug,
                PLUGIN_NAME.' Settings',
                function () {
                    ?>Edit the Git to <?= PLUGIN_NAME ?> settings here.<?php
                },
                $page
            );
        

            add_settings_field(
                GTW_SETTING_GLOB,
                'Glob Pattern',
                function () {
                    ?>
                        <input class="regular-text code" type="text" name="<?=GTW_SETTING_GLOB?>" value="<?=get_option(GTW_SETTING_GLOB)?>">
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
                        <input class="regular-text" type="url" name="<?=GTW_SETTING_REPO?>" value="<?=get_option(GTW_SETTING_REPO)?>">
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
                       <fieldset disabled="true">
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
                        $this->_view(GTW_ROOT_PATH.'views/articles.php');
                    },
                    plugin_dir_url(__FILE__) . 'images/icon.svg',
                    20,
                );
            }
        );

        add_action('gtw_publish', function () {$this->_publishOrUpdateArticle($_GET['slug']);});
        add_action('gtw_update', function () {$this->_publishOrUpdateArticle($_GET['slug']);});
        add_action('gtw_publish_all', function () {
            foreach (GTW_REMOTE_ARTICLES_MERGED as $article) {
                $this->_publishOrUpdateArticle($article['slug']);
            }
        });
        add_action('gtw_fetch_repository', function () {
            $out = [];
            
            chdir(MIRROR_PATH);
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

        add_action('gtw_delete', function() {
            $article = $this->getMergedArticleBySlug($_GET['slug']);

            wp_delete_post($article['_local_post_data']->ID);
        });


        add_action('init', function () {
            // Run a custom action if there is the `action` get parameter defined.
            if (array_key_exists('action', $_GET) && $_GET['page'] == GTW_ARTICLES_SLUG) {
                do_action('gtw_'.$_GET['action']);
                header('Location: '.$_SERVER['SCRIPT_NAME'].'?page='.$_GET['page']);
            }
        });
    }


    function __activate () {
        add_option(GTW_SETTING_GLOB, '**/_blog/article.md');
        add_option(GTW_SETTING_REPO, 'https://github.com/Maximinodotpy/articles.git');
    }

    function __deactivate() {
        delete_option(GTW_SETTING_GLOB);
        delete_option(GTW_SETTING_REPO);
    }

    function _view($path, $input= []) {
        $gtw_data = $input;

        require_once($path);
    }

    function getRemoteArticles() {
        $resolverFunctions = [
            'simple' => function($path) {
                $defaultPostData = [
                    'name' => $path,
                    'description' => 'Lorem ipsum dolor sit amet, consectetur ...'
                ];

                $fileContent = file_get_contents($path);

                $parser = new Mni\FrontYAML\Parser;
                $postData = [];
                $document = $parser->parse($fileContent, false);
                $postData = array_merge($defaultPostData, $document->getYAML() ?? []);
                $postData['raw_content'] = $document->getContent();
                $postData['featured_image'] = dirname($path).'/preview.png';
                
                if ( !array_key_exists( 'slug', $postData ) ) {
                    $postData['slug'] = stringToSlug($postData['name']);
                }

                return $postData;
            },
            'custom' => ''
        ];

        chdir(GTW_ROOT_PATH);
    
        $simpleGlobPath = get_option(GTW_SETTING_GLOB);
        $globPath = MIRROR_PATH . $simpleGlobPath;

        $paths = glob($globPath);

        $remotePosts = [];

        foreach ($paths as $path) {
            array_push($remotePosts, $resolverFunctions['simple']($path));
        }
    
        return $remotePosts;
    }

    function getLocalArticles() {
        return get_posts([
            'numberposts' => -1,
        ]);
    }

    function getRemoteLocalVersion($slug) {
        foreach (GTW_LOCAL_ARTICLES as $localArticle) {
            if ($localArticle->post_name == $slug) return $localArticle;
        }
        return false;
    }

    function mergeArticleData() {
        $merged = [];

        foreach (GTW_REMOTE_ARTICLES as $key => $remoteArticle) {
            $localArticle = $this->getRemoteLocalVersion($remoteArticle['slug']);

            $remoteArticle['_is_published'] = !!$localArticle;
            $remoteArticle['_local_post_data'] = $localArticle ?? [];

            array_push($merged, $remoteArticle);
        }

        return $merged;
    }

    function getMergedArticleBySlug($slug) {
        $returned_article = null;
        foreach (GTW_REMOTE_ARTICLES_MERGED as $article) {
            if ($article['slug'] == $slug) {
                $returned_article = $article;
                break;
            }
        }
        return $returned_article;
    }

    function _publishOrUpdateArticle($slug) {
        $remoteArticle = $this->getMergedArticleBySlug($slug);

        $Parsedown = new Parsedown();

        $my_post = array(
            'post_title'    => $remoteArticle['name'],
            'post_name'    => $remoteArticle['slug'],
            'post_content'  => $Parsedown->text($remoteArticle['raw_content']),
            'post_status'   => 'publish',
        );

        /* Add the ID in case it is already published */
        if ($remoteArticle['_is_published']) {
            $my_post['ID'] = $remoteArticle['_local_post_data']->ID;
        }
        
        // Insert the post into the database
        $post_id = wp_insert_post( $my_post );


        // Uploading the Image
        $imagePath = GTW_ROOT_PATH.$remoteArticle['featured_image'];

        if (!is_file($imagePath)) return;

        $uploadPath = wp_upload_dir()['path'].'/'.$my_post['post_name'].'.png';

        copy($imagePath, $uploadPath);

        $thumbnailId = get_post_thumbnail_id($post_id);
    
        $attachment_data = array(
            'ID' => $thumbnailId,
            'post_mime_type' => wp_check_filetype( $uploadPath, null )['type'],
            'post_title' => sanitize_file_name( $uploadPath ),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment_data, $uploadPath, $post_id );

        set_post_thumbnail($post_id, $attach_id);
    }


    function _outpour($info) {
        echo '<pre style="position: absolute; right: 200px; z-index: 100; background-color: black; padding: 1rem; white-space: pre-wrap; width: 500px; height: 300px; overflow-y: auto;">';
        echo esc_html(print_r($info, true));
        echo '</pre>';
    }
};

$gtw = new Gitdown();