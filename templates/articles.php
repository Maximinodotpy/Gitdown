<div class="wrap gitdown_ui" id="vue_app">

    <div class="tw-w-full tw-bg-red-400 tw-font-bold tw-p-4 tw-text-center tw-text-red-900" v-if="!online">
        <?php _e('You Are offline', 'gitdown'); ?>
    </div>

    <div class="tw-flex tw-items-center tw-flex-col sm:tw-flex-row">
        <h1 class="tw-flex-grow"><?php _e('Manage Git Posts', 'gitdown')?></h1>

        <div class="tw-flex tw-gap-4 tw-items-center">
            <p><?php _e('Made by', 'gitdown')?> <a href="https://maximmaeder.com" target="_blank">Maxim Maeder</a></p>
            <p><i>Gitdown v<?php echo esc_html(get_plugin_data(MGD_ROOT_PATH.'gitdown.php')['Version']) ?></i></p>
            <div>
                <a href="https://github.com/Maximinodotpy/Gitdown" target="_blank">
                    <img src="<?php echo esc_url(MGD_ROOT_URL.'images/github-mark.svg') ?>" alt="Contribute to Gitdown on Github" class="tw-w-[30px]" title="Contribute to Gitdown on Github">
                </a>
            </div>
        </div>
    </div>

    <!-- Report -->
    <details class="tw-my-4">
        <summary class="tw-text-xl tw-transition-all md:tw-p-3 hover:tw-cursor-pointer">
            Report
            <span class="tw-border tw-border-solid tw-border-red-600 tw-px-2 tw-py-1 tw-bg-red-200 tw-text-red-700 tw-rounded-md tw-text-sm">{{ reports?.errors?.length ?? '0' }} Error(s)</span>
        </summary>
        <div class="md:tw-p-8 tw-max-h-[400px] tw-overflow-y-auto tw-overflow-x-visible">
            <div class="tw-grid lg:tw-grid-cols-2 tw-gap-10 lg:tw-gap-28">
                <div class="tw-grid tw-grid-cols-2 tw-auto-rows-auto tw-gap-3 lg:tw-gap-10 md:tw-pr-8">
                    <div>
                        <div class="tw-text-4xl tw-font-mono">{{ reports.found_posts }}</div>
                        <div class="tw-text-lg">Found Posts</div>
                    </div>
                    <div>
                        <div class="tw-text-4xl tw-font-mono">{{ reports.published_posts }}</div>
                        <div class="tw-text-lg">Published Posts</div>
                    </div>
                    <div>
                        <div class="tw-text-4xl tw-font-mono">{{ reports.valid_posts }}</div>
                        <div class="tw-text-lg">Valid Posts</div>
                    </div>
                    <div>
                        <div class="tw-text-4xl tw-font-mono">{{ reports.coerced_slugs }}</div>
                        <div class="tw-text-lg">Coerced Slugs</div>
                    </div>
                </div>
                <div>
                    <div class="tw-text-3xl tw-mb-4">Errors and Warnings</div>

                    <div v-if="reports?.errors?.length != 0" class="tw-flex tw-flex-col tw-gap-4">
                        <div v-for="error in reports.errors">
                            <div class="tw-p-2 md:hover:tw-scale-[1.01] tw-transition-all hover:tw-shadow-lg tw-bg-[#f0f0f1]">
                            <div class="tw-font-mono tw-font-semibold tw-gap-3 tw-flex tw-flex-col">
                                <span class="tw-bg-orange-300 tw-text-orange-700 tw-p-1">{{ error.type }}</span>
                                <span class="tw-bg-blue-300 tw-text-blue-700 tw-flex">
                                    <span class="tw-p-1 tw-bg-blue-900 tw-text-blue-200">@</span>
                                    <span class="tw-p-1">{{ error.location }}</span>
                                </span>
                            </div>
                            <div class="tw-text-lg">
                                {{ error.description }}
                            </div>
                        </div>
                        </div>

                    </div>
                    <div v-else>
                        Congratulations! You have no Errors 😀.
                    </div>
                </div>
            </div>
        </div>
    </details>

    <br>
    <div>
        <div class="tw-flex tw-flex-1 tw-overflow-auto tw-items-center tw-gap-2">
            <button id="pa" @click="updateAllArticles()" class=" button button-primary"><?php _e('Update All', 'gitdown')?></button>

            <button id="da" @click="deleteAll()" class="button "><?php _e('Delete All', 'gitdown')?></button>

            <a href="https://github.com/Maximinodotpy/gitdown-test-repository/archive/refs/heads/master.zip" download="example" class="button "><?php _e('Download Example Folder Structure', 'gitdown')?></a>

            <a href="<?php echo esc_url(get_site_url(null, 'wp-admin/options-reading.php')) ?>" class="button "><?php _e('Settings', 'gitdown')?></a>

            <a href="<?php echo esc_html(home_url('wp-admin/admin.php?page=mgd-article-manager&how_to')) ?>" class="button "><?php _e('How to use Gitdown', 'gitdown')?></a>

            <a href="<?php echo esc_html(home_url('wp-admin/admin.php?page=mgd-article-manager&raw_data')) ?>" class="button ">Show Raw Data</a>
        </div>

        <div class="lg:tw-flex tw-items-center tw-mt-4 tw-hidden">
            <input type="checkbox" v-model="complex_view">
            <span><?php _e('Complex View', 'gitdown')?></span>
        </div>
    </div>

    <br>

    <table class="fixed wp-list-table widefat striped table-view-list posts">
        <thead>
            <tr class="tw-z-40 md:tw-top-[32px] tw-top-[0] tw-sticky tw-bg-white">
                <th class="tw-flex tw-flex-col tw-align-baseline">
                    <div class="tw-mb-3 tw-flex tw-items-center tw-justify-between">
                        <div>
                            <div class="tw-hidden sm:tw-block"><?php _e('Remote', 'gitdown')?></div>
                            <div class="sm:tw-hidden"><?php _e('Articles', 'gitdown')?></div>
                        </div>

                        <div class="lg:tw-hidden tw-items-center md:tw-mt-0 tw-flex tw-gap-2">
                            <input type="checkbox" v-model="complex_view" class="tw-m-0">
                            <span><?php _e('Complex View', 'gitdown')?></span>
                        </div>
                    </div>

                    <div class="tw-flex tw-flex-1 tw-overflow-auto tw-gap-2 tw-w-full tw-flex-nowrap tw-whitespace-nowrap mgd-hide-scrollbar">
                        <a href="<?php echo esc_url(get_option('mgd_repo_setting')) ?>" target="_blank">
                            <code><?php echo esc_html(basename(get_option('mgd_repo_setting'))) ?></code>
                        </a>
                        →
                        <code>
                            <?php echo get_option('mgd_glob_setting') ?>
                        </code>
                        ↓
                    </div>
                </th>
                <th class="tw-hidden sm:tw-table-cell tw-align-baseline">
                    <div class="tw-mb-3">
                        WordPress
                    </div>
                    
                    <?php
                        $automatice_updates = (bool) get_option('mgd_cron_setting');
                    ?>
                    <?php if($automatice_updates) : ?>
                        <code class="tw-whitespace-nowrap tw-border-2 tw-border-solid tw-border-green-500 tw-bg-green-200 tw-text-green-800">Automatic Updates Enabled</code>
                    <?php else : ?>
                        <code class="tw-whitespace-nowrap tw-border-2 tw-border-solid tw-border-red-500 tw-bg-red-200 tw-text-red-800">Automatic Updates Disabled</code>
                    <?php endif ; ?>
                </th>
                <th class="tw-hidden sm:tw-table-cell tw-align-baseline"><?php _e('Actions', 'gitdown')?></th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="item in articles" class="tw-relative tw-box-border tw-flex sm:tw-table-row tw-flex-col" :key="item.remote.slug">
                <td class="md:tw-py-4 tw-py-2">
                    <p class="row-title" title="Post Name">
                        {{ item.remote.name }}

                        <span class="tw-block tw-text-neutral-400">
                            <span>{{ item.remote.status ?? 'publish' }}</span>
                            |
                            <span>{{ item.remote.post_type ?? 'post' }}</span>
                            |
                            <code title="last commit hash for this article in the remote repository">{{ item.remote.last_commit.slice(0, 6) }}</code>
                        </span>
                    </p>

                    <div v-if="complex_view">
                        <p title="Post Slug">{{ item.remote.slug }}</p>
                        <p title="description">{{ item.remote.description }}</p>
                        <p title="Category">{{ item.remote.category }}</p>
                    </div>
                </td>
                <td class="md:tw-py-4 tw-py-2">
                    <template v-if="item._is_published">
                        <div class="row-title tw-mb-3">
                            ✅ <?php _e('Is on WordPress', 'gitdown')?>

                            <span class="tw-opacity-75">and it is </span>

                            <span v-if="item.remote.last_commit == item.local.last_commit" class="">📪 up to Date</span>
                            <span v-else>📬 Outdated</span>
                            </div>
                        </div>
                        <div>
                            <b>Last updated: </b>
                            {{ new Date(item.last_updated*1000).toLocaleString() }}
                        </div>

                        <div v-if="complex_view">
                            <div>ID: <code>{{ item.local.ID }}</code></div>
                            <div>Latest Commit: <code title="last commit hash for this article in the remote repository">{{ item.local?.last_commit?.slice(0, 6) }}</code></div>
                            <div>Slug: <code>{{ item.local.post_name }}</code></div>
                            <div>Excerpt: <code>{{ item.local.post_excerpt }}</code></div>
                            <div>Status: <code>{{ item.local.post_status }}</code></div>
                        <div>
                    </template>

                    <template v-else>
                        ❌ <?php _e('Not on WordPress', 'gitdown')?>
                    </template>
                </td>
                <td class="tw-relative tw-box-border md:tw-py-4 tw-py-2">
                    <div>
                        <div class="tw-flex tw-mb-2 tw-gap-2 tw-items-center">
                            <!-- Start Sync -->
                            <button v-if="!item._is_published" class="button action button-primary tw-inline-block" @click="update_post(item.remote.slug)"><?php _e('Start Sync', 'gitdown')?></button>
                            
                            <!-- Sync and Delete -->
                            <button v-if="item._is_published" class="button action button-primary tw-inline-block" @click="update_post(item.remote.slug)"><?php _e('Update', 'gitdown')?></button>
                            <button v-if="item._is_published" class="button action tw-inline-block" @click="delete_post(item.remote.slug)"><?php _e('Delete', 'gitdown')?></button>

                            <!-- Loader -->
                            <div :ref="item.remote.slug" style="visibility: hidden">
                                <div class="tw-text-lg tw-font-semibold tw-flex tw-items-center tw-gap-2 drop-shadow-2xl">
                                    <img src="<?php echo esc_url(MGD_ROOT_URL . 'images/loader.svg') ?>" alt="Loader" style="width: 30px">
                                    <span class="tw-select-none"><?php _e('Loading', 'gitdown')?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Extra Links -->
                        <div class="tw-flex tw-gap-2">
                            <a :href="`<?php echo esc_html(home_url('wp-admin/admin.php?page=mgd-article-manager&raw_data=&slug=')) ?>${item.remote.slug}`">Raw Data</a>

                            <a v-if="item._is_published" :href="`<?php echo esc_html(home_url('wp-admin/edit.php?post_type=${item.local.post_type}#post-${item.local.ID}')) ?>`">Normal Overview</a>

                            <a v-if="item._is_published" target="_blank" class="tw-inline-block" :href="item.local.guid"><?php _e('Open in new Tab', 'gitdown')?> ↗</a>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <div v-if="articles.length == 0" class="tw-text-xl tw-text-center tw-py-10">
        If there are no articles, maybe an error occured, you will find helpful informations in the <code>Report</code> section above.
    </div>
</div>