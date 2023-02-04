<?php

function PageArticles()
{
    ?>
    <div class="wrap">
        <h1>Manage Github Articles</h1>
        <p>According to the glob pattern <code><?= get_option(GTW_SETTING_GLOB) ?></code> and your set resolver function the following files could be found.</p>
        
        <?php
        /* 
        chdir(GTW_ROOT_PATH);
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
        } */
        ?>

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

                foreach (GTW_REMOTE_ARTICLES as $key => $remotePost) {

                ?>
                    <tr>
                        <td>
                            <p class="row-title" title="Post Name"><?= $remotePost['name'] ?></p>
                            <p title="Post Slug"><?= $remotePost['slug'] ?></p>

                            <p title="description"><?= truncateString($remotePost['description'], 100) ?></p>
                            <pre style="white-space: pre-wrap;" title="Content Snippet"><?= truncateString($remotePost['raw_content'], 100) ?></pre>
                        </td>
                        <td>
                            <?php
                            
                            $livePost = getPostOnWordpress($remotePost['slug']);
                            $isOnWordpress = !!$livePost;
                            $baseUrl = $_SERVER['REQUEST_URI'];

                            if ($isOnWordpress) {
                                echo '<div class="row-title">Is on Wordpress</div>';
                                echo '<br/>';
                                echo '<div>ID: '.$livePost->ID.'</div>';
                                echo '<div><a target="_blank" href="'.$livePost->guid.'">Open in new Tab</a></div>';
                            } else {
                                echo 'Not on Wordpress';
                            }
                            
                            ?>
                        </td>
                        <td>
                            <a href="<?= $baseUrl.'&action=publish&slug='.$remotePost['slug'] ?>" class="button action">Publish</a>
                            <a href="" class="button action">Delete</a>
                        </td>
                    </tr>

                <?php
                }

                ?>
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
        ?>
        </pre>
    </div>
<?php
};

?>