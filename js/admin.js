const { createApp } = Vue

const vueApp = createApp({
    data() {
        return {
            message: 'Hello Vue!',
            articles: []
        }
    },
    async mounted() {
        this.articles = (await this.callAJAX({
            action: 'get_all_articles',
        })).reverse()
        console.log(this.articles);
    },
    methods: {
        async callAJAX(desiredData) {
            const data = new FormData();
            data.append('action', desiredData.action);
            data.append('slug', desiredData.slug);

            const re = await fetch(ajaxurl, {
                method: 'POST',
                body: data,
            })
            return await re.json();
        },
        async updateArticle(slug) {
            const loaderElement = this.$refs[slug][0]

            console.log('Updating: '+slug);

            loaderElement.style.visibility = 'visible';

            const newData = await this.callAJAX({
                action: 'update_article',
                slug: slug,
            })

            this.articles.find(article => {
                if (article.remote.slug == slug) {
                    article.local = newData;
                    article._is_published = true;
                }
            });

            loaderElement.style.visibility = 'hidden';
        },
        async deleteArticle(slug) {
            const loaderElement = this.$refs[slug][0]
            loaderElement.style.visibility = 'visible';


            console.log('Deleting: '+slug);

            const newData = await this.callAJAX({
                action: 'delete_article',
                slug: slug,
            })

            if (newData) {
                this.articles.forEach(article => {
                    if (article.remote.slug == slug) {
                        article._is_published = false;
                        article.local = {};
                    }
                })
            }

            loaderElement.style.visibility = 'hidden';
        },
    }
})

document.addEventListener('DOMContentLoaded', (event) => {
    vueApp.mount('#vue_app')
})