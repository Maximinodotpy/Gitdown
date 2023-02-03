<?php

/* Creating the Admin Pages */

function PageArticles()
{

    $simpleGlobPath = get_option(GTW_SETTING_GLOB);
    $globPath = MIRROR_PATH . $simpleGlobPath;

?>
    <div class="wrap">
        <h1>Manage Github Articles</h1>
        <p>According to the glob pattern <code><?= $simpleGlobPath ?></code> and your set resolver function the following files could be found.</p>

        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
                <tr>
                    <th>Github</th>
                    <th>Wordpress</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php


                /* Add Resolver Function */

                $resolverFunctions = [
                    'simple' => function($path) {
                        $fileContent = file_get_contents($path);

                        /* $slug = stringToSlug('How to make XY'); */

                        return [
                            'name' => 'How to make XY',
                            'slug' => 'how-to-make-xy',
                            'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna ...',
                        ];
                    },
                    'custom' => ''
                ];

                chdir(GTW_ROOT_PATH);

                $paths = glob($globPath);
                $githubPosts = [];

                foreach ($paths as $path) {
                    array_push($githubPosts, $resolverFunctions['simple']($path));
                }


                foreach ($githubPosts as $key => $value) {

                ?>
                    <tr>
                        <td>
                            <p class="row-title"><?= $value['name'] ?></p>
                            <p><?= $value['slug'] ?></p>
                            <p><?= $value['content'] ?></p>
                        </td>
                        <td>Not Published / outdated / up-to-date</td>
                        <td>
                            <a href="">Publish</a>
                            <a href="">Update</a>
                            <a href="">Delete</a>
                        </td>
                    </tr>

                <?php
                }

                ?>
            </tbody>
        </table>

        <pre><code>
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


    echo 'getcwd(): ' . getcwd() . '<br>';

    echo '<pre>';
    echo $globPath;
    echo '<br>';

    print_r(glob($globPath));
    /* chdir($temp); */
?>
        </pre></code>
    </div>
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
}

?>