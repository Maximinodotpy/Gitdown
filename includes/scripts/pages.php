<?php

/* Creating the Admin Pages */

function PageArticles()
{
    chdir(GTW_ROOT_PATH);
    
    $simpleGlobPath = get_option(GTW_SETTING_GLOB);
    $globPath = MIRROR_PATH . $simpleGlobPath;
    
    ?>
    <div class="wrap">
        <h1>Manage Github Articles</h1>
        <p>According to the glob pattern <code><?= $simpleGlobPath ?></code> and your set resolver function the following files could be found.</p>
        
        
        <?php
        
        if (is_dir(MIRROR_PATH.'.git')) {
            echo 'There is a .git file'; 
            $out = [];
            chdir(MIRROR_PATH);
            exec('git remote update', $out);
            exec('git status -uno', $out);
            exec('git pull', $out);
            exec('git log -1 --format=%cd', $out);
            chdir(GTW_ROOT_PATH);
            
            echo '<pre>';
            print_r($out);
            echo '</pre>';
        } else {
            chdir(MIRROR_PATH);
            echo 'There is not';
            exec('git clone '.get_option(GTW_SETTING_REPO).' .');
            chdir(GTW_ROOT_PATH);
        }
        ?>

        <a href="" class="button">Fetch Repository</a>

        <br>
        <br>

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
                        $defaultPostData = [
                            'name' => $path,
                            'description' => 'Lorem ipsum dolor sit amet, consectetur ...'
                        ];

                        $fileContent = file_get_contents($path);

                        /* $Parsedown = new Parsedown();

                        $body = $Parsedown->text($fileContent); */

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

                $paths = glob($globPath);
                $githubPosts = [];

                foreach ($paths as $path) {
                    array_push($githubPosts, $resolverFunctions['simple']($path));
                }


                foreach ($githubPosts as $key => $value) {

                ?>
                    <tr>
                        <td>
                            <p class="row-title" title="Post Name"><?= $value['name'] ?></p>
                            <p title="Post Slug"><?= $value['slug'] ?></p>

                            <p title="description"><?= truncateString($value['description'], 100) ?></p>
                            <pre style="white-space: pre-wrap;" title="Content Snippet"><?= truncateString($value['raw_content'], 100) ?></pre>
                        </td>
                        <td>Not Published / outdated / up-to-date</td>
                        <td>
                            <a href="" class="button action">Publish</a>
                            <a href="" class="button action">Update</a>
                            <a href="" class="button action">Delete</a>
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