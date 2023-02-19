<div class="wrap gitdown_ui" id="vue_app">
    <h1>Manage Git Articles</h1>

    <div style="display: flow-root;">
        <ul class='subsubsub'>
            <span style="color: hsl(0, 0%, 60%)">Published </span>
            {{ articles.filter(article => article._is_published).length }}
            <span style="color: hsl(0, 0%, 60%)"> out of </span>
            {{ articles.length }}
        </ul>
    </div>

    <br>

    <div>
        <button @click="updateAllArticles()" class="tw-mr-2 button button-primary">Update All</button>

        <button @click="deleteAll()" class="button tw-mr-2">Delete All</button>

        <button @click="sync()" class="button tw-mr-2">Reload</button>

        <a href="<?php echo esc_url(dirname(plugin_dir_url(__FILE__), 1) . '/files/example.zip') ?>" download="example" class="button tw-mr-2">Download Example Folder Structure</a>

        <p class="search-box">
            <span class="tw-inline-block">
                <span class="tw-mr-3 tw-flex tw-items-center">
                    <input type="checkbox" v-model="complex_view">
                    <span>Complex View</span>
                </span>
            </span>

            <label class="screen-reader-text" for="post-search-input">Search Posts:</label>
            <input type="search" id="post-search-input" v-model="search_query" placeholder="Search" />
        </p>
    </div>

    <br>

    <table class="fixed wp-list-table widefat striped table-view-list posts">
        <thead>
            <tr>
                <th>Remote</th>
                <th>Wordpress</th>
                <th>Actions</th>
                <th class="tw-w-0"><!-- This row is used for the Loader --></th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="item in articles" class="tw-relative tw-box-border">
                <template v-if="item.remote.name.toLowerCase().includes(search_query.toLowerCase())">
                    <td>
                        <p class="row-title" title="Post Name">{{ item.remote.name }}</p>

                        <div v-if="complex_view">
                            <p title="Post Slug">{{ item.remote.slug }}</p>
                            <p title="description">{{ item.remote.description }}</p>
                            <p title="Category">{{ item.remote.category }}</p>
                        </div>
                    </td>
                    <td>
                        <template v-if="item._is_published">
                            <div class="row-title tw-mb-3">✅ Is on Wordpress</div>

                            <div v-if="complex_view">
                                <div>ID: <code>{{ item.local.ID }}</code></div>
                                <div>Slug: <code>{{ item.local.post_name }}</code></div>
                                <div>Excerpt: <code>{{ item.local.post_excerpt }}</code></div>
                                <div>Status: <code>{{ item.local.post_status }}</code></div>
                            <div>
                        </template>
                    </td>
                    <td class="tw-relative tw-box-border">
                        <div v-if="item._is_published">
                            <button class="button action button-primary tw-mr-4" @click="updateArticle(item.remote.slug)">Update</button>
                            <button class="button action tw-mr-4" @click="deleteArticle(item.remote.slug)">Delete</button>

                            <a target="_blank" class="button action" :href="item.local.guid">Open in new Tab</a>
                        </div>

                        <div v-else>
                            <button class="button action" @click="updateArticle(item.remote.slug)">Publish</button>
                        </div>

                        <br>
                    </td>
                    
                    <td :ref="item.remote.slug" style="visibility: hidden" 
                        class="tw-absolute tw-top-0 tw-left-0 tw-w-full tw-h-full tw-flex tw-justify-center tw-items-center tw-backdrop-blur-[4px]">
                        <div class="tw-text-xl tw-font-semibold tw-flex tw-items-center tw-gap-2 drop-shadow-2xl">
                            <img src="<?php echo GD_ROOT_URL . 'images/loader.svg' ?>" alt="Loader" style="width: 40px">
                            <span class="tw-select-none">Loading</span>
                        </div>
                    </td>
                </template>
            </tr>
        </tbody>
    </table>
</div>