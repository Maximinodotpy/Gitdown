<?php
/*
Plugin Name:  Github to Wordpress
Plugin URI:   https://maximmaeder
Description:  Use this Plugin to do stuff
Version:      1.0
Author:       Maxim Maeder
Author URI:   https://maximmaeder
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  git-to-wordpress
Domain Path:  /languages
*/

/* http://localhost/git-to-wordpress/wordpress/wp-admin/admin.php */


class GIT_TO_WORDPRESS {
    
    function __construct() {
        include 'includes/scripts/vendor/autoload.php';
        include 'includes/scripts/config.php';
        include 'includes/scripts/helpers.php';
        include 'includes/scripts/pages.php';

        define('GTW_ROOT_PATH', __DIR__.'/');

        // Activation Hook
        register_activation_hook(
            __FILE__,
            function() {
                add_option(GTW_SETTING_GLOB, '**/_blog/article.md');
                add_option(GTW_SETTING_REPO, 'https://github.com/Maximinodotpy/articles.git');
            }
        );

        register_deactivation_hook(
            __FILE__,
            function() {
                delete_option(GTW_SETTING_GLOB);
                delete_option(GTW_SETTING_REPO);

                deleteFiles(MIRROR_PATH);
            },
        );
        
        add_action('admin_init', function () {
            /* add_option(GTW_SETTING_REPO, 'fasd'); */

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
    }

    
};

$gtw = new GIT_TO_WORDPRESS();