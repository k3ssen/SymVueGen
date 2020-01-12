import Vue from 'vue';
var pluralize = require('pluralize');

// $store property available in all components
Vue.prototype.$store = Vue.observable({
    pageUrl: null,
    vuePage: vue,
    pageData: {},
});

// Global mixin for methods available in all components.
Vue.mixin({
    computed: {
        pageData: {
            get() {
                return this.$store.pageData;
            },
            set(pageData) {
                this.$store.pageData = pageData;
            }
        },
    },
    methods: {
        pluralize(word, count = null, inclusive = false) {
            if (count !== null) {
                return pluralize(word);
            }
            return pluralize(word, count, inclusive);
        },
        async fetchPage(url) {
            // Add vue=1 parameter. This can be used to decide that only vue content should be fetched, but it also
            // prevents that the back button will fetch vue-content instead of the whole page due to caching
            url += url.includes('?') ? '&vue=1' : '?vue=1';
            const pageResult = await fetch(url);

            this.$store.vuePage = (new Function(
                (await pageResult.text()).replace(/<\/?script([^a-zA-Z>]?)([^>]*)>/g, '')
                + '; return vue'
            ))();
        }
    }
});