// eslint-disable-next-line no-undef
const { createApp } = Vue

const vueApp = createApp({
    data() {
        return {
            articles: [],
            search_query: '',
            complex_view: false,
            reports: {
                published_posts: 0,
                found_posts: 0,
                valid_posts: 0,
                coerced_slugs: 0,
            }
        }
    },

    async mounted() {
        await this.sync()
    },

    methods: {

        async callAJAX(desiredData) {
            const form_data = new FormData()

            for (const key in desiredData) {
                form_data.append(key, desiredData[key])
            }

            // eslint-disable-next-line no-undef
            const re = await fetch(ajaxurl, {
                method: 'POST',
                body: form_data,
            })
            return await re.json()
        },

        async updateArticle(slug) {
            const loaderElement = this.$refs[slug][0]
            loaderElement.style.visibility = 'visible'

            console.log('Updating: '+slug)


            const newData = await this.callAJAX({
                action: 'update_article',
                slug: slug,
            })

            this.articles.find(article => {
                if (article.remote.slug == slug) {
                    article.local = newData
                    article._is_published = true
                }
            })

            loaderElement.style.visibility = 'hidden'
        },

        async deleteArticle(slug) {
            const loaderElement = this.$refs[slug][0]
            loaderElement.style.visibility = 'visible'

            console.log('Deleting: '+slug)

            const newData = await this.callAJAX({
                action: 'delete_article',
                slug: slug,
            })

            if (newData) {
                this.articles.forEach(article => {
                    if (article.remote.slug == slug) {
                        article._is_published = false
                        article.local = {}
                    }
                })
            }

            loaderElement.style.visibility = 'hidden'
        },

        updateAllArticles() {
            console.log('Updating All')

            this.articles.forEach(article => {
                this.updateArticle(article.remote.slug)
            })
        },

        deleteAll() {
            console.log('Deleting all articles')

            this.articles.forEach(article => {
                this.deleteArticle(article.remote.slug)
            })
        },

        async sync() {            
            const response = (await this.callAJAX({
                action: 'get_all_articles',
            }))

            this.articles = response.posts.reverse()
            this.reports = response.reports

            console.log(this.articles)
            console.log(response.reports)
        }
    }
})

document.addEventListener('DOMContentLoaded', () => {
    vueApp.mount('#vue_app')
})