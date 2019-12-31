<template>
    <v-app id="inspire">
        <v-navigation-drawer v-model="drawer" app clipped>
            <component :is="vueMenu"></component>
        </v-navigation-drawer>

        <v-app-bar app color="primary" dark clipped-left>
            <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
            <v-toolbar-title>Application</v-toolbar-title>
        </v-app-bar>

        <v-content>
            <v-container>
                <component :is="vuePage"></component>
            </v-container>
        </v-content>
    </v-app>
</template>

<script>
    export default {
        data: () => ({
            drawer: null,
            vueMenu: vueMenu,
            vuePage: vue,
        }),
        mounted() {
            this.$store.watch((state) => state.route.pageUrl, this.loadPage);
        },
        methods: {
            async loadPage(url) {
                // Add vue=1 parameter. This can be used to decide that only vue content should be fetched, but it also
                // prevents that the back button will fetch vue-content instead of the whole page due to caching
                url += url.includes('?') ? '&vue=1' : '?vue=1';
                const pageResult = await fetch(url);
                this.vuePage = (new Function(
                    (await pageResult.text()).replace(/<\/?script([^a-zA-Z>]?)([^>]*)>/g, '') + '; return vue;'
                ))();
            }
        }
    };
</script>
