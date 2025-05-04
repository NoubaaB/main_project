<template>
    <SwipeModal
        v-model="main.command_drawer"
        :is-backdrop="false"
    >
    <template v-slot:drag-handle>
      <div class="text-center mt-2" @click.stop="main.command_drawer = !main.command_drawer">
        <v-icon size="large" :color="main.is_dark?'white':'black'">
          {{main.command_drawer ? "mdi-chevron-down":"mdi-chevron-up"}}
        </v-icon>

      </div>
    </template>

        <v-card
            class="mx-auto mt-5"
            min-height="350"
            variant="flat"
            style="position: relative;"
            >
            <CostumBtn 
            :name="'add_command'" 
            :action="add_command" 
            :allow_slot="false"
            :params="null"
            :isEnabled="false" 
            :isRestricted="false"
            :loading="false"
            :disabled="send_load"
            :icon="'mdi-plus'"
            :class="'bg-red fab-command'"
            />

            <CostumBtn 
            :name="'voice_command'" 
            :action="voice_command" 
            :allow_slot="false"
            :params="null"
            :isEnabled="false" 
            :isRestricted="false"
            :loading="false"
            :disabled="send_load"
            :icon="'mdi-microphone'"
            :class="'bg-amber fab-record ' + (audio_active ?'circle-ripple-record' : '')"
            />

            <CostumBtn 
            :name="'send_load'" 
            :action="sendCommand" 
            :allow_slot="false"
            :params="null"
            :isEnabled="false" 
            :isRestricted="false"
            :loading="send_load"
            :disabled="send_load"
            :icon="'mdi-send'"
            :class="'bg-blue fab-send'"
            />

            <CostumBtn 
            :name="'fab_speed_dial'" 
            :action="null" 
            :allow_slot="true"
            :params="null"
            :isEnabled="false" 
            :isRestricted="false"
            :loading="false"
            :disabled="false"
            :icon="'mdi-send'"
            :class="'fab-speed-dial'"
            >
                <template #header>
                    <v-speed-dial
                    location="right center"
                    transition="fade-transition"
                    >
                        <template v-slot:activator="{ props: activatorProps }">
                            <v-fab
                            v-bind="activatorProps"
                            color="deep-orange"
                            icon="mdi-auto-fix"
                            class="fab-speed-dial"
                            ></v-fab>
                        </template>

                        <v-btn v-show="main.command_drawer" color="red" class="absolute_index" key="1" icon="mdi-delete-sweep" @click="deleteAllCommands"></v-btn>
                        <v-btn v-show="main.command_drawer" color="primary" class="absolute_index" key="2" icon="mdi-delete-restore" @click="restoreAllDeletedCommands"></v-btn>
                        <v-btn v-show="main.command_drawer" color="orange-darken-1" class="absolute_index" key="3" icon="mdi-delete-clock" @click="clearRestore"></v-btn>
                    </v-speed-dial>
                </template>
            </CostumBtn>
            
            <v-divider ></v-divider>
            <div v-if="command.commands.length > 0">
                <v-row>
                    <v-col cols="1">
                        <v-checkbox
                            v-model="is_paid"
                            color="info"
                            name="is_paid"
                            id="is_paid"
                            hide-details
                        ></v-checkbox>
                    </v-col>
                    <v-col class="text-center mt-2">
                        <input v-model="date_command_global" :max="date_command_max" type="datetime-local" name="time_global" id="time_global">
                    </v-col>
                </v-row>
                <v-sheet
                    rounded="lg"
                    :elevation="0"
                    v-for="cmd in command.commands"
                    :key="cmd.id"
                >
                    <CommandItem  
                    :cmd="cmd"
                    :date_command_max="date_command_max"
                    :_deleteCommand="deleteCommand"
                    direction="right"
                    />
                </v-sheet>
            </div>
            <div v-else>
                <CommandItemPlaceHolder :add_command="add_command"/>
            </div>
        </v-card>
        <v-dialog
        v-model="command.dialog_client"
        width="300"
        z-index="99999"
        >
            <v-card
                class="pa-5"
                rounded="xl"
            >
                <v-autocomplete
                v-model="command.cmd.client_id"
                autofocus
                @update:model-value="command.dialog_client = false"
                @keydown.prevent.enter="command.addClient"
                id="id"
                class="d-inline"
                hide-no-data
                :items="client.getMinList"
                item-value="id"
                auto-select-first
                item-title="name"
                flat
                :name="`select-item${command.cmd.client_id}`"
                variant="solo-filled"
                :hint="$t('select_client')"
                :placeholder="$t('select_client')"
                rounded
                chips
                hide-details
                hide-selected
                closable-chips
                menu-icon=""
                :menu-props="{ zIndex: 99999999 }"
                >
                </v-autocomplete>
                <!-- <select v-else 
                        name="clients" 
                        v-model="command.cmd.client_id" 
                        @change="command.dialog_client = false">
                    <option 
                        v-for="cl in client.getMinList" 
                        v-bind:value="cl.id" 
                        :key="cl.id">{{cl.name}}
                    </option>
                </select> -->
                <v-btn 
                v-if="false"
                block 
                color="primary" 
                rounded 
                class="mt-3" 
                @click="command.dialog_client = !command.dialog_client">
                    {{$t('ok')}}
                </v-btn>
            </v-card>
        </v-dialog>
    </SwipeModal>
    <v-overlay
        v-model="overlay_record"
        class="align-center justify-center"
        :close-delay="100"
    >
        <v-img
        :width="150"
        aspect-ratio="16/9"
        cover
        :src="overlay_src"
        class="ma-5 mx-auto"
        ></v-img>
        <v-btn
        :color="overlay_color"
        @click="overlay_record = false"
        rounded
        >
        {{overlay_text}}
        </v-btn>
    </v-overlay>
