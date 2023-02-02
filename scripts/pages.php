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

    <form action="<?php menu_page_url('git-to-wordpress-article-manager') ?>" method="post">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="">Repository URL</label>
                    </th>
                    <td>
                        <input type="text">
                        <p class="description">Where is the <code>.git</code> file of your repository located? example: <code>https://github.com/Maximinodotpy/articles.git</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Glob Pattern</label>
                    </th>
                    <td>
                        <input type="text" value="**/_blog/articles.md">
                        <p class="description">Where are the markdown files that are your articles located? Use a php <a href="https://www.php.net/manual/de/function.glob.phps">glob pattern</a> to search for files.
                            <div>
                                More Examples with descriptions

                                <table>
                                    <thead>
                                        <tr>
                                            <th>Pattern</th>
                                            <th>Explanation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>*.md</code></td>
                                            <td>Will simply match all files ending with <code>.md</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Resolver Function</label>
                    </th>
                    <td>
                        <textarea name="" id="" cols="30" rows="10"></textarea>
                        <p class="description">fasd</p>
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
        GTW_ARTICLES_SLUG,
        'PageArticles',
        plugin_dir_url(__FILE__) . 'images/icon.svg',
        20
    );

    add_submenu_page(
        GTW_ARTICLES_SLUG,
        'Resolve Settings',
        'Resolve Settings',
        'manage_options',
        GTW_SETTINGS_SLUG,
        'PageSettings',
    );
}

?>