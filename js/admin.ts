interface article {
    remote: {
        name: string,
        slug: string,
        description: string,
        category: string[] | string,
        tags: string[] | string,
        raw_content: string,
        featured_image: string,
    },
    local: {
        ID: number,
        post_author: string,
        post_date: string,
        post_date_gmt: string,
        post_content: string,
        post_excerpt: string,
        post_status: 'publish' | 'draft' | 'trash',
        comment_status: 'closed' | 'open',
        ping_status: 'open' | 'closed',
        post_name: string,
        to_ping: string,
        pinged: string,
        post_modified: string,
        post_modified_gmt: string,
        post_content_filtered: '',
        post_parent: 0,
        guid: string,
        menu_order: 0,
        post_type: 'post',
        post_mime_type: '',
        comment_count: '0',
        filter: 'raw'
    },
    _is_published: true,
    last_updated: 1678556573
}

type article_list = article[]



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
            
            this.articles = []
        },
    
        methods: {
            async callAJAX(desiredData : {action: string, slug: string; [key: string]: string;}) {
                const form_data = new FormData()
    
                for (const key in desiredData) {
                    const value : string = desiredData[key]
                    form_data.append(key, value)
                }
    
                // eslint-disable-next-line no-undef
                const re = await fetch(ajaxurl, {
                    method: 'POST',
                    body: form_data,
                })
                return await re.json()
            },
    
            async update_post(slug : string) {
                const loaderElement = this.$refs[slug][0]
                loaderElement.style.visibility = 'visible'
    
                console.log('Updating: '+slug)
    
    
                const newData = await this.callAJAX({
                    action: 'update_article',
                    slug: slug,
                })
    
                this.articles.find(article => {
                    if (article.remote.slug == slug) {
                        article.local = newData.new_post
                        article._is_published = true
                        article.last_updated = newData.last_updated
                    }
                })

                console.log(newData)
    
                loaderElement.style.visibility = 'hidden'
            },
    
            async delete_post(slug : string) {
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
    
                console.log(this.articles)
                console.log(response.reports)
            },
        }
    })
    
    
    
    document.addEventListener('DOMContentLoaded', () => {
        const appNode = document.querySelector('#vue_app')

        if (appNode) {
            vueApp.mount(appNode)
        }
    })

}
