<template>
    <v-card flat class="pa-4 bg-grey">
        <span>Settings</span>
    </v-card>
    <v-card flat class="mt-10 bg-grey text-secondary">
        <v-tabs v-model="currentItem">
            <div v-for="(item, index) in items" :key="index">
                <v-tab :key="item" :value="item" color="secondary">
                    <span class="text-700">{{ item }}</span>
                </v-tab>
                <span
                    v-if="index !== items.length - 1"
                    class="text-none text-14 font-weight-medium tab-text pa-2 nav-separator"
                >
                    |
                </span>
            </div>
        </v-tabs>
    </v-card>
    <v-window v-model="currentItem">
        <v-window-item key="Users" value="Users">
            <Users />
        </v-window-item>
        <v-window-item
            key="Countries & Currencies"
            value="Countries & Currencies"
        >
            <CountryAndCurrencies />
        </v-window-item>
        <v-window-item key="Market Segmentation" value="Market Segmentation">
            <MarketSegmentation />
        </v-window-item>
        <v-window-item
            key="Sales Terms & Statuses"
            value="Sales Terms & Statuses"
        >
            <SalesTermsStatuses />
        </v-window-item>
        <v-window-item key="Contact Types" value="Contact Types">
            <ContactTypes />
        </v-window-item>
        <v-window-item key="Unit" value="Unit">
            <Unit />
        </v-window-item>
        <v-window-item key="Shipment" value="Shipment">
            <Shipment />
        </v-window-item>
        <v-window-item
            key="Customer / Supplier Type"
            value="Customer / Supplier Type"
        >
            <CustomerSupplierType />
        </v-window-item>
        <v-window-item key="Industry" value="Industry">
            <Industry />
        </v-window-item>
    </v-window>
</template>

<script setup>
import Users from "@/admin/settings/Users.vue";
import CountryAndCurrencies from "@/admin/settings/CountryAndCurrencies.vue";
import MarketSegmentation from "@/admin/settings/MarketSegmentation.vue";
import Unit from "@/admin/settings/Unit.vue";
import SalesTermsStatuses from "@/admin/settings/SalesTermsStatuses.vue";
import ContactTypes from "@/admin/settings/ContactTypes.vue";
import Shipment from "@/admin/settings/Shipment.vue";
import CustomerSupplierType from "@/admin/settings/CustomerSupplierType.vue";
import Industry from "@/admin/settings/Industry.vue";

document.title = "Settings";
</script>

<script>
export default {
    data() {
        return {
            currentItem: "Users",
            items: [
                "Users",
                "Countries & Currencies",
                "Market Segmentation",
                "Sales Terms & Statuses",
                "Contact Types",
                "Unit",
                "Shipment",
                "Customer / Supplier Type",
                "Industry",
            ],
        };
    },
    methods: {},
    created() {},
};
</script>