</template>
<script>
import { SwipeModal } from "@takuma-ru/vue-swipe-modal";
import { useMain } from '@/stores/Main.js';
import { useCommand } from '@/stores/Command.js';
import { useClient } from '@/stores/Client.js';
import annyang from "annyang";
import CommandItem from "./plugins/CommandItem.vue";
import CostumBtn from "./plugins/CostumBtn.vue";
import CommandItemPlaceHolder from "./plugins/CommandItemPlaceHolder.vue";
import moment from "moment";

export default {
  data() {
    return {
        overlay_record:false,
        overlay_src:"",
        overlay_color:"",
        overlay_text:"",
        date_command_global: moment().format('YYYY-MM-DDThh:mm'),
        date_command_max:moment().format('YYYY-MM-DDThh:mm'),
        is_paid:false,
        audio_active:false,
        send_load:false
    }
  },
  mounted: function () {
      this.initAnnyang();
      console.log(this.$vuetify.display)
  },
  computed: {
    main: function() {
      return useMain()
    },
    color:function(){
      return useMain().is_dark ? "#212121" : "white"
    },
    command:function(){
        return useCommand();
    },
    client:function(){
        return useClient();
    }
  },
  methods: {
    animation: function () {
        setTimeout(() => {
          this.command.setAnimationtoDefault();
        }, 4000);
    },
    initAnnyang: function () {
        var commands = {
            'ajouter *val': this.addNewCommand,
            'supprimer le dernier': this.deleteLatestCommand,
            'supprimer client :name': this.deleteCommandByClient,
            'supprimer tout': this.deleteAllCommands,
            'restaurer': this.restoreAllDeletedCommands,
            'restaurer client :name': this.restorCommandByClient,
            'arrêter': this.stopVoiceCommand,
            'fermer': this.closeDrawer,
            'obtiner la facture :matricule': this.getBill,
        };
        annyang.addCommands(commands);
        annyang.addCallback('resultNoMatch', (userSaid, commandText, phrases) => {
            this.overlay_text = this.$t("record_added_failed");
            this.overlay_color = "red";
            this.overlay_src = "/storage/images/microphone_mute.png";
            this.overlay_record = true;
            setTimeout(() => {
                this.overlay_record = false;
            }, 2000);
            console.log("resultNoMatch");
            console.log(userSaid); // sample output: 'hello'
            console.log(commandText); // sample output: 'hello (there)'
            console.log(phrases); // sample output: ['hello', 'halo', 'yellow', 'polo', 'hello kitty']
        });
        annyang.addCallback('resultMatch', (userSaid, commandText, phrases) => {
            this.overlay_text = this.$t("record_added_success");
            this.overlay_color="success";
            this.overlay_src = "/storage/images/microphone_sound.png";
            this.overlay_record = true;
            setTimeout(() => {
                this.overlay_record = false;
            }, 2000);
            console.log("resultMatch",this.overlay_record);
            console.log(userSaid); // sample output: 'hello'
            console.log(commandText); // sample output: 'hello (there)'
            console.log(phrases); // sample output: ['hello', 'halo', 'yellow', 'polo', 'hello kitty']
        });      
    },
    deleteAllCommands: function () {
        if (this.command.commands.length>0) {
            this.command.deleteAllCommands();    
            this.command.snackbar_action_command = true;
            this.command.snackbar_action_command_color = "text-red";
            this.command.snackbar_action_command_icon_color = "red";
            this.command.snackbar_action_command_icon = "mdi-delete-alert";
            this.command.snackbar_action_command_text = this.$t("command_deleted_all");
        } else {
            this.lengthEmpty("nothing_command_deleted_all");
        }
      },
    start : function(){
        console.log("annyang start")
        annyang.start();
        this.command.snackbar_action_command = true;
        this.command.snackbar_action_command_color = "text-green";
        this.command.snackbar_action_command_icon_color = "green";
        this.command.snackbar_action_command_icon = "mdi-microphone";
        this.command.snackbar_action_command_text = this.$t("command_voice_start");
    },
    stopVoiceCommand : function(){
        console.log("annyang end")
        annyang.abort();
        this.command.snackbar_action_command = true;
        this.command.snackbar_action_command_color = "text-red";
        this.command.snackbar_action_command_icon_color = "red";
        this.command.snackbar_action_command_icon = "mdi-microphone-off";
        this.command.snackbar_action_command_text = this.$t("command_voice_stop");
        this.audio_active = false;
    },
    closeDrawer: function () {
        this.main.command_drawer = !this.main.command_drawer
    },
    getBill: function (matricule) {
        let bill = this.command.getBill(matricule);

    },
    restorCommandByClient: function (client) {
        let client_id = client ? this.command.filterCloseNames(this.client.getMinList, client) : null;
        if (client_id != null) {
            let client_name = this.command.gtetClientName(client_id);
            this.command.restorCommandByClient(client_id);    
            this.command.snackbar_action_command = true;
            this.command.snackbar_action_command_color = "text-green";
            this.command.snackbar_action_command_icon_color = "green";
            this.command.snackbar_action_command_icon = "mdi-delete-restore";
            this.command.snackbar_action_command_text = this.$t("command_restore_by_client",{client:client_name});
        } else {
            this.lengthEmpty("nothing_command_to_restore_by_client",{client:client});
        }
    },
    restoreAllDeletedCommands: function () {
        if (this.command.deleted_commands.length>0) {
            this.command.restore();    
            this.command.snackbar_action_command = true;
            this.command.snackbar_action_command_color = "text-green";
            this.command.snackbar_action_command_icon_color = "green";
            this.command.snackbar_action_command_icon = "mdi-delete-restore";
            this.command.snackbar_action_command_text = this.$t("command_restore_all");
        } else {
            this.lengthEmpty("nothing_command_to_restore");
        }
    },
    clearRestore: function () {
        if (this.command.deleted_commands.length>0) {
            this.command.clear_restore();    
            this.command.snackbar_action_command = true;
            this.command.snackbar_action_command_color = "text-red";
            this.command.snackbar_action_command_icon_color = "red";
            this.command.snackbar_action_command_icon = "mdi-delete-clock";
            this.command.snackbar_action_command_text = this.$t("command_clear_restore");
        } else {
            this.lengthEmpty("nothing_command_to_clear");
        }
    },
    lengthEmpty: function (text , params = {}) {
        this.command.snackbar_action_command = true;
        this.command.snackbar_action_command_color = "text-red";
        this.command.snackbar_action_command_icon_color = "yellow";
        this.command.snackbar_action_command_icon = "mdi-alert";
        this.command.snackbar_action_command_text = this.$t(text,params);    
    },
    deleteCommand:async function(client){
        this.command.snackbar_action_command = true;
        this.command.snackbar_action_command_color = "text-red";
        this.command.snackbar_action_command_icon_color = "red";
        this.command.snackbar_action_command_icon = "mdi-delete";
        this.command.snackbar_action_command_text = this.$t("one_command_deleted",{client:client});
    },
    sendCommand: async function () {
        if (this.command.commands.length == 0) {
            this.command.snackbar_action_command = true;
            this.command.snackbar_action_command_color = "text-red";
            this.command.snackbar_action_command_icon_color = "red";
            this.command.snackbar_action_command_icon = "mdi-cart-heart";
            this.command.snackbar_action_command_text = this.$t("command_cart_is_empty");
            return 0
          }
        
        this.send_load=true;
        await this.command.post().then(res=>{
            if (res == false) {
                this.command.snackbar_action_command = true;
                this.command.snackbar_action_command_color = "text-red";
                this.command.snackbar_action_command_icon_color = "red";
                this.command.snackbar_action_command_icon = "mdi-shield-alert";
                this.command.snackbar_action_command_text = this.$t("please_select_items");
            }
            this.send_load=false;
        });
    },
    add_command: function(){
        this.command.add();
        this.command.snackbar_action_command = true;
        this.command.snackbar_action_command_color = "text-green";
        this.command.snackbar_action_command_icon_color = "green";
        this.command.snackbar_action_command_icon = "mdi-plus-thick";
        this.command.snackbar_action_command_text = this.$t("one_command_added");
    },
    voice_command: function () {
        this.audio_active = !this.audio_active;
        if(this.audio_active){
            this.start()
        }else{
            this.stopVoiceCommand();
        }
    },
    deleteLatestCommand:function(){
        let cmd = this.command.commands[this.command.commands_count - 1];
        let client_id = cmd.client_id;
        this.command.delete(cmd);
        this.deleteCommand(client_id == null ? this.$t('divers') : this.command.gtetClientName(client_id));

    },
    addNewCommand: function (val) {
        let [client, item, qte ] = val.split(" ")
        this.command.add(client, item, qte)
    },
    deleteCommandByClient: function (val) {
        this.command.deleteCommandByClient(val)
    }
  },
  validations: function () {
      return {
      };
  },
  watch: {
    "command.commands": {
        deep: true,
        handler: function (val) {
            this.command.delaySetCartStorage()
        }
    },
    "main.command_drawer": function(val) {
        if(val){
            this.animation();
        } else if(this.audio_active) {
            this.voice_command();
            setTimeout(()=>{
                if(!this.main.command_drawer){
                    this.command.clear_restore();
                }
            },5000)
        }
    },
    date_command_global: function (val) {
        this.command.commands.forEach(command => {
            command.date = val
        });
    },
    is_paid: function (val) {
        this.command.commands.forEach(command => {
            command.is_paid = val
        });
    }
  },
  components: {
    SwipeModal,
    CommandItem,
    CostumBtn,
    CommandItemPlaceHolder
  }
}
</script>

<style lang="scss" scoped >
dialog{
  z-index: 1500 !important;
}
.modal-style{
  background-color: v-bind(color) !important;
}
.swipe-modal-content{
  background-color: v-bind(color) !important;
}
.swipe-modal[data-v-8a63990a]::backdrop{
    background-color: #00000080 !important;
    -webkit-backdrop-filter: blur(0px) !important;
    backdrop-filter: blur(0px) !important;
}
button:focus,
input:focus {
    outline: 0;
}


</style>
