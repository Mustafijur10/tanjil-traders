<template>
  
    <div class="list-page ma-5">
      <v-row>
          <v-col cols="4">
            <v-row class="no-gutters">
              <v-col cols="4" class="d-flex align-center pr-0">
                <div class="text-h4 text-greymain font-weight-bold">Orders</div>
              </v-col>
              <v-col cols="6" class="d-flex align-center pl-0">
                <router-link to="/admin/order" class="text-decoration-none">
                    <v-btn 
                        prepend-icon="mdi-plus"  
                        elevation="3"
                        :disabled="loading"
                        :loading="loading"
                        rounded="lg"
                        color="primary"
                        block
                        @click="loading = !loading"
                        class="text-body-2 font-weight-bold"
                        >Create an Order
                    </v-btn>
                </router-link>
              </v-col>
            </v-row>
          </v-col>
      </v-row>
  
      <v-row>       
          <v-col>
            <v-card flat class="rounded-lg mb-9" >
            <v-card-text >
              <v-row>
                <v-col class="text-center text-body-1 text-greymain font-weight-medium">New</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-medium">In Production</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-medium">For Delivery</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-medium">In Transit</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-medium">Payment</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-medium">Documents</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-medium">Shipment</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-medium">Complaint</v-col>
              </v-row>
              <v-row>
                <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
                <v-col class="text-center text-body-1 text-success font-weight-bold">200</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
                <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
                <v-col class="text-center text-body-1 text-orange font-weight-bold">02</v-col>
              </v-row>
            </v-card-text>
            </v-card>
            <v-card flat class="pa-6 mt-5 rounded-lg">
                <v-row class="mb-4">
                    <v-col cols="1" class="d-flex align-center pr-0">
                        <div class="text-h6 text-greymain font-weight-bold">Filters</div>
                    </v-col>
                    <v-col cols="" class="my-auto">
                        <v-btn class="bg-white text-secondary rounded-border text-body-2 font-weight-medium" variant="outlined" size="small" @click="clearFilter()">
                            Clean Filters
                        </v-btn>
                    </v-col>
                </v-row>
              <v-row>
                <v-col cols="2">
                  <v-select
                      density="compact"
                      variant="outlined"
                      placeholder="All Status"
                      v-model="search_status"
                      :items="allStatus"
                      item-title="text"
                      item-value="text"
                      class="rounded"
                  ></v-select>
                </v-col>

                <v-col cols="">
                  <v-select
                      density="compact"
                      variant="outlined"
                      placeholder="All Customers"
                      v-model="search_customer"
                      :items="allCustomers"
                      item-title="text"
                      item-value="text"
                      class="rounded"
                  ></v-select>
                </v-col>
                
                <v-col cols="">                 
                    <v-select
                        density="compact"
                        variant="outlined"
                        placeholder="All Suppliers"
                        v-model="search_Supplier"
                        :items="allSuppliers"
                        item-title="text"
                        item-value="text"
                        class="rounded"
                    ></v-select>
                </v-col>
                
                <v-col cols="2">
                  <v-text-field
                      clearable
                      variant="outlined"
                      density="compact"
                      class="rounded"
                      v-model="searchByKeyword"
                      @click:clear="searchByKeyword = ''"
                      placeholder="Keyword"
                  >
                  </v-text-field>
                </v-col>

                <v-col cols="">
                    <v-text-field clearable variant="outlined" v-model="selectedDate" type="date" density="compact" placeholder="Date Range">
                    </v-text-field>
                </v-col>     
              </v-row>

              <v-row class="mb-5">
                <v-col>
                    <div class="d-flex flex-column align-center">
                        <v-btn-toggle v-model="toggle" color="white bg-secondary" class="bg-secondary_lite rounded-lg text-greymain border-secondary" variant="outlined" divided >
                            <v-btn value="left" @click="openOrder" class="text-body-1"> Open Orders (9000)</v-btn>
                            <v-btn value="center" @click="draft" class="text-body-1"> Draft (10)</v-btn>
                            <v-btn value="right" @click="allOrder" class="text-body-1"> All Orders (10000)</v-btn>
                        </v-btn-toggle>
                    </div>
                </v-col>
                
                <v-col cols="2" class="my-auto text-right">
                    <v-btn icon="mdi-share-outline" class="text-secondary bg-secondary_lite" variant="text"></v-btn>
                    <v-btn icon="mdi-tray-arrow-down" class="ms-5 text-secondary bg-secondary_lite" variant="text"></v-btn>
                </v-col>
              </v-row>

            <v-window v-model="tab">
                <v-window-item value="open-order">
                    <EasyDataTable
                        :headers="headers"
                        :items="orders"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="5"
                        :fixedCheckbox="true"
                        :fixedIndex="true"
                        :search-value="searchValue"
                        class="mt-5 border-0"
                    >   
                        <template #header-date="header"><div class="text-greyheader text-body-1">{{ header.text }}</div></template>
                        <template #header-order="header"><div class="text-greyheader text-body-1">{{ header.text }}</div></template>
                        <template #header-pi="header"><div class="text-greyheader text-body-1">{{ header.text }}</div></template>
                        <template #header-customer="header"><div class="text-greyheader text-body-1">{{ header.text }}</div></template>
                        <template #header-supplier="header"><div class="text-greyheader text-body-1">{{ header.text }}</div></template>
                        <template #header-item="header"><div class="text-greyheader text-body-1">{{ header.text }}</div></template>
                        <template #header-status="header"><div class="text-greyheader text-body-1">{{ header.text }}</div></template>

                        <template #item-date="item"><div class="text-greymain text-body-2 my-5">{{ item.date }}</div></template>
                        <template #item-order="item"><div class="text-greymain text-body-2 my-5">00000000{{ item.order }}</div></template>
                        <template #item-pi="item"><div class="text-greymain text-body-2 my-5">00000000{{ item.pi }}</div></template>
                        <template #item-customer="item"><div class="text-greymain text-body-2 my-5">{{ item.customer }}</div></template>
                        <template #item-supplier="item"><div class="text-greymain text-body-2 my-5">{{ item.supplier }}</div></template>
                        <template #item-item="item"><div class="text-greymain text-body-2 my-5">{{ item.item }}</div></template>
                       
                        <template #item-status="item" >
                           <div v-if="item.status=='delivered'" class="text-success text-body-2 font-weight-medium text-center bg-success_lite rounded-xl py-1">Delivered</div>
                           <div v-else class="text-error text-body-2 font-weight-medium text-center">Pending</div>
                        </template>

                        <template #item-operation="item" >
                            <div class="text-right">
                                <v-btn class="text-secondary bg-secondary_lite" variant="raised" size="small" @click="openDialouge(item)">
                                    <v-icon icon="mdi-dots-horizontal" class="v-card-icon text-h4"></v-icon>
                                </v-btn>
                                <v-card class="rounded-lg mt-1 position-absolute" v-if="selectedItem.id == item.id" :min-width="180">
                                    <div class="text-start my-5">
                                        <span class="ms-4 cursor-pointer text-body-1 text-greymain">Duplicate</span>
                                    </div>
                                    <hr />
                                    <div class="text-start my-5">
                                        <span class="ms-4 cursor-pointer text-body-1 text-greymain">Download</span>
                                    </div>
                                    <hr />
                                    <div class="text-start my-5">
                                        <span class="ms-4 cursor-pointer text-body-1 text-greymain">Edit</span>
                                    </div>
                                    <hr />
                                    <div class="text-start my-5">
                                        <span class="ms-4 cursor-pointer text-error text-body-1">Delete Order</span>
                                    </div>
                                </v-card>
                            </div>
                        </template>
                    </EasyDataTable>
                </v-window-item>
                <v-window-item value="draft">
                    <EasyDataTable
                        :headers="headers"
                        :items="allSuppliers"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="10"
                        :fixedCheckbox="true"
                        :fixedIndex="true"
                        :search-value="searchValue"
                        class="mt-5 text-greymain"
                    >
                        <template #item-operation="item" >
                            <div class="text-right">
                                <v-btn class="ms-5 text-secondary bg-secondary_lite" variant="raised" size="small">
                                    <v-icon icon="mdi-dots-horizontal" class="v-card-icon" ></v-icon>
                                </v-btn>
                            </div>
                        </template>
                    </EasyDataTable>
                </v-window-item>
                <v-window-item value="all-order">
                    <EasyDataTable
                        :headers="headers"
                        :items="allStatus"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="10"
                        :fixedCheckbox="true"
                        :fixedIndex="true"
                        :search-value="searchValue"
                        class="mt-5"
                    >              
                        <template #item-operation="item" >
                            <div class="text-right">
                                <v-btn class="ms-5 text-secondary bg-secondary_lite" variant="raised" size="small">
                                    <v-icon icon="mdi-dots-horizontal" class="v-card-icon" ></v-icon>
                                </v-btn>
                            </div>
                        </template>
                    </EasyDataTable>
                </v-window-item>
            </v-window>
            </v-card>
            </v-col>
      </v-row>
    </div>
    {{ changeFilter }}
  </template>
  <script setup>
   document.title = "Orders";
  </script>
  
  <script>
    
      export default {
  
        data() {
          
            return {   
              selectedDate: null,
              searchValue:"",
              search_customer: null,
              search_status: null,
              search_Supplier: null,
              searchByKeyword: null,
              selectedItem: "",
             
              tab: "open-orders",
              loading: false,
              allStatus:[{text:'delivered',id:1},{text:'pending',id:2}],
              allCustomers:[{text:'customer 1',id:1},{text:'customer 2',id:2},{text:'customer 3',id:3}],
              allSuppliers:[{text:'supplier 1',id:1},{text:'supplier 2',id:2},{text:'supplier 3',id:3}],
              
              inputModel:'',
              orders: [],
             
              headers: [
                { text: "Date", value: "date", sortable: true },
                { text: "Order #", value: "order", sortable: true },
                { text: "PI #", value: "pi", sortable: true },
                { text: "Customer", value: "customer", sortable: true },
                { text: "Supplier", value: "supplier", sortable: true },
                { text: "Item", value: "item", sortable: true },
                { text: "Status", value: "status", sortable: true, width: 120 },
                { text: "", value: "operation", align:"end"},
                ],
            }
        },
          methods: {         
            draft(){
                this.tab = "draft";
                this.clearFilter();       
            }, 
            openOrder(){
                this.tab = "open-order";
                this.clearFilter();       
            } ,
            allOrder(){
                this.tab = "all-order";
                this.clearFilter();       
            },    
            List() {
                axios.get("/api/demo").then((response) => {
                    this.orders = response.data;
                });
            },
            clearFilter(){
                this.search_customer = null;
                this.search_status = null;
                this.search_Supplier = null;
                this.searchByKeyword = null;
                this.selectedDate = null;
                this.searchValue = ""; 
                this.selectedItem="";          
            },
            openDialouge(item) {
                this.selectedItem = item;
            },                
          },

          watch: {
            loading (val) {
              if (!val) return
  
              setTimeout(() => (this.loading = false), 2000)
            },
          },
          created() {      
           this.List();  
          },

          computed: {
            changeFilter() {
                if (this.search_customer) {
                    this.searchValue = this.search_customer;
                }
                if (this.search_status) {
                    this.searchValue = this.search_status;
                }             
                if (this.search_Supplier) {
                    this.searchValue = this.search_Supplier;
                }
                if (this.searchByKeyword) {
                    this.searchValue = this.searchByKeyword;
                }
            },
    },
          
      }
  </script>