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
        include 'includes/scripts/config.php';
        include 'includes/scripts/pages.php';

        // Activation Hook
        register_activation_hook(
            __FILE__,
            function() {}
        );
        
        add_action('admin_init', function () {
            $settingsSectionSlug = PLUGIN_PREFIX.'_settings_section';
            $page = 'reading';

            register_setting($page, 'test_setting');

            add_settings_section(
                $settingsSectionSlug,
                'Git To WordPress Settings',
                function () {
                    ?>fasdf<?php
                },
                $page
            );
        
            // register a new field in the "wporg_settings_section" section, inside the "reading" page
            add_settings_field(
                'glob_pattern',
                'Glob Pattern',
                function () {
                    ?>
                    <input type="text" name="test_setting" value="<?=get_option('test_setting')?>">
                    <p class="description">blalblalblal</p>
                    <?php
                },
                $page,
                $settingsSectionSlug
            );
        });
        
        /* register_uninstall_hook(
            __FILE__,
            function() {
        
            }
        ); */
    }

    
};

$gtw = new GIT_TO_WORDPRESS();

// Deactivation Hook
/* register_deactivation_hook(
    __FILE__,
    function() {

    }
); */