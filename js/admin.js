// eslint-disable-next-line no-undef
const { createApp } = Vue

const vueApp = createApp({
    data() {
        return {
            articles: [],
            search_query: '',
            complex_view: false,
            metadata: {
                repo_url: 'https://github.com/Maximinodotpy/articles.git'
            }
        }
    },

    async mounted() {
        console.log('Ruunning tour')

        var queryDict = {}
        location.search.substr(1).split('&').forEach(function(item) {queryDict[item.split('=')[0]] = item.split('=')[1]})
        
        if ('run_tour' in queryDict) {
            // eslint-disable-next-line no-undef
            runTour([
                {
                    title: 'Introducing: Gitdown',
                    text: 'Thank you for using gitdown! Lets go over the UI so you can go and create something awesome.',
                },
                {
                    title: 'Global Actions: Updating',
                    text: 'Will update or publish all articles in your repository.',
                    element: '#pa',
                },
                {
                    title: 'Global Actions: Deleting',
                    text: 'Will delete these articles from Wordpress not Github so watch out.',
                    element: '#da',
                },
                {
                    title: 'Global Actions: Reloading',
                    text: 'Simpley Reload all articles with this button.',
                    element: '#sy',
                },
                {
                    title: 'Rows',
                    text: 'Each row represents a single that was found in your repository.',
                    element: 'table tr td',
                },
            ])
        }


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
            this.articles = []

            this.articles = (await this.callAJAX({
                action: 'get_all_articles',
            })).reverse()
            console.log(this.articles)
        }
    }
})

document.addEventListener('DOMContentLoaded', () => {
    vueApp.mount('#vue_app')
})