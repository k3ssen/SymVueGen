import Vue from 'vue';
import Vuex from 'vuex';
import route from './modules/route';

Vue.use(Vuex);

const debug = process.env.NODE_ENV !== 'production';

export default new Vuex.Store({
    modules: {
        route,
    },
    strict: debug,
    // plugins: debug ? [createLogger()] : []
})