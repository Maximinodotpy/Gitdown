<div class="wrap gitdown_ui">
    <h1>Manage Git Articles</h1>

    <?php if (GD_REMOTE_IS_CLONED) : ?>

        <p>According to the glob pattern <code><?php echo get_option(GD_SETTING_GLOB) ?></code> and your set resolver function the following files could be found.</p>

        <p>Keep in mind that all articles are identified by their <code>slug</code>/<code>post_name</code>. Thats why it is shown here in the Github column. If you change the slug in the markdown file, Gitdown wont recognize that the articles belong together.</p>

        <p>Contact me if <a href="mailto:info@maximmaeder.com?subject=Gitdown: ">here</a> if you found some issues or have any questions.</p>

        <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=publish_all') ?>" class="button button-primary">Update / Publish All</a>
        <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=delete_all') ?>" class="button">Delete All</a>

    <?php else : ?>

        <p>Lets start by fetching/cloning the Repo at <code><?php echo esc_url(get_option(GD_SETTING_REPO)) ?></code></p>

    <?php endif ?>

    <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=fetch_repository') ?>" class="button">Fetch Repo</a>

    <a href="<?php echo esc_url(dirname(plugin_dir_url(__FILE__), 1) . '/files/example.zip') ?>" download="example" class="button">Download Example Folder Structure</a>

    <br>
    <br>

    <?php if (GD_DEBUG) : ?>
        <details open>
            <summary>Debug</summary>

            <pre style="white-space: pre-wrap;"><?php gd_dumpJSON(json_decode(json_encode([
                                                    'os' => PHP_OS,
                                                    'GD_REMOTE_IS_CLONED' => GD_REMOTE_IS_CLONED,
                                                ]))); ?></pre>
        </details>
    <?php endif; ?>

    <br>

    <br>

    <table class="fixed wp-list-table widefat striped table-view-list posts" id="vue_app">
        <thead>
            <tr>
                <th>Remote</th>
                <th>Wordpress</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="item in articles">
                <td>
                    <p class="row-title" title="Post Name">{{ item.remote.name }}</p>
                    <p title="Post Slug">{{ item.remote.slug }}</p>
                    <p title="description">{{ item.remote.description }}</p>
                    <p title="Category">{{ item.remote.category }}</p>
                </td>
                <td>
                    <template v-if="item._is_published">
                        <div class="row-title">✅ Is on Wordpress</div>

                        <br/>

                        <div>ID: <code>{{ item.local.ID }}</code></div>
                        <div>Slug: <code>{{ item.local.post_name }}</code></div>
                        <div>Excerpt: <code>{{ item.local.post_excerpt }}</code></div>
                        <div>Status: <code>{{ item.local.post_status }}</code></div>
                        <br>
                        <div><a target="_blank" :href="item.local.guid">Open in new Tab</a></div>
                        <br>

                        <?php if (has_post_thumbnail($postData[GD_LOCAL_KEY]['ID'])) : ?>
                            <img src="" alt="Thumbnail not Found" style="max-width: 130px; filter: grayscale(50%); opacity: 0.5">
                        <?php endif; ?>
                    </template>
                </td>
                <td>
                    <div v-if="item._is_published">
                        <button href="" class="button action button-primary" @click="updateArticle(item.remote.slug)">Update</button>
                        <button href="" class="button action">Delete</button>
                    </div>
                        
                    <div v-else>
                        <button href="" class="button action">Publish</button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>