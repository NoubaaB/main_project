import { defineStore } from "pinia";
import { useAuth } from "./Auth";
export const useAIPrompt = defineStore("ai_prompt", {
    state: () => {
        return {
            messages: [
                {
                    text: "Hello! How can I help you today?",
                    sender: "AI"
                },
            ],
            auth:useAuth(),
        };
    },
    actions: {
        sendMessage:async function (message) {
            this.messages.push({
                text: message,
                sender: this.auth.user.name
            });
            let id = this.messages.length;
            return await axios.post(`/api/ask-db`, {
                message,
                id
            }).then(response => {
                this.messages.push({
                    text: response.data.answer,
                    sender: "AI"
                });
                return {
                    answer: response.data.answer,
                    id: id
                };
            }).catch(error => { this.error() });
        },
        error: function () {
            console.log("inside error")
            this.messages.push({
                text: "Sorry, I couldn't process your request.",
                sender: "AI"
            });
        },
    },
    getters: {
        getList: state => state.list
    }
});