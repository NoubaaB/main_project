import { defineStore } from "pinia";
import { useAuth } from "./Auth";
import { useMain } from "./Main";
import { useCorporal } from "./Corporal";
import { useSection } from "./Section";
import { useWorkforce } from "./Workforce";
import mitt from '@/plugins/mitt.js';
import { useStorage } from '@vueuse/core';

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
            thinking_level: useStorage('ai_thinking_level', 'low'),
            selected_model: useStorage('ai_selected_model', 'gemini-4-carbon'),
            available_models: [
                {
                    id: 'gemini-4-carbon',
                    name: 'Gemini 4 Carbon',
                    tag: 'Carbon Core',
                    icon: 'mdi-atom',
                    color: '#00E5FF',
                    desc: 'Google Gemini 4 Generation • Carbon Architecture • Deepest Analytical Reasoning',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-4-argon',
                    name: 'Gemini 4 Argon',
                    tag: 'Ultra Fast',
                    icon: 'mdi-shield-star',
                    color: '#76FF03',
                    desc: 'Gemini 4 Generation • High Efficiency & Real-Time Enterprise Synthesis',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-4-neon',
                    name: 'Gemini 4 Neon',
                    tag: 'Visual Analytics',
                    icon: 'mdi-chart-timeline-variant-shimmer',
                    color: '#FF007F',
                    desc: 'Gemini 4 Generation • Advanced Dashboard & ApexCharts Synthesis',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-4-hydrogen',
                    name: 'Gemini 4 Hydrogen',
                    tag: 'Deep Reasoning',
                    icon: 'mdi-molecule',
                    color: '#00B0FF',
                    desc: 'Gemini 4 Generation • Advanced Statistical Analysis & Auditing',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-3.5-flash-lite',
                    name: 'Gemini 3.5 Flash-Lite',
                    tag: 'Recommended',
                    icon: 'mdi-lightning-bolt',
                    color: '#42A5F5',
                    desc: 'Google Next-Gen • 1M Context • Ultra Fast (~1s)',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-3.8-flash',
                    name: 'Gemini 3.8 Flash',
                    tag: 'High Performance',
                    icon: 'mdi-flash-alert',
                    color: '#FFAB00',
                    desc: 'Gemini 3.8 Generation • Peak Throughput & Multimodal Speed',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-3.7-flash',
                    name: 'Gemini 3.7 Flash',
                    tag: 'Hybrid Thinking',
                    icon: 'mdi-brain-cog',
                    color: '#FF5252',
                    desc: 'Gemini 3.7 Generation • Adaptive CoT Hybrid Reasoning',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-3.1-flash-lite',
                    name: 'Gemini 3.1 Flash-Lite',
                    tag: 'Low Latency',
                    icon: 'mdi-flash',
                    color: '#00BFA5',
                    desc: 'Optimized Low Latency • High Accuracy SQL Synthesis',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-flash-lite-latest',
                    name: 'Gemini Flash-Lite Latest',
                    tag: 'Stable',
                    icon: 'mdi-auto-fix',
                    color: '#7C4DFF',
                    desc: 'Google Stable Release Channel • High Availability',
                    provider: 'gemini',
                },
                {
                    id: 'gemini-3.1-pro-preview',
                    name: 'Gemini 3.1 Pro',
                    tag: 'Complex Logic',
                    icon: 'mdi-crown',
                    color: '#D500F9',
                    desc: 'Pro Tier • Advanced Multi-Step Schema Reasoning',
                    provider: 'gemini',
                },
                {
                    id: 'gemma-4-31b-it',
                    name: 'Gemma 4 31B IT',
                    tag: 'Open Weights',
                    icon: 'mdi-creation',
                    color: '#00C853',
                    desc: 'Google Gemma 4 Open Architecture • Deep Instruction Tuning',
                    provider: 'gemini',
                },
                {
                    id: 'gpt-oss:120b-cloud',
                    name: 'GPT-OSS 120B Cloud',
                    tag: 'Deep Reasoning',
                    icon: 'mdi-brain',
                    color: '#FF9100',
                    desc: 'Ollama Cloud Engine • Deep Analytical Rigor',
                    provider: 'ollama',
                },
                {
                    id: 'qwen3:14b',
                    name: 'Qwen 3 14B',
                    tag: 'Local',
                    icon: 'mdi-server-network',
                    color: '#E91E63',
                    desc: 'On-Premise Dedicated Local Engine',
                    provider: 'ollama',
                },
            ],
        };
    },
    actions: {
        getModelObj(modelId) {
            const authorized = this.authorizedModels;
            if (authorized && authorized.length > 0) {
                const isSelectedAuthorized = authorized.some(m => m.id === this.selected_model);
                if (!isSelectedAuthorized) {
                    this.selected_model = authorized[0].id;
                }
            }
            const id = modelId || this.selected_model;
            return this.available_models.find(m => m.id === id) || this.available_models[0];
        },
        sendMessage: async function (message) {
            const rawMsgs = (this.messages || []).filter(e => e.sender != 'AI' && e.role != 'agent');
            const structuredMessages = rawMsgs.map(e => ({
                role: 'user',
                content: e.text || e.content || '',
                text: e.text || e.content || '',
            }));
            let messagesStr = JSON.stringify(structuredMessages);

            this.messages.push({
                text: message,
                content: message,
                role: 'user',
                sender: this.auth?.user?.name || 'User',
                ts: Date.now()
            });
            let id = this.messages.length;
            let theme = this.main?.is_dark ? "dark" : (localStorage.getItem("theme") || "light");
            let locale = localStorage.getItem('locale') || localStorage.getItem('lang') || 'en';
            return await axios.post(`/api/ask-db`, {
                message,
                content: message,
                id,
                theme,
                locale,
                model: this.selected_model,
                messages: structuredMessages,
                messages_str: messagesStr,
                thinking_level: this.thinking_level
            }).then(response => {
                const answerText = response.data?.message?.content || response.data?.answer;
                const reasoning = response.data?.message?.reasoning || response.data?.reasoning || null;
                const thinkingTime = response.data?.thinking_time || reasoning?.thinking_time || null;
                const modelUsed = response.data?.model || this.selected_model;

                this.messages.push({
                    text: answerText,
                    content: answerText,
                    sender: "AI",
                    role: "agent",
                    model: modelUsed,
                    reasoning: reasoning,
                    thinking_time: thinkingTime,
                    ts: Date.now()
                });
                return {
                    answer: answerText,
                    message: response.data?.message,
                    reasoning: reasoning,
                    thinking_time: thinkingTime,
                    model: modelUsed,
                    id: id
                };
            }).catch(error => { this.error() });
        },
        sendMessageWithExcel: async function (message, excelFiles) {
            this.messages.push({
                text: message,
                content: message,
                role: 'user',
                sender: this.auth?.user?.name || 'User',
                ts: Date.now()
            });
            let id = this.messages.length;
            let theme = this.main?.is_dark ? "dark" : (localStorage.getItem("theme") || "light");
            let locale = localStorage.getItem('locale') || localStorage.getItem('lang') || 'en';
            const structuredMessages = this.messages.filter(e => e.sender != 'AI' && e.role != 'agent').map(e => ({
                role: 'user',
                content: e.text || e.content || '',
                text: e.text || e.content || '',
            }));
            let messagesStr = JSON.stringify(structuredMessages);

            return await axios.post(`/api/ask-db`, {
                message,
                content: message,
                id,
                theme,
                locale,
                model: this.selected_model,
                messages: structuredMessages,
                messages_str: messagesStr,
                excel_files: excelFiles,
                thinking_level: this.thinking_level
            }).then(response => {
                const answerText = response.data?.message?.content || response.data?.answer;
                const reasoning = response.data?.message?.reasoning || response.data?.reasoning || null;
                const thinkingTime = response.data?.thinking_time || reasoning?.thinking_time || null;
                const modelUsed = response.data?.model || this.selected_model;

                this.messages.push({
                    text: answerText,
                    content: answerText,
                    sender: "AI",
                    role: "agent",
                    model: modelUsed,
                    reasoning: reasoning,
                    thinking_time: thinkingTime,
                    ts: Date.now()
                });
                return {
                    answer: answerText,
                    message: response.data?.message,
                    reasoning: reasoning,
                    thinking_time: thinkingTime,
                    model: modelUsed,
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
                    reasoning: m.reasoning || null,
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
                    reasoning: m.reasoning || null,
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
            let theme = this.main?.is_dark ? "dark" : (localStorage.getItem("theme") || "light");
            let locale = localStorage.getItem('locale') || localStorage.getItem('lang') || 'en';
            return await axios.post(`/api/ai-assistant`, {
                message,
                messages,
                theme,
                locale,
                model: this.selected_model,
                thinking_level: this.thinking_level
            }).then(response => {
                const reply = {
                    id,
                    role: 'agent',
                    text: response.data.answer || response.data.message?.content,
                    content: response.data.answer || response.data.message?.content,
                    reasoning: response.data.reasoning || response.data.message?.reasoning || null,
                    model: response.data.model || this.selected_model,
                    thinking_time: response.data.thinking_time || null,
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
            let theme = this.main?.is_dark ? "dark" : (localStorage.getItem("theme") || "light");
            let locale = localStorage.getItem('locale') || localStorage.getItem('lang') || 'en';
            return await axios.post(`/api/ai-assistant/execute`, {
                action_plan: actionPlan,
                message: message || 'Confirmed action',
                theme,
                locale,
            }).then(response => {
                const reply = {
                    id: Date.now() + Math.random(),
                    role: 'agent',
                    text: response.data.answer,
                    content: response.data.answer,
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
            let theme = this.main?.is_dark ? "dark" : (localStorage.getItem("theme") || "light");
            let locale = localStorage.getItem('locale') || localStorage.getItem('lang') || 'en';
            const formData = new FormData();
            formData.append('message', message);
            formData.append('excel_data', JSON.stringify(excelData));
            formData.append('theme', theme);
            formData.append('locale', locale);
            formData.append('thinking_level', this.thinking_level);
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
                    content: response.data.answer,
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
                    content: m.text,
                    reasoning: m.reasoning || null,
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
                    content: m.text,
                    reasoning: m.reasoning || null,
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
        getList: state => state.list,
        canSelectModel: (state) => {
            const auth = useAuth();
            if (auth.isSuperAdmin) return true;
            return auth.canSelectAIModel;
        },
        authorizedModels: (state) => {
            const auth = useAuth();
            // Super Admin has all models by default
            if (auth.isSuperAdmin) {
                return state.available_models;
            }
            // If user cannot select models, locked to default model
            if (!auth.canSelectAIModel) {
                const def = auth.defaultAIModel || 'gemini-4-carbon';
                const found = state.available_models.find(m => m.id === def);
                return found ? [found] : [state.available_models[0]];
            }
            // If user or role has specific allowed models list
            const allowed = auth.allowedAIModels;
            if (allowed && Array.isArray(allowed) && allowed.length > 0) {
                const filtered = state.available_models.filter(m => allowed.includes(m.id));
                return filtered.length > 0 ? filtered : state.available_models;
            }
            return state.available_models;
        },
    }
});

