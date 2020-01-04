// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.css');

import Vue from 'vue';
import App from './App.vue';
import Vuetify from 'vuetify/lib';

Vue.use(Vuetify);
const vuetify = new Vuetify();

require('./globalComponents');
require('./globalStoreAndMixin');


new Vue({
    el: '#app',
    vuetify,
    render: h => h(App)
});
