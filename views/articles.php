<div class="wrap">
    <h1>Manage Github Articles</h1>
    <p>According to the glob pattern <code><?= get_option(GTW_SETTING_GLOB) ?></code> and your set resolver function the following files could be found.</p>

    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th>Github</th>
                <th>Wordpress</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (GTW_REMOTE_ARTICLES_MERGED as $key => $postData) { ?>
                <tr>
                    <td>
                        <p class="row-title" title="Post Name"><?= $postData['name'] ?></p>
                        <p title="Post Slug"><?= $postData['slug'] ?></p>

                        <p title="description"><?= truncateString($postData['description'], 100) ?></p>
                        <pre style="white-space: pre-wrap;" title="Content Snippet"><?= truncateString($postData['raw_content'], 100) ?></pre>
                    </td>
                    <td>
                        <?php if ($postData['_is_published']) : ?>

                            <div class="row-title">Is on Wordpress</div>
                            <br/>
                            <div>ID: <code><?= $postData['_local_post_data']->ID ?></code></div>
                            <div><a target="_blank" href="<?= $postData['_local_post_data']->guid ?>">Open in new Tab</a></div>

                            <pre><?php /* echo esc_html(print_r($postData, true)); */ ?></pre>

                        <?php else : ?>

                            <div class="row-title">Is not on wordpress</div>

                        <?php endif ?>
                    </td>
                    <td>
                        <a href="<?= $baseUrl . '&action=publish&slug=' . $postData['slug'] ?>" class="button action">Publish</a>
                        <a href="" class="button action">Delete</a>
                    </td>
                </tr>

            <?php
            }
            ?>
        </tbody>
    </table>
</div>
};