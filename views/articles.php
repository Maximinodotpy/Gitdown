<div class="wrap">
    <h1>Manage Git Articles</h1>

    <?php if(GTW_REMOTE_IS_CLONED) : ?>
    
        <p>According to the glob pattern <code><?php echo get_option(GTW_SETTING_GLOB) ?></code> and your set resolver function the following files could be found.</p>

        <p>Keep in mind that all articles are identified by their <code>slug</code>/<code>post_name</code>. Thats why it is shown here in the Github column. If you change the slug in the markdown file, Gitdown wont recognize that the articles belong together.</p>
        
        <pre>
            <?php echo esc_attr( 'fasöldkjfaölsdFASDFA-%*ç' ) ?>
        </pre>

        <a href="<?php echo esc_url($_SERVER['REQUEST_URI'].'&action=publish_all') ?>" class="button button-primary">Update / Publish All</a>
        <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&action=delete_all') ?>" class="button">Delete All</a>
        
    <?php else : ?>
            
        <p>Lets start by fetching/cloning the Repo at <code><?php echo esc_url(get_option(GTW_SETTING_REPO)) ?></code></p>
            
    <?php endif ?>
            
    <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&action=fetch_repository') ?>" class="button">Fetch Repo</a>

    <a href="<?php echo esc_url(dirname(plugin_dir_url(__FILE__), 1).'/files/example.zip') ?>" download="example" class="button">Download Example Folder Structure</a>
    
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
            <?php foreach (array_reverse($gtw_data) as $key => $postData) { ?>
                <tr>
                    <td>
                        <p class="row-title" title="Post Name"><?php echo esc_html($postData['name']) ?></p>
                        <p title="Post Slug"><?php echo esc_html($postData['slug']) ?></p>

                        <p title="description"><?php echo esc_html(truncateString($postData['description'], 100)) ?></p>
                        <pre style="white-space: pre-wrap;" title="Content Snippet"><?php echo esc_html(truncateString($postData['raw_content'], 100)) ?></pre>
                    </td>
                    <td>
                        <?php if ($postData['_is_published']) : ?>

                            <div class="row-title">✅ Is on Wordpress</div>
                            <br/>
                            
                            <div>ID: <code><?php echo esc_html($postData['_local_post_data']->ID) ?></code></div>
                            <div>Slug: <code><?php echo esc_html($postData['slug']) ?></code></div>
                            <div>Excerpt: <code><?php echo esc_html($postData['_local_post_data']->post_excerpt) ?></code></div>
                            <div>Status: <code><?php echo esc_html($postData['_local_post_data']->post_status) ?></code></div>

                            <br>

                            <div><a target="_blank" href="<?php echo esc_url($postData['_local_post_data']->guid) ?>">Open in new Tab</a></div>
                            <br>
                            
                            <img src="<?php echo esc_url(get_the_post_thumbnail_url($postData['_local_post_data']->ID, 'thumbnail')) ?>" alt="Thumbnail not Found" style="width: 100%; filter: grayscale(50%); opacity: 0.5">

                        <?php else : ?>

                            <div class="row-title">❌ Not on Wordpress</div>

                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($postData['_is_published']) : ?>

                            <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&action=update&slug=' . $postData['slug']) ?>" class="button action button-primary">Update</a>
                            <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&action=delete&slug=' . $postData['slug']) ?>" class="button action">Delete</a>

                        <?php else : ?>

                            <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&action=publish&slug=' . $postData['slug']) ?>" class="button action">Publish</a>

                        <?php endif ?>
                    </td>
                </tr>

            <?php
            }
            ?>
        </tbody>
    </table>
</div>