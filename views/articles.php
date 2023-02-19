<div class="wrap gitdown_ui" id="vue_app">
    <h1>Manage Git Articles ...</h1>
    <p>...  for <code>{{ metadata.repo_url }}</code>.</p>

    <p>According to the glob pattern <code><?php echo get_option(GD_SETTING_GLOB) ?></code> and your set resolver function the following files could be found.</p>

    <!-- <p>Keep in mind that all articles are identified by their <code>slug</code>/<code>post_name</code>. Thats why it is shown here in the Github column. If you change the slug in the markdown file, Gitdown wont recognize that the articles belong together.</p> -->

    <!-- <p>Contact me if <a href="mailto:info@maximmaeder.com?subject=Gitdown: ">here</a> if you found some issues or have any questions.</p> -->
    
    
    <div class="tablenav top">
        <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=publish_all') ?>" class="button button-primary">Update / Publish All</a>
        
        <a href="<?php echo esc_url($_SERVER['REQUEST_URI'] . '&gd_action=delete_all') ?>" class="button">Delete All</a>
        
        <button @click="sync()" class="button">Reload</button>
        
        <a href="<?php echo esc_url(dirname(plugin_dir_url(__FILE__), 1) . '/files/example.zip') ?>" download="example" class="button">Download Example Folder Structure</a>
    </div>

    <br>

    <table class="fixed wp-list-table widefat striped table-view-list posts">
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
                        <button class="button action button-primary" @click="updateArticle(item.remote.slug)">Update</button>
                        <button class="button action" @click="deleteArticle(item.remote.slug)">Delete</button>
                    </div>
                        
                    <div v-else>
                        <button class="button action" @click="updateArticle(item.remote.slug)">Publish</button>
                    </div>

                    <div :ref="item.remote.slug" style="visibility: hidden; display: flex; align-items: center; font-weight: 600">
                        <img src="<?php echo GD_ROOT_URL.'images/loader.svg' ?>" alt="Loader" style="width: 30px">
                        Loading
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>