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
            assistant_messages: [],
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
            let theme = localStorage.getItem("theme");
            return await axios.post(`/api/ask-db`, {
                message,
                id,
                theme
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
                text: "Sorry — an error occurred. Please try again.",
                sender: "AI"
            });
        },
        askAssistant: async function (message) {
            let messages = JSON.stringify(this.assistant_messages.map(e => {
                return {
                    id: e.id,
                    role: e.role,
                    text: e.text
                }
            }));
            let id = this.assistant_messages.length+1;
            return await axios.post(`/api/ai-assistant`, {
                message,
                messages
            }).then(response => {
                const reply = {
                    id,
                    role: 'agent',
                    text: response.data.answer,
                    ts: Date.now(),
                };
                this.add_assistant_messages(reply);
                return {
                    answer: response.data.answer,
                    id: id
                };
            }).catch(error => { 
                const errMsg = {
                    id: Date.now() + Math.random(),
                    role: 'agent',
                    text: 'Sorry — an error occurred. Please try again.',
                    ts: Date.now(),
                };
                this.assistant_messages.push(errMsg)

             });
        },
        add_assistant_messages: function (msg) {
            this.assistant_messages.push(msg)
        }
    },
    getters: {
        getList: state => state.list
    }
});