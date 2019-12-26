// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.css');

import Vue from 'vue';
import App from './App.vue';
import Vuetify from 'vuetify/lib';
import store from './store';

Vue.use(Vuetify);
const vuetify = new Vuetify();

require('./globalComponents');
require('./globalMixin');

new Vue({
    el: '#app',
    vuetify,
    store,
    render: h => h(App)
});
