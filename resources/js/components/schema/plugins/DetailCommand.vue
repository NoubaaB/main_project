<template>
    <tr
        class="text-center"
        height="90"
    >
        <td
            class="text-center"
            width="5"
        >
            <v-btn
                :disabled="
                    cmd.qte ==
                    1
                "
                tabindex="-1"
                variant="tonal"
                size="20"
                color="red"
                icon
                @click="
                    command.deleteCommandItem(
                        cmd,
                        command_item
                    )
                "
            >
                <v-icon
                    icon="mdi-close"
                    size="10"
                ></v-icon>
            </v-btn>
        </td>
        <td
            class="text-center"
        >
            <v-autocomplete
            v-model="command_item.item_id"
            :items="command.item.getMinList"
            item-value="id"
            item-title="name"
            :placeholder="$t('select_item')"
            :hint="$t('select_item')"
            auto-select-first
            flat
            :name="`select-item${command_item.id}`"
            variant="solo-filled"
            rounded
            chips
            hide-details
            hide-selected
            closable-chips
            menu-icon=""
            class="detail_command"
            :menu-props="{ zIndex: 9999999 }"
            >
            <template v-slot:chip="{ props, item }">
                <v-avatar
                v-bind="props"
                >
                <v-img :src="item.raw.image" cover></v-img>
            </v-avatar>
            </template>
            <template v-slot:item="{ props, item }">
                <v-list-item
                v-bind="props"
                :prepend-avatar="item.raw.image"
                ></v-list-item>
            </template>
            </v-autocomplete>
        </td>
        <td
            class="text-center"
        >
            <div
                class="text-caption text-blue font-weight-thin h6 d-block"
            >
                <strong>
                    {{
                        formatFloatNumber(command.calcTotalPriceCommandItem(
                            command_item
                        ))
                    }}
                </strong>
                MAD
            </div>
        </td>
        <td
            class="text-center d-sm-none"
        >
            <div
                class="qte_wrapper-detail"
            >
                <button
                    class="btn-detail btn--minus rounded-b-xl mt-1"
                    @click="
                        command.changeCounter(
                            command_item,
                            -1
                        )
                    "
                    type="button"
                    name="remove commandItem"
                    v-ripple
                >
                    <v-icon
                        >mdi-minus</v-icon
                    >
                </button>
                <input
                    class="quantity-detail font-weight-medium"
                    type="number"
                    disabled
                    :name="`counter_${command_item.id}`"
                    v-model="
                        command_item.qte
                    "
                    @input="
                        command.changeCounter(
                            command_item,
                            0
                        )
                    "
                    tabindex="-1"
                />
                <button
                    :disabled="
                        command_item.qte >=
                        command.MAX_QTE
                    "
                    class="btn-detail btn--plus rounded-t-xl mb-1"
                    @click="
                        command.changeCounter(
                            command_item,
                            +1
                        )
                    "
                    type="button"
                    name="add commandItem"
                    v-ripple
                >
                    <v-icon
                        >mdi-plus</v-icon
                    >
                </button>
            </div>
        </td>
        <td
            class="text-center hidden-xs"
        >
            <div
                class="qte_wrapper"
            >
                <button
                    class="btn btn--minus rounded-s-xl mr-1"
                    @click="
                        command.changeCounter(
                            command_item,
                            -1
                        )
                    "
                    type="button"
                    name="remove commandItem"
                    v-ripple
                >
                    <v-icon
                        >mdi-minus</v-icon
                    >
                </button>
                <input
                    class="quantity font-weight-medium"
                    type="number"
                    disabled
                    :name="`counter_${command_item.id}`"
                    v-model="
                        command_item.qte
                    "
                    @input="
                        command.changeCounter(
                            command_item,
                            0
                        )
                    "
                    tabindex="-1"
                />
                <button
                    :disabled="
                        command_item.qte >=
                        command.MAX_QTE
                    "
                    class="btn btn--plus rounded-e-xl ml-1"
                    @click="
                        command.changeCounter(
                            command_item,
                            +1
                        )
                    "
                    type="button"
                    name="add commandItem"
                    v-ripple
                >
                    <v-icon
                        >mdi-plus</v-icon
                    >
                </button>
            </div>
        </td>
        <td
            class="text-center"
        >
            <v-text-field
                @update:model-value="
                    command.delaySetCartStorage()
                "
                :label="
                    $t(
                        'command_item_description'
                    )
                "
                flat
                :name="`description-${command_item.id}`"
                variant="solo-filled"
                rounded
                hide-details
                v-model="
                    command_item.description
                "
                rows="1"
                color="blue"
                tabindex="1"
            ></v-text-field>
        </td>
    </tr>
</template>

<script>
import { useCommand } from '@/stores/Command.js';

export default {
    props: {
      command_item:Object,
      cmd:Object  
    },
    computed: {
        command: function () {
            return useCommand();
        }
    },
    watch: {
        "command_item.item_id": function () {
            this.command.getCommandFirstItemInfo(this.cmd);
        }
    }

}
</script>
<style >

.detail_command  .v-field__field{
    width: 11vw;
}
th{
    padding: 0px 10px !important;
}
td{
    padding: 0px 10px !important;
}
/* Product Quantity */
.qte_wrapper {
    height: 30px;
    display: flex;
    justify-content: center;
}
.quantity {
    border: none;
    text-align: center;
    width: 30px;
    background-color: #b1b5b714 !important;
    font-size: 16px;
    color: var(--inverse_theme) !important;
    font-weight: 300;
    border: 1px solid #bec3c529;
}
.quantity::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.qte_wrapper-detail {
    height: 30px;
    display: flex;
    flex-direction: column-reverse;
    justify-content: center;
    align-items: center;
}
.quantity-detail {
    border: none;
    text-align: center;
    width: 30px;
    background-color: #b1b5b714 !important;
    font-size: 16px;
    color: var(--inverse_theme) !important;
    font-weight: 300;
    border: 1px solid #bec3c529;
}
.quantity-detail::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.btn {
    border: 1px solid #bec3c529;
    width: 30px;
    background-color: #b1b5b714 !important;
    color: var(--inverse_theme) !important;
    /*   border-radius: 6px; */
    cursor: pointer;
}
.btn-detail {
    border: 1px solid #bec3c529;
    width: 30px;
    background-color: #b1b5b714 !important;
    color: var(--inverse_theme) !important;
    /*   border-radius: 6px; */
    cursor: pointer;
}
</style>