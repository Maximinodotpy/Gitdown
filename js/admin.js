/* console.log('Maxim Maeder');

console.log({ document });
console.log(this);
console.log(ajaxurl);

let obj = { action: 'get_time' };

const data = new FormData();

data.append('action', 'get_time');

fetch(ajaxurl, {
    method: 'POST', // or 'PUT'
    credentials: 'same-origin',
    body: data,
})
    .then((re) => re.json())
    .then((json) => console.log(json))
 */

const { createApp } = Vue

const vueApp = createApp({
    data() {
        return {
            message: 'Hello Vue!',
            articles: []
        }
    },
    async mounted() {
        this.articles = await this.callAJAX({
            action: 'get_all_articles',
        })
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
            console.log('Updating: '+slug);

            const newData = await this.callAJAX({
                action: 'update_article',
                slug: slug,
            })

            console.log(newData);
            /* const old = this.articles.find(article => article.slug == slug);
            old = newData */
        }
    }
})

document.addEventListener('DOMContentLoaded', (event) => {
    vueApp.mount('#vue_app')
})