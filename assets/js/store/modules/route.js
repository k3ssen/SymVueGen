export default {
    state: {
        pageUrl: ''
    },
    getters: {
        pageUrl() {
            return state.pageUrl
        }
    },
    mutations: {
        setPageUrl (state, url) {
            state.pageUrl = url;
        }
    }
};