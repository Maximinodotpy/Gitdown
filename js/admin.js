"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
if (window.Vue) {
    // eslint-disable-next-line no-undef
    const { createApp } = Vue;
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
            };
        },
        mounted() {
            return __awaiter(this, void 0, void 0, function* () {
                yield this.sync();
                this.articles = [];
            });
        },
        methods: {
            callAJAX(desiredData) {
                return __awaiter(this, void 0, void 0, function* () {
                    const form_data = new FormData();
                    for (const key in desiredData) {
                        const value = desiredData[key];
                        form_data.append(key, value);
                    }
                    // eslint-disable-next-line no-undef
                    const re = yield fetch(ajaxurl, {
                        method: 'POST',
                        body: form_data,
                    });
                    return yield re.json();
                });
            },
            update_post(slug) {
                return __awaiter(this, void 0, void 0, function* () {
                    const loaderElement = this.$refs[slug][0];
                    loaderElement.style.visibility = 'visible';
                    console.log('Updating: ' + slug);
                    const newData = yield this.callAJAX({
                        action: 'update_article',
                        slug: slug,
                    });
                    this.articles.find(article => {
                        if (article.remote.slug == slug) {
                            article.local = newData.new_post;
                            article._is_published = true;
                            article.last_updated = newData.last_updated;
                        }
                    });
                    console.log(newData);
                    loaderElement.style.visibility = 'hidden';
                });
            },
            delete_post(slug) {
                return __awaiter(this, void 0, void 0, function* () {
                    const loaderElement = this.$refs[slug][0];
                    loaderElement.style.visibility = 'visible';
                    console.log('Deleting: ' + slug);
                    const newData = yield this.callAJAX({
                        action: 'delete_article',
                        slug: slug,
                    });
                    if (newData) {
                        this.articles.forEach(article => {
                            if (article.remote.slug == slug) {
                                article._is_published = false;
                                article.local = {};
                            }
                        });
                    }
                    loaderElement.style.visibility = 'hidden';
                });
            },
            updateAllArticles() {
                console.log('Updating All');
                this.articles.forEach(article => {
                    this.update_post(article.remote.slug);
                });
            },
            deleteAll() {
                console.log('Deleting all articles');
                this.articles.forEach(article => {
                    this.delete_post(article.remote.slug);
                });
            },
            sync() {
                return __awaiter(this, void 0, void 0, function* () {
                    const response = (yield this.callAJAX({
                        action: 'get_all_articles',
                    }));
                    this.articles = response.posts.reverse();
                    this.reports = response.reports;
                    console.log(this.articles);
                    console.log(response.reports);
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
