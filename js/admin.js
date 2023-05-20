if (Object.hasOwn(window, 'Vue')) {
    // eslint-disable-next-line no-undef
    // @ts-ignore
    const { createApp } = window.Vue;
    const vueApp = createApp({
        data() {
            return {
                articles: [],
                complex_view: false,
                reports: {
                    published_posts: 0,
                    found_posts: 0,
                    valid_posts: 0,
                    coerced_slugs: 0,
                },
                online: true
            };
        },
        mounted() {
            this.online = navigator.onLine;
            window.addEventListener('online', (event) => {
                console.log("You are now connected to the network.");
                this.online = true;
                this.sync();
            });
            window.addEventListener('offline', (event) => {
                console.log("You are no longer connected to the network.");
                this.online = false;
            });
            this.sync();
        },
        methods: {
            async callAJAX(desiredData) {
                const form_data = new FormData();
                for (const key in desiredData) {
                    form_data.append(key, desiredData[key]);
                }
                // eslint-disable-next-line no-undef
                // @ts-ignore
                const re = await fetch(ajaxurl, {
                    method: 'POST',
                    body: form_data,
                });
                return await re.json();
            },
            update_post(slug) {
                const loaderElement = this.$refs[slug][0];
                loaderElement.setAttribute('style', 'visibility: visible; width: 100% !important;');
                console.log(`%cUpdating Post: %c${slug}`, 'font-weight: bold', 'font-weight: unset');
                this.callAJAX({
                    action: 'update_article',
                    slug: slug,
                }).then((newData) => {
                    this.articles.find(article => {
                        if (article.remote.slug == slug) {
                            article.local = newData.new_post;
                            article._is_published = true;
                            article.last_updated = newData.last_updated;
                            article.local.last_commit = newData.last_commit;
                        }
                    });
                }).catch(error => {
                    console.log(error);
                    if (this.online)
                        this.sync();
                }).finally(() => {
                    loaderElement.style.visibility = 'hidden';
                });
            },
            delete_post(slug) {
                const loaderElement = this.$refs[slug][0];
                loaderElement.setAttribute('style', 'visibility: visible; width: 100% !important;');
                console.log(`%cDeleting: %c${slug}`, 'font-weight: bold', 'font-weight: unset');
                this.callAJAX({
                    action: 'delete_article',
                    slug: slug,
                }).then(() => {
                    this.articles.forEach(article => {
                        if (article.remote.slug == slug) {
                            article._is_published = false;
                            article.local = {};
                        }
                    });
                }).catch(error => {
                    console.log(error);
                    if (this.online)
                        this.sync();
                }).finally(() => {
                    loaderElement.style.visibility = 'hidden';
                });
            },
            updateAllArticles() {
                console.log('Updating All ...');
                this.articles.forEach(article => {
                    this.update_post(article.remote.slug);
                });
            },
            deleteAll() {
                console.log('Deleting All articles ...');
                this.articles.forEach(article => {
                    this.delete_post(article.remote.slug);
                });
            },
            sync() {
                this.callAJAX({
                    action: 'get_all_articles',
                }).then(response => {
                    this.articles = response.posts.reverse();
                    this.reports = response.reports;
                }).catch(error => {
                    console.log(error);
                });
            },
        }
    });
    document.addEventListener('DOMContentLoaded', () => {
        const appNode = document.querySelector('#vue_app');
        if (appNode) {
            vueApp.mount(appNode);
        }
    });
}
