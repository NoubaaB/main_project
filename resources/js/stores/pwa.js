import { defineStore } from "pinia";
import { useMain } from "./Main";
import i18n from '@/plugins/i18n';

// ─── Early capture: beforeinstallprompt fires BEFORE Pinia initializes ───
// We stash the event at module scope so it's never lost, then hand it
// to the reactive store inside init().
let _earlyInstallPrompt = null;

window.addEventListener("beforeinstallprompt", (e) => {
    e.preventDefault();
    _earlyInstallPrompt = e;
    console.log("[PWA] beforeinstallprompt captured (module-level)");
});

export const usePwa = defineStore("pwa", {
    state: () => {
        return {
            is_online: navigator.onLine,
            install_prompt: null,
            is_installed: false
        };
    },
    actions: {
        init() {
            // Transfer early-captured prompt (if it fired before init)
            if (_earlyInstallPrompt) {
                this.install_prompt = _earlyInstallPrompt;
                _earlyInstallPrompt = null;
                console.log("[PWA Store] Transferred early-captured install prompt");
            }

            // Monitor Online / Offline events
            window.addEventListener("online", () => this.handleConnectionChange(true));
            window.addEventListener("offline", () => this.handleConnectionChange(false));

            // Listen for any future beforeinstallprompt (e.g. after user dismisses)
            window.addEventListener("beforeinstallprompt", (e) => {
                e.preventDefault();
                this.install_prompt = e;
                console.log("[PWA Store] beforeinstallprompt event captured!");
            });

            // Monitor appinstalled event
            window.addEventListener("appinstalled", () => {
                this.is_installed = true;
                this.install_prompt = null;
                console.log("[PWA Store] App installed successfully!");
            });

            // Detect if already installed (standalone mode)
            if (window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone) {
                this.is_installed = true;
            }
        },
        handleConnectionChange(isOnline) {
            this.is_online = isOnline;
            const main = useMain();
            
            // Format nice snackbar
            main.snackbar = {
                action_command: true,
                action_command_icon: isOnline ? "mdi-wifi" : "mdi-wifi-off",
                action_command_icon_color: isOnline ? "#4CAF50" : "#FF9800",
                action_command_text: isOnline 
                    ? i18n.global.t("pwa_online_desc") 
                    : i18n.global.t("pwa_offline_desc")
            };
        },
        async triggerInstall() {
            if (!this.install_prompt) return;

            // Show the install prompt
            this.install_prompt.prompt();

            // Wait for the user to respond to the prompt
            const { outcome } = await this.install_prompt.userChoice;
            console.log(`[PWA Store] User response to install prompt: ${outcome}`);

            // We've used the prompt, and can't use it again, discard it
            this.install_prompt = null;
        }
    },
    getters: {
        isInstallable: (state) => !!state.install_prompt
    }
});
