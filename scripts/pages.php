<?php

/* Creating the Admin Pages */

function PageArticles() {
    ?>
    <div class="wrap">        
        <h1>Manage Github Articles</h1>
        <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Ullam rerum nisi, voluptas modi sequi voluptatem dicta, quas eos, quia quos cupiditate. Enim reprehenderit neque asperiores consequatur, eveniet dicta pariatur quasi!</p>

        <table>
            <thead>
                <tr>
                    <th>flaskj</th>
                    <th>flaskj</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>fsd</td>
                    <td>fsd</td>
                </tr>
            </tbody>
        </table>

        <pre>
<?php
echo plugins_url();

/* echo plugin_dir_path(); */
/* echo plugin_basename(); */
echo '<br>';
echo WP_PLUGIN_DIR;
?>
        </pre>
    </div>
    <?php
};

function PageSettings() {
    ?>
            
    <h1>Resolve Settings</h1>
    
    <form action="" method="post">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="">Repository URL</label>
                    </th>
                    <td>
                        <input type="text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Glob Pattern</label>
                    </th>
                    <td>
                        <input type="text" value="**/_blog/articles.md">
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button('Save') ?>
    </form>

    <?php
};

add_action( 'admin_menu', 'options_menu' );

function options_menu() {
    add_menu_page(
        'Github to Wordpress',
        'Github to Wordpress',
        'manage_options',
        'git-to-wordpress-article-manager',
        'PageArticles',
        plugin_dir_url(__FILE__) . 'images/icon.svg',
        20
    );

    add_submenu_page(
        'git-to-wordpress-article-manager',
        'Resolve Settings',
		'Resolve Settings',
		'manage_options',
		'resolve-settings',
		'PageSettings',
    );
}

?>