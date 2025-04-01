<template>
  <div class="home-page">
    <v-row >
        <v-col >
          
            

              <v-card variant="elevated" class="mx-auto">
                <v-card-title class="text-primary">This is a title</v-card-title>
                <v-card-subtitle class="text-secondary">This is a subtitle</v-card-subtitle>
                <v-card-text>
                  This is content
                </v-card-text>
                <v-card-actions>
                  
                  <v-container>
                    <v-row align="center" justify="center">
                      <v-col cols="auto">
                        <v-btn prepend-icon="$vuetify" variant="flat" elevation="4" color="primary" size="x-small"
                        :disabled="loading"
                        :loading="loading"
                        block
                        @click="loading = !loading"
                        >Extra small Button</v-btn>
                      </v-col>

                      

                      <v-col cols="auto">

                    
                        <VBtnPrimary
                          append-icon="$vuetify" size="small" elevation="3"
                          :disabled="loading"
                          :loading="loading"
                          block
                          @click="loading = !loading"
                          >Small Button
                        </VBtnPrimary>
                        <v-spacer></v-spacer>
                        <VBtnSecondary
                          append-icon="$vuetify" size="small" elevation="3"
                          :disabled="loading"
                          :loading="loading"
                          block
                          @click="loading = !loading" class="mx-0"
                          >Small Button
                        </VBtnSecondary>
                        <v-spacer></v-spacer>
                        <VBtnTertiary
                          append-icon="$vuetify" size="small" elevation="3" class="mx-0"
                          :disabled="loading"
                          :loading="loading"
                          block
                          @click="loading = !loading"
                          >Small Button
                        </VBtnTertiary>
                      </v-col>

                      <v-col cols="auto">
                        <v-btn 
                        :disabled="loading"
                        :loading="loading"
                        color="primary"
                        variant="flat"
                        block
                        @click="loading = !loading"
                        >Regular Button</v-btn>
                      </v-col>

                      <v-col cols="auto">
                        <v-btn size="large"
                        :disabled="loading"
                        :loading="loading"
                        color="secondary"
                        variant="flat"
                        block
                        @click="loading = !loading"
                        >Large Button</v-btn>
                      </v-col>

                      <v-col cols="auto">
                        <v-btn size="x-large"
                        :disabled="loading"
                        :loading="loading"
                        color="primary"
                        variant="flat"
                        block
                        @click="loading = !loading"
                        >X-Large Button</v-btn>
                      </v-col>
                    </v-row>

                    <v-row>
                      <v-col>
                        <VInputTrz label="Text field"></VInputTrz>
                        <VSelectTrz label="Select field"></VSelectTrz>
                      </v-col>
                      <v-col>
                        <VTextareaTrz label="Text area"></VTextareaTrz>
                      </v-col>
                      <v-col></v-col>
                    </v-row>
                  </v-container>
                
              </v-card-actions>
              </v-card>
              </v-col>
              </v-row>
              <v-row>  
              <v-col>
              <v-card variant="flat" class="mx-auto mt-4">
                <v-card-title class="text-primary">This is a title</v-card-title>
                <v-card-subtitle class="text-secondary">This is a subtitle</v-card-subtitle>
                <v-card-text>
                  This is content
                </v-card-text>
                <v-card-actions>
                  <VBtnPrimary
                  append-icon="$vuetify" size="small" elevation="3"
                  :disabled="loading"
                  :loading="loading"
                  block
                  @click="loading = !loading"
                  >Small Button
                  </VBtnPrimary>
              </v-card-actions>
              </v-card>
              </v-col>
              <v-col>
              <v-card variant="tonal" class="mx-auto mt-4">
                <v-card-title class="text-primary">This is a title</v-card-title>
                <v-card-subtitle class="text-secondary">This is a subtitle</v-card-subtitle>
                <v-card-text>
                  This is content
                </v-card-text>
                <v-card-actions>
                  <VBtnSecondary
                  append-icon="$vuetify" size="small" elevation="3"
                  :disabled="loading"
                  :loading="loading"
                  block
                  @click="loading = !loading"
                  >Small Button
                  </VBtnSecondary>
              </v-card-actions>
              </v-card>
              </v-col>
              </v-row>
              <v-row>  
              <v-col>
              <v-card variant="outlined" class="mx-auto mt-4">
                <v-card-title class="text-primary">This is a title</v-card-title>
                <v-card-subtitle class="text-secondary">This is a subtitle</v-card-subtitle>
                <v-card-text>
                  This is content
                </v-card-text>
                <v-card-actions >
                  <VBtnTertiary
                  append-icon="$vuetify" size="small" elevation="3"
                  :disabled="loading"
                  :loading="loading"
                  block
                  @click="loading = !loading"
                  >Small Button
                  </VBtnTertiary>
              </v-card-actions>
              </v-card>
        </v-col>
    </v-row>
  <v-row>
    <v-col>
      <v-card variant="outlined" class="mx-auto mt-4">
      <v-card-title class="text-primary">This is a title</v-card-title>
      <v-card-subtitle class="text-secondary">This is a subtitle</v-card-subtitle>
      <v-card-text>
        This is content
      </v-card-text>
      <v-card-actions>
        <VBtnSecondary
        append-icon="$vuetify" size="small" elevation="3"
        :disabled="loading"
        :loading="loading"
        block
        @click="loading = !loading"
        >Small Button
        </VBtnSecondary>
    </v-card-actions>
    </v-card>
    </v-col>
    <v-col>
        <v-card class="mx-auto mt-4">
      
        <v-card-title class="text-primary">This is a title</v-card-title>
        <v-card-subtitle class="text-secondary">This is a subtitle</v-card-subtitle>
        <v-card-text>
          This is content
        </v-card-text>
        <v-card-actions>
          <VBtnSecondary
          append-icon="$vuetify" size="small" elevation="3"
          :disabled="loading"
          :loading="loading"
          block
          @click="loading = !loading"
          >Small Button
          </VBtnSecondary>
      </v-card-actions>
      </v-card>

    </v-col>
    <v-col>
      <v-card variant="tonal" class="mx-auto mt-4">
     
      <v-card-title class="text-primary">This is a title</v-card-title>
      <v-card-subtitle class="text-secondary">This is a subtitle</v-card-subtitle>
      <v-card-text>
        This is content
      </v-card-text>
      <v-card-actions>
        <VBtnSecondary
        append-icon="$vuetify" size="small" elevation="3"
        :disabled="loading"
        :loading="loading"
        block
        @click="loading = !loading"
        >Small Button
        </VBtnSecondary>
    </v-card-actions>
    </v-card>
    </v-col>
  </v-row>
  </div>
</template>
<script setup>
 
</script>

<script>
  
    export default {

      data() {
        
          return {   
            loading: false,
          }
      },
        methods: {         
          
                
          
            
        },
        watch: {
          loading (val) {
            if (!val) return

            setTimeout(() => (this.loading = false), 2000)
          },
        },
        created() {      
            
        },
        
    }
</script>