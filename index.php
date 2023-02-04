<?php
/*
Plugin Name:  Github to Wordpress
Plugin URI:   https://maximmaeder.com
Description:  Use this Plugin to create, update, delete and manage articles hosted on github.
Version:      1.0
Author:       Maxim Maeder
Author URI:   https://maximmaeder.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  git-to-wordpress
Domain Path:  /languages
*/

/* http://localhost/git-to-wordpress/wordpress/wp-admin/admin.php */
/* maximmaeder */
/* fjöalsjfölasjfsjö*ç */


class GIT_TO_WORDPRESS {
    
    function __construct() {

        require_once 'includes/scripts/vendor/autoload.php';
        require_once 'includes/scripts/helpers.php';
        /* require_once 'includes/scripts/config.php'; */

        define('MIRROR_PATH', 'mirror/');
        define('PLUGIN_PREFIX', 'gtw');

        // Option Names
        define('GTW_SETTING_GLOB', PLUGIN_PREFIX.'_glob_setting');
        define('GTW_SETTING_REPO', PLUGIN_PREFIX.'_repo_setting');
        define('GTW_SETTING_RESOLVER', PLUGIN_PREFIX.'_resolver_setting');

        /* Admin Menu Slugs */
        define('GTW_ARTICLES_SLUG', PLUGIN_PREFIX.'-article-manager');

        define('GTW_ROOT_PATH', __DIR__.'/');
        define('GTW_REMOTE_ARTICLES', $this->getRemoteArticles());
        define('GTW_LOCAL_ARTICLES', $this->getLocalArticles());
        define('GTW_REMOTE_ARTICLES_MERGED', $this->mergeArticleData());

        // Activation and Deactivation Hook
        register_activation_hook(__FILE__, '__activation');
        register_deactivation_hook(__FILE__, '__deactivate');
        
        /* add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script(PLUGIN_PREFIX.'_admin', GTW_ROOT_PATH.'js/admin.js');
        }); */

        add_action('admin_init', function () {
            

            $settingsSectionSlug = PLUGIN_PREFIX.'_settings_section';
            $page = 'reading';

            register_setting($page, GTW_SETTING_GLOB);
            register_setting($page, GTW_SETTING_REPO);

            add_settings_section(
                $settingsSectionSlug,
                'Git To WordPress Settings',
                function () {
                    ?>Edit the Git to WordPress settings here.<?php
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
                       <fieldset>
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
                    'Github to Wordpress',
                    'Github to Wordpress',
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


        add_action('gtw_publish', function () {
            $remoteArticle = '';
            foreach (GTW_REMOTE_ARTICLES_MERGED as $article) {
                if ($article['slug'] == $_GET['slug']) {
                    $remoteArticle = $article;
                    break;
                }
            }

            $Parsedown = new Parsedown();
    
            /* TODO: Transpile content from markdown to HTML */
            $my_post = array(
                'post_title'    => $remoteArticle['name'],
                'post_name'    => $remoteArticle['slug'],
                'post_content'  => $Parsedown->text($remoteArticle['raw_content']),
                'post_status'   => 'publish',
            );
            
            // Insert the post into the database
            try {
                wp_insert_post( $my_post );
            } catch (\Throwable $th) {}

            ?>
            <pre style="position: fixed; left: 200px; padding: 1rem; background-color: black; z-index: 99; box-shadow: 0 0 50px 2px black;">
<?= $_GET['slug'] ?>

            <?php /* print_r($my_post); */ ?>
            <?php /* print_r($remoteArticle); */ ?>

            </pre>
            <?php
        });

        // Run a custom action if there is the `action` get parameter defined.
        if (array_key_exists('action', $_GET) && $_GET['page'] == GTW_ARTICLES_SLUG) {
            do_action('gtw_'.$_GET['action']);
            /* TODO: route back to the same page without action attribute so it does not run twice. */
            header('Location: '.$_SERVER['SCRIPT_NAME'].'?page='.$_GET['page']);
        }
    }


    function __activation () {
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
                $document = $parser->parse($fileContent, false);
                $postData = array_merge($defaultPostData, $document->getYAML() ?? []);
                $postData['raw_content'] = $document->getContent();

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

    /* TODO Potentially Remove this function */
    function getLocalArticles() {
        return get_posts();
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

};

$gtw = new GIT_TO_WORDPRESS();