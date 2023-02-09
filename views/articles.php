<div class="wrap">
    <h1>Manage Git Articles</h1>

    <?php if(GTW_REMOTE_IS_CLONED) : ?>
    
        <p>According to the glob pattern <code><?= get_option(GTW_SETTING_GLOB) ?></code> and your set resolver function the following files could be found.</p>
        
        <a href="<?= $_SERVER['REQUEST_URI'] . '&action=publish_all' ?>" class="button">Update / Publish All</a>
        
    <?php else : ?>
            
        <p>Lets start by fetching/cloning the Repo at <code><?= get_option(GTW_SETTING_REPO) ?></code></p>
            
    <?php endif ?>
            
    <a href="<?= $_SERVER['REQUEST_URI'] . '&action=fetch_repository'?>" class="button">Fetch Repo</a>
    
    <br>
    <br>

    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th>Remote</th>
                <th>Wordpress</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_reverse(GTW_REMOTE_ARTICLES_MERGED) as $key => $postData) { ?>
                <tr>
                    <td>
                        <p class="row-title" title="Post Name"><?= $postData['name'] ?></p>
                        <p title="Post Slug"><?= $postData['slug'] ?></p>

                        <p title="description"><?= truncateString($postData['description'], 100) ?></p>
                        <pre style="white-space: pre-wrap;" title="Content Snippet"><?= truncateString($postData['raw_content'], 100) ?></pre>

                        <p alt=""><?= ''.$postData['featured_image'] ?></p>

                    </td>
                    <td>
                        <?php if ($postData['_is_published']) : ?>

                            <div class="row-title">Is on Wordpress</div>
                            <br/>
                            <div>ID: <code><?= $postData['_local_post_data']->ID ?></code></div>
                            <div><a target="_blank" href="<?= $postData['_local_post_data']->guid ?>">Open in new Tab</a></div>

                            <pre><?php /* echo esc_html(print_r($postData, true)); */ ?></pre>

                        <?php else : ?>

                            <div class="row-title">NOT</div>

                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($postData['_is_published']) : ?>

                            <a href="<?= $_SERVER['REQUEST_URI'] . '&action=update&slug=' . $postData['slug'] ?>" class="button action">Update</a>
                            <a href="<?= $_SERVER['REQUEST_URI'] . '&action=delete&slug=' . $postData['slug'] ?>" class="button action">Delete</a>

                        <?php else : ?>

                            <a href="<?= $_SERVER['REQUEST_URI'] . '&action=publish&slug=' . $postData['slug'] ?>" class="button action">Publish</a>

                        <?php endif ?>
                    </td>
                </tr>

            <?php
            }
            ?>
        </tbody>
    </table>
</div>