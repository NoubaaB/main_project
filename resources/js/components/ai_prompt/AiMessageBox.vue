<template>
    <div :id="`_ai_message_`+index"></div>
</template>

<script>
    import { loadExternalContent } from '@/helpers/dynamic-loader';
    import {useAIPrompt} from "@/stores/AIPrompt";
    export default {
        props: ['msg', 'index'],
        computed: {
            ai_prompt : function(){
                return useAIPrompt();
            },
        },
        mounted:function(){
            loadExternalContent(this.msg.text, `_ai_message_${this.ai_prompt.messages.length-1}`); 
        }
    }
</script>

<style lang="scss" scoped>

</style>