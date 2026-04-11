import { defineStore } from "pinia";
import { useAuth } from "./Auth";
import { useMain } from "./Main";
export const useAIPrompt = defineStore("ai_prompt", {
    state: () => {
        return {
            messages: [],
            assistant_messages: [],
            auth: useAuth(),
            main: useMain(),
            // Pagination state
            loading_history: false,
            has_more: false,
            next_before_id: null,
            history_loaded: false,
        };
    },
    actions: {
        sendMessage:async function (message) {
            let messages = JSON.stringify(this.assistant_messages.filter(e => e.sender != 'AI').map(e => {
                return {
                    role: e.sender,
                    text: e.text
                }
            }));
            this.messages.push({
                text: message,
                sender: this.auth.user.name
            });
            let id = this.messages.length;
            let theme = localStorage.getItem("theme");
            return await axios.post(`/api/ask-db`, {
                message,
                id,
                theme,
                messages
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
        },
        clear_assistant_messages: function () {
            this.assistant_messages = [];
        },

        /**
         * Delete a single message by id from local state and backend.
         */
        delete_assistant_message: async function (msgId) {
            this.assistant_messages = this.assistant_messages.filter(m => m.id !== msgId);
            try {
                await axios.delete(`/api/chat-messages/${msgId}`);
            } catch (e) {
                console.error('Failed to delete message:', e);
            }
        },

        /**
         * Toggle a reaction on a message (like / dislike).
         * reaction: 'like' | 'dislike' | null (to clear)
         */
        react_to_message: async function (msgId, reaction) {
            const msg = this.assistant_messages.find(m => m.id === msgId);
            if (!msg) return;
            // Toggle: if same reaction, clear it
            const newReaction = msg.reaction === reaction ? null : reaction;
            msg.reaction = newReaction;
            try {
                await axios.post(`/api/chat-messages/${msgId}/react`, { reaction: newReaction });
            } catch (e) {
                console.error('Failed to react to message:', e);
            }
        },

        // ── Persistence Actions ──

        /**
         * Load chat history from the API (first page).
         * Called once when the chat panel opens.
         */
        loadChatHistory: async function () {
            if (this.history_loaded || this.loading_history) return;
            this.loading_history = true;
            try {
                const response = await axios.get('/api/chat-messages', {
                    params: { per_page: 20 }
                });
                const data = response.data;
                // API returns newest-first, we reverse to chronological
                const msgs = (data.messages || []).reverse().map(m => ({
                    id: m.id,
                    role: m.role,
                    text: m.text,
                    ts: m.ts,
                    reaction: m.reaction || null,
                    persisted: true,
                }));
                this.assistant_messages = msgs;
                this.has_more = data.has_more;
                this.next_before_id = data.next_before_id;
                this.history_loaded = true;
            } catch (e) {
                console.error('Failed to load chat history:', e);
            } finally {
                this.loading_history = false;
            }
        },

        /**
         * Load older messages (next page) for infinite scroll.
         * Prepends older messages to the beginning.
         */
        loadMoreHistory: async function () {
            if (!this.has_more || this.loading_history || !this.next_before_id) return;
            this.loading_history = true;
            try {
                const response = await axios.get('/api/chat-messages', {
                    params: { per_page: 20, before_id: this.next_before_id }
                });
                const data = response.data;
                const olderMsgs = (data.messages || []).reverse().map(m => ({
                    id: m.id,
                    role: m.role,
                    text: m.text,
                    ts: m.ts,
                    reaction: m.reaction || null,
                    persisted: true,
                }));
                // Prepend older messages
                this.assistant_messages = [...olderMsgs, ...this.assistant_messages];
                this.has_more = data.has_more;
                this.next_before_id = data.next_before_id;
            } catch (e) {
                console.error('Failed to load more history:', e);
            } finally {
                this.loading_history = false;
            }
        },

        /**
         * Save a message to the database.
         */
        saveMessage: async function (role, text) {
            try {
                await axios.post('/api/chat-messages', { role, text });
            } catch (e) {
                console.error('Failed to save message:', e);
            }
        },

        /**
         * Clear chat history from the database and local state.
         */
        clearHistory: async function () {
            try {
                await axios.delete('/api/chat-messages');
            } catch (e) {
                console.error('Failed to clear history:', e);
            }
            this.assistant_messages = [];
            this.has_more = false;
            this.next_before_id = null;
            this.history_loaded = false;
        },
    },
    getters: {
        getList: state => state.list
    }
});