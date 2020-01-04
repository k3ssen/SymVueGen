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
        }),
        computed: {
            vuePage() {
                return this.$store.vuePage;
            }
        }
    };
</script>
