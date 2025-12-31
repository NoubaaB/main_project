<template>
  <v-app>
    <v-main class="d-flex flex-column">
      
      <!-- Chat Messages Area -->
      <v-container class="flex-grow-1 overflow-auto rounded-xl" :style="{ backgroundColor: main.is_dark ? '#010101' : '#f5f5f5' }">
        <v-row dense>
          <v-col
            v-for="(msg, index) in ai_prompt.messages"
            :key="index"
            cols="12"
            :class="msg.sender !== 'AI' ? 'text-right' : 'text-left'"
          >
            <template v-if="msg.sender !== 'AI'">
              <v-chip
                :color="msg.sender !== 'AI' ? 'blue lighten-3' : 'grey lighten-2'"
                class="ma-2"
              >
              {{ msg.text }}
              </v-chip>
            </template>
            <template v-else>
              <AiMessageBox :msg="msg" :index="index" />
            </template>
          </v-col>
        </v-row>
      </v-container>

      <!-- Input Area -->
      <v-container class="py-2 px-0">
        <v-row no-gutters align="center">
          <v-col cols="12">
            <v-text-field
                v-model="newMessage"
                :label="$t('type_a_message')"
                outlined
                dense
                variant="solo-filled"
                rounded
                hide-details
                @keyup.enter="sendMessage"
                :disabled="loading"
            >
              <template #append-inner>
                <v-btn
                  v-if="loading"
                  :loading="loading"
                  icon="mdi-send"
                  variant="tonal"
                  readonly
                  color="primary" 
                >
                </v-btn>
                <v-btn 
                  v-else
                  @click="sendMessage" 
                  color="primary" 
                  icon="mdi-send" 
                  variant="tonal" 
                  size="small"
                ></v-btn>
              </template>
            </v-text-field>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
import {useAIPrompt} from "@/stores/AIPrompt";
import {useMain} from "@/stores/Main";
import AiMessageBox from "@/components/ai_prompt/AiMessageBox.vue";
export default {
  components: {
    AiMessageBox
  },
  data : function() {
    return {
      newMessage: "",
      DynamicComponent: null,
      loading:false
    };
  },
  computed: {
    ai_prompt : function(){
      return useAIPrompt();
    },
    main:function(){
      return useMain();
    },
  },
  methods: {
    sendMessage : async function() {
        if (!this.newMessage.trim()) return;
        this.loading = true;
        let message = this.newMessage;
        await this.ai_prompt.sendMessage(message)
        .then(res=>{
            // Clear input
            if(res?.answer){
              // loadExternalContent(res.answer,`_ai_message_${this.ai_prompt.messages.length-1}`);
              this.newMessage = "";
            }
            this.loading = false;
        }).catch(err=>{
            console.error("Error sending message:", err);
            this.loading = false;
        });
    }
  },
};
</script>
