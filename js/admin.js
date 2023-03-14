if (window.Vue) {
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
    
            async update_post(slug) {
                const loaderElement = this.$refs[slug][0]
                loaderElement.style.visibility = 'visible'
    
                console.log('Updating: '+slug)

                this.callAJAX({
                    action: 'update_article',
                    slug: slug,
                }).then(newData => {
                    this.articles.find(article => {
                        if (article.remote.slug == slug) {
                            article.local = newData.new_post
                            article._is_published = true
                            article.last_updated = newData.last_updated
                        }
                    })
                }).catch(error => {
                    console.log(error)
                    this.sync()
                }).finally(() => {
                    loaderElement.style.visibility = 'hidden'
                })
            },
    
            async delete_post(slug) {
                const loaderElement = this.$refs[slug][0]
                loaderElement.style.visibility = 'visible'
    
                console.log('Deleting: '+slug)
    
                this.callAJAX({
                    action: 'delete_article',
                    slug: slug,
                }).then(() => {
                    this.articles.forEach(article => {
                        if (article.remote.slug == slug) {
                            article._is_published = false
                            article.local = {}
                        }
                    })
                }).catch(error => {
                    console.log(error)
                }).finally(() => {
                    loaderElement.style.visibility = 'hidden'
                })
            },
    
            updateAllArticles() {
                console.log('Updating All')
    
                this.articles.forEach(article => {
                    this.update_post(article.remote.slug)
                })
            },
    
            deleteAll() {
                console.log('Deleting all articles')
    
                this.articles.forEach(article => {
                    this.delete_post(article.remote.slug)
                })
            },
    
            async sync() {            
                const response = (await this.callAJAX({
                    action: 'get_all_articles',
                }))
    
                this.articles = response.posts.reverse()
                this.reports = response.reports
            },
        }
    })
    
    
    
    document.addEventListener('DOMContentLoaded', () => {
        const appNode = document.querySelector('#vue_app')
        console.log(appNode)
        if (appNode) {
            vueApp.mount(appNode)
        }
    })

}