<template>
    <EasyDataTable
        v-bind="$attrs"
        table-class-name="tt-edt"
        :theme-color="themeColor"
        header-text-direction="left"
        body-text-direction="left"
        buttons-pagination
        :rows-items="[10, 25, 50, 100]"
        :rows-per-page-message="$t('Rows per page')"
        :rows-of-page-separator-message="$t('of')"
        :empty-message="$t('Nothing to show')"
        :hide-footer="hideFooter"
    >
        <template v-for="(_, name) in $slots" #[name]="scope">
            <slot :name="name" v-bind="scope || {}" />
        </template>
        <template v-if="!$slots.loading" #loading>
            <v-progress-circular indeterminate color="primary" size="28" width="3" />
        </template>
    </EasyDataTable>
</template>

<script>
/*
 * The one table used across the admin: vue3-easy-data-table with the store's look and translated labels.
 * Everything else passes straight through, so EasyDataTable's own props, events and slots all work:
 *
 *   <DataTable :headers="headers" :items="items" :loading="loading" />                     (paged in the browser)
 *   <DataTable v-model:server-options="opts" :server-items-length="total" ... />          (paged by the API)
 *   <template #item-name="row"> ... </template>                                           (custom cell)
 *
 * headers: [{ text: "Product", value: "name", sortable: true, width: 280 }]  (text is translated by the page)
 */
import Vue3EasyDataTable from "vue3-easy-data-table";

export default {
    name: "DataTable",
    components: { EasyDataTable: Vue3EasyDataTable },
    inheritAttrs: false,
    props: {
        hideFooter: { type: Boolean, default: false },
    },
    computed: {
        themeColor() {
            return this.$vuetify.theme.current.colors.primary;
        },
    },
};
</script>
