<?php

/* Creating the Admin Pages */

function PageArticles()
{

    $simpleGlobPath = '**/_blog/article.md';
    $globPath = MIRROR_PATH . $simpleGlobPath;

?>
    <div class="wrap">
        <h1>Manage Github Articles</h1>
        <p>According to the glob pattern <code><?= $simpleGlobPath ?></code> and your set resolver function the following files could be found.</p>

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
    echo 'plugins_url(): ' . plugins_url() . '<br>';
    echo 'WP_PLUGIN_URL: ' . WP_PLUGIN_URL . '<br>';
    echo 'WP_PLUGIN_URL: ' . WP_PLUGIN_URL . '<br>';
    echo '__FILE__: ' . __FILE__ . '<br>';
    echo 'MIRROR_PATH: ' . MIRROR_PATH . '<br>';
    echo 'GTW_ROOT_PATH: ' . GTW_ROOT_PATH . '<br>';
    echo 'getcwd(): ' . getcwd() . '<br>';


    /* Setting CWD */
    $temp = getcwd();

    chdir(GTW_ROOT_PATH);
    echo 'getcwd(): ' . getcwd() . '<br>';

    echo '<pre>';
    echo $globPath;
    echo '<br>';

    print_r(glob($globPath));
    echo '</pre>';
    /* chdir($temp); */
?>
        </pre>
    </div>
<?php
};

function PageSettings()
{
?>

    <h1>Resolve Settings</h1>

    <form action="<?php menu_page_url( 'git-to-wordpress-article-manager' ) ?>" method="post">

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

add_action('admin_menu', 'options_menu');

function options_menu()
{
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