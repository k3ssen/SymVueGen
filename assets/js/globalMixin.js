import Vue from 'vue';

Vue.mixin({
    methods: {
        fetchPage(url) {
            this.$store.commit('setPageUrl', url);
        },
    }
});
