<div class="wrap gitdown_ui" id="vue_app">
    <h1><?php _e('Manage Git Articles', 'gitdown')?></h1>

    <p><?php _e('This plugin was made by', 'gitdown')?> <a href="https://maximmaeder.com" target="_blank">Maxim Maeder</a>.</p>

    <div style="display: flow-root;">
        <ul class='subsubsub'>
            {{ articles.filter(article => article._is_published).length }}
            <span style="color: hsl(0, 0%, 60%)"> / </span>
            {{ articles.length }}
        </ul>
    </div>

    <br>

    <div>
        <button @click="updateAllArticles()" class="tw-mr-2 button button-primary"><?php _e('Update All', 'gitdown')?></button>

        <button @click="deleteAll()" class="button tw-mr-2"><?php _e('Delete All', 'gitdown')?></button>

        <button @click="sync()" class="button tw-mr-2"><?php _e('Reload', 'gitdown')?></button>

        <a href="https://github.com/Maximinodotpy/gitdown-test-repository/archive/refs/heads/master.zip" download="example" class="button tw-mr-2"><?php _e('Download Example Folder Structure', 'gitdown')?></a>

        <a href="<?php echo get_site_url(null, 'wp-admin/options-reading.php') ?>" class="button tw-mr-2"><?php _e('Settings', 'gitdown')?></a>

        <p class="search-box">
            <span class="tw-inline-block">
                <span class="tw-mr-3 tw-flex tw-items-center">
                    <input type="checkbox" v-model="complex_view">
                    <span><?php _e('Complex View', 'gitdown')?></span>
                </span>
            </span>

            <label class="screen-reader-text" for="post-search-input"><?php _e('Search Posts')?>:</label>
            <input type="search" id="post-search-input" v-model="search_query" placeholder="<?php _e('Search')?>" />
        </p>
    </div>

    <br>

    <table class="fixed wp-list-table widefat striped table-view-list posts">
        <thead>
            <tr>
                <th class="tw-flex tw-flex-col">
                    <span class="inline-block tw-mr-2 tw-mb-3">
                        <?php _e('Remote', 'gitdown')?>
                    </span>
                    
                    <span class="tw-block">
                        <a href="<?php echo get_option(GD_SETTING_REPO) ?>" target="_blank">
                            <code class=""><?php echo basename(get_option(GD_SETTING_REPO)) ?></code>
                        </a>
                        →
                        <code class="">
                            <?php echo get_option(GD_SETTING_GLOB) ?>
                        </code>
                        ↓
                    </span>
                </th>
                <th>Wordpress</th>
                <th><?php _e('Actions', 'gitdown')?></th>
                <th class="tw-w-0"><!-- This row is used for the Loader --></th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="item in articles" class="tw-relative tw-box-border">
                <template v-if="item.remote.name.toLowerCase().includes(search_query.toLowerCase())">
                    <td>
                        <p class="row-title" title="Post Name">{{ item.remote.name }}
                            <span class="tw-text-neutral-400">— {{ item.remote.status ?? 'publish' }}</span>
                        </p>

                        <div v-if="complex_view">
                            <p title="Post Slug">{{ item.remote.slug }}</p>
                            <p title="description">{{ item.remote.description }}</p>
                            <p title="Category">{{ item.remote.category }}</p>
                        </div>
                    </td>
                    <td>
                        <template v-if="item._is_published">
                            <div class="row-title tw-mb-3">✅ <?php _e('Is on Wordpress', 'gitdown')?></div>

                            <div v-if="complex_view">
                                <div>ID: <code>{{ item.local.ID }}</code></div>
                                <div>Slug: <code>{{ item.local.post_name }}</code></div>
                                <div>Excerpt: <code>{{ item.local.post_excerpt }}</code></div>
                                <div>Status: <code>{{ item.local.post_status }}</code></div>
                            <div>
                        </template>

                        <template v-else>
                            ❌ <?php _e('Not on wordpress', 'gitdown')?>
                        </template>
                    </td>
                    <td class="tw-relative tw-box-border">
                        <div v-if="item._is_published">
                            <button class="button action button-primary tw-mr-2 tw-mb-2 tw-inline-block" @click="updateArticle(item.remote.slug)"><?php _e('Update', 'gitdown')?></button>
                            <button class="button action tw-mr-2 tw-mb-2 tw-inline-block" @click="deleteArticle(item.remote.slug)"><?php _e('Delete', 'gitdown')?></button>

                            <a target="_blank" class="button action tw-inline-block" :href="item.local.guid"><?php _e('Open in new Tab', 'gitdown')?> ↗</a>
                        </div>

                        <div v-else>
                            <button class="button action" @click="updateArticle(item.remote.slug)"><?php _e('Publish', 'gitdown')?></button>
                        </div>

                        <br>
                    </td>
                    
                    <td :ref="item.remote.slug" style="visibility: hidden" 
                        class="tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-full tw-flex tw-justify-center tw-items-center tw-backdrop-blur-[4px]">
                        <div class="tw-text-xl tw-font-semibold tw-flex tw-items-center tw-gap-2 drop-shadow-2xl">
                            <img src="<?php echo GD_ROOT_URL . 'images/loader.svg' ?>" alt="Loader" style="width: 40px">
                            <span class="tw-select-none"><?php _e('Loading', 'gitdown')?></span>
                        </div>
                    </td>
                </template>
            </tr>
        </tbody>
    </table>
</div>