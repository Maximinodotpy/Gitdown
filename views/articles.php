<div class="wrap gitdown_ui">
    <h1>Manage Git Articles</h1>

    <?php if(GD_REMOTE_IS_CLONED) : ?>
    
        <p>According to the glob pattern <code><?php echo get_option(GD_SETTING_GLOB) ?></code> and your set resolver function the following files could be found.</p>

        <p>Keep in mind that all articles are identified by their <code>slug</code>/<code>post_name</code>. Thats why it is shown here in the Github column. If you change the slug in the markdown file, Gitdown wont recognize that the articles belong together.</p>

        <p>Contact me if <a href="mailto:info@maximmaeder.com?subject=Gitdown: ">here</a> if you found some issues or have any questions.</p>

        <a href="<?php echo esc_url($_SERVER['REQUEST_URI'].'&gd_action=publish_all') ?>" class="button button-primary">Update / Publish All</a>
        <a href="<?php echo esc_url($_SERVER['REQUEST_URI'].'&gd_action=delete_all') ?>" class="button">Delete All</a>
        
    <?php else : ?>
            
        <p>Lets start by fetching/cloning the Repo at <code><?php echo esc_url(get_option(GD_SETTING_REPO)) ?></code></p>
            
    <?php endif ?>
            
    <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=fetch_repository') ?>" class="button">Fetch Repo</a>

    <a href="<?php echo esc_url(dirname(plugin_dir_url(__FILE__), 1).'/files/example.zip') ?>" download="example" class="button">Download Example Folder Structure</a>
    
    <br>
    <br>

    <?php if (GD_DEBUG) : ?>
        <details open>
            <summary>Debug</summary>
            
            <pre style="white-space: pre-wrap;"><?php gd_dumpJSON(json_decode(json_encode([
                'os' => PHP_OS,
                'GD_REMOTE_IS_CLONED' => GD_REMOTE_IS_CLONED,
            ])));?></pre>
        </details>
    <?php endif; ?>

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
            <?php foreach (array_reverse($gtw_data['articles']) as $key => $postData) { ?>
                <tr>
                    <td>
                        <p class="row-title" title="Post Name"><?php echo esc_html($postData[GD_REMOTE_KEY]['name']) ?></p>
                        <p title="Post Slug"><?php echo esc_html($postData[GD_REMOTE_KEY]['slug']) ?></p>

                        <p title="description"><?php echo esc_html(gd_truncateString($postData[GD_REMOTE_KEY]['description'], 100)) ?></p>
                        <p title="Category"><?php echo esc_html(gd_truncateString($postData[GD_REMOTE_KEY]['category'], 100)) ?></p>
                        <pre style="white-space: pre-wrap;" title="Content Snippet"><?php echo esc_html(gd_truncateString($postData[GD_REMOTE_KEY]['raw_content'], 350)) ?></pre>
                    </td>
                    <td>
                        <?php if ($postData['_is_published']) : ?>

                            <div class="row-title">✅ Is on Wordpress</div>
                            <br/>
                            
                            <div>ID: <code><?php echo esc_html($postData[GD_LOCAL_KEY]['ID']) ?></code></div>
                            <div>Slug: <code><?php echo esc_html($postData[GD_LOCAL_KEY]['post_name']) ?></code></div>
                            <div>Excerpt: <code><?php echo esc_html($postData[GD_LOCAL_KEY]['post_excerpt']) ?></code></div>
                            <div>Status: <code><?php echo esc_html($postData[GD_LOCAL_KEY]['post_status']) ?></code></div>

                            <br>

                            <div><a target="_blank" href="<?php echo esc_url($postData[GD_LOCAL_KEY]['guid']) ?>">Open in new Tab</a></div>
                            <br>
                            
                            <?php if (has_post_thumbnail($postData[GD_LOCAL_KEY]['ID'])) : ?>

                                <img src="<?php echo esc_url(get_the_post_thumbnail_url($postData[GD_LOCAL_KEY]['ID'], 'thumbnail')) ?>" alt="Thumbnail not Found" style="max-width: 130px; filter: grayscale(50%); opacity: 0.5">

                            <?php endif; ?>


                        <?php else : ?>

                            <div class="row-title">❌ Not on Wordpress</div>

                        <?php endif ?>
                    </td>
                    <td>
                        <div>
                        <?php if ($postData['_is_published']) : ?>

                            <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=update&gd_slug=' . $postData[GD_REMOTE_KEY]['slug']) ?>" class="button action button-primary">Update</a>
                            <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=delete&gd_slug=' . $postData[GD_REMOTE_KEY]['slug']) ?>" class="button action">Delete</a>
                            
                        <?php else : ?>
                                
                                <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=publish&gd_slug=' . $postData[GD_REMOTE_KEY]['slug']) ?>" class="button action">Publish</a>
                                
                        <?php endif ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <details>
                            <summary>Raw Data for "<i><?php echo $postData[GD_REMOTE_KEY]['name'] ?></i>"</summary>
                            <pre style="white-space: pre-wrap;"><?php gd_dumpJSON(json_decode(json_encode($postData)));?></pre>
                        </details>

                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
</div>