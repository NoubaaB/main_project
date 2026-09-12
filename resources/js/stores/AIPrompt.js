import { defineStore } from "pinia";
import { useAuth } from "./Auth";
import { useMain } from "./Main";
import { useCorporal } from "./Corporal";
import { useSection } from "./Section";
import { useWorkforce } from "./Workforce";
import mitt from '@/plugins/mitt.js';
export const useAIPrompt = defineStore("ai_prompt", {
    state: () => {
        return {
            messages: [],
            assistant_messages: [],
            auth: useAuth(),
            main: useMain(),
            // Pagination state (sidebar assistant)
            loading_history: false,
            has_more: false,
            next_before_id: null,
            history_loaded: false,
            // Pagination state (full-page ai_prompt)
            query_loading_history: false,
            query_has_more: false,
            query_next_before_id: null,
            query_history_loaded: false,
        };
    },
    actions: {
        sendMessage: async function (message) {
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
        sendMessageWithExcel: async function (message, excelFiles) {
            this.messages.push({
                text: message,
                sender: this.auth.user.name
            });
            let id = this.messages.length;
            let theme = localStorage.getItem("theme");
            let messages = JSON.stringify(this.messages.filter(e => e.sender != 'AI').map(e => ({
                role: e.sender,
                text: e.text
            })));
            return await axios.post(`/api/ask-db`, {
                message,
                id,
                theme,
                messages,
                excel_files: excelFiles
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
                text: "Sorry , an error occurred. Please try again.",
                sender: "AI"
            });
        },

        // -- AI Query (full-page) message persistence --

        loadQueryHistory: async function () {
            if (this.query_history_loaded || this.query_loading_history) return;
            this.query_loading_history = true;
            try {
                const response = await axios.get('/api/ai-query-messages', {
                    params: { per_page: 30 }
                });
                const data = response.data;
                const msgs = (data.messages || []).reverse().map(m => ({
                    id: m.id,
                    text: m.text,
                    sender: m.role === 'agent' ? 'AI' : 'user',
                    role: m.role,
                    ts: m.ts,
                    reaction: m.reaction || null,
                    persisted: true,
                }));
                this.messages = msgs;
                this.query_has_more = data.has_more;
                this.query_next_before_id = data.next_before_id;
                this.query_history_loaded = true;
            } catch (e) {
                console.error('Failed to load AI query history:', e);
            } finally {
                this.query_loading_history = false;
            }
        },

        loadMoreQueryHistory: async function () {
            if (!this.query_has_more || this.query_loading_history || !this.query_next_before_id) return;
            this.query_loading_history = true;
            try {
                const response = await axios.get('/api/ai-query-messages', {
                    params: { per_page: 20, before_id: this.query_next_before_id }
                });
                const data = response.data;
                const olderMsgs = (data.messages || []).reverse().map(m => ({
                    id: m.id,
                    text: m.text,
                    sender: m.role === 'agent' ? 'AI' : 'user',
                    role: m.role,
                    ts: m.ts,
                    reaction: m.reaction || null,
                    persisted: true,
                }));
                this.messages = [...olderMsgs, ...this.messages];
                this.query_has_more = data.has_more;
                this.query_next_before_id = data.next_before_id;
            } catch (e) {
                console.error('Failed to load more query history:', e);
            } finally {
                this.query_loading_history = false;
            }
        },

        deleteQueryMessage: async function (msgId) {
            this.messages = this.messages.filter(m => m.id !== msgId);
            try {
                await axios.delete(`/api/ai-query-messages/${msgId}`);
            } catch (e) {
                console.error('Failed to delete query message:', e);
            }
        },

        reactQueryMessage: async function (msgId, reaction) {
            const msg = this.messages.find(m => m.id === msgId);
            if (!msg) return;
            const newReaction = msg.reaction === reaction ? null : reaction;
            msg.reaction = newReaction;
            try {
                await axios.post(`/api/ai-query-messages/${msgId}/react`, { reaction: newReaction });
            } catch (e) {
                console.error('Failed to react to query message:', e);
            }
        },

        clearQueryHistory: async function () {
            try {
                await axios.delete('/api/ai-query-messages');
            } catch (e) {
                console.error('Failed to clear query history:', e);
            }
            this.messages = [];
            this.query_has_more = false;
            this.query_next_before_id = null;
            this.query_history_loaded = false;
        },
        askAssistant: async function (message) {
            let messages = JSON.stringify(this.assistant_messages.map(e => {
                return {
                    id: e.id,
                    role: e.role,
                    text: e.text
                }
            }));
            let id = this.assistant_messages.length + 1;
            return await axios.post(`/api/ai-assistant`, {
                message,
                messages
            }).then(response => {
                const reply = {
                    id,
                    role: 'agent',
                    text: response.data.answer,
                    ts: Date.now(),
                    pending_action: response.data.pending_action || null,
                };
                this.add_assistant_messages(reply);

                // If this was a confirm intent, refresh affected stores
                const affectedModels = response.data.affected_models || [];
                if (affectedModels.length > 0) {
                    this.refreshAffectedStores(affectedModels);
                }

                return {
                    answer: response.data.answer,
                    id: id,
                    pending_action: response.data.pending_action || null,
                };
            }).catch(error => {
                const errMsg = {
                    id: Date.now() + Math.random(),
                    role: 'agent',
                    text: 'Sorry , an error occurred. Please try again.',
                    ts: Date.now(),
                };
                this.assistant_messages.push(errMsg)
            });
        },
        executeAction: async function (actionPlan, message) {
            return await axios.post(`/api/ai-assistant/execute`, {
                action_plan: actionPlan,
                message: message || 'Confirmed action',
            }).then(response => {
                const reply = {
                    id: Date.now() + Math.random(),
                    role: 'agent',
                    text: response.data.answer,
                    ts: Date.now(),
                };
                this.add_assistant_messages(reply);

                // Refresh affected stores immediately
                const affectedModels = response.data.affected_models || [];
                if (affectedModels.length > 0) {
                    this.refreshAffectedStores(affectedModels);
                }

                return response.data;
            }).catch(error => {
                const errMsg = {
                    id: Date.now() + Math.random(),
                    role: 'agent',
                    text: 'Sorry , failed to execute action. Please try again.',
                    ts: Date.now(),
                };
                this.assistant_messages.push(errMsg);
            });
        },
        askWithExcel: async function (message, excelData, file) {
            const formData = new FormData();
            formData.append('message', message);
            formData.append('excel_data', JSON.stringify(excelData));
            if (file) {
                formData.append('file', file);
            }
            return await axios.post(`/api/ai-assistant/excel`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            }).then(response => {
                const reply = {
                    id: Date.now() + Math.random(),
                    role: 'agent',
                    text: response.data.answer,
                    ts: Date.now(),
                    pending_action: response.data.pending_action || null,
                };
                this.add_assistant_messages(reply);

                // If affected models returned, refresh stores
                const affectedModels = response.data.affected_models || [];
                if (affectedModels.length > 0) {
                    this.refreshAffectedStores(affectedModels);
                }

                return response.data;
            }).catch(error => {
                const errMsg = {
                    id: Date.now() + Math.random(),
                    role: 'agent',
                    text: 'Sorry , failed to process Excel data. Please try again.',
                    ts: Date.now(),
                };
                this.assistant_messages.push(errMsg);
            });
        },
        /**
         * Refresh Pinia stores and emit mitt events for the affected models.
         */
        refreshAffectedStores: function (modelNames) {
            // Stores with fetch() methods
            const fetchableStores = {
                'Corporal': () => useCorporal(),
                'Section': () => useSection(),
                'Workforce': () => useWorkforce(),
            };

            // Reference data: re-fetch from /api/me
            const referenceModels = [
                'Supplier', 'Ship', 'Client', 'Truck', 'FishType', 'FishFamily',
                'FishProcessType', 'PackageType', 'GuanoType', 'Fishery', 'Origin',
                'Warehouse', 'WarehouseCategory', 'ChargeCategory', 'ShippingCategory', 'Formula',
            ];

            let needsAuthRefresh = false;

            for (const model of modelNames) {
                // If it has its own fetch, call it
                if (fetchableStores[model]) {
                    try { fetchableStores[model]().fetch(); } catch (e) { /* ignore */ }
                }

                // If it's reference data, flag for auth refresh
                if (referenceModels.includes(model)) {
                    needsAuthRefresh = true;
                }

                // Emit mitt event for DataTable pages
                mitt.emit('model-changed', { model, action: 'refresh' });
            }

            // Re-fetch all reference data in one call
            if (needsAuthRefresh) {
                try { this.auth.attemp(); } catch (e) { /* ignore */ }
            }
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

        // -- Persistence Actions --

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
                    file_url: m.file_url || null,
                    file_name: m.file_name || null,
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
                    file_url: m.file_url || null,
                    file_name: m.file_name || null,
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

