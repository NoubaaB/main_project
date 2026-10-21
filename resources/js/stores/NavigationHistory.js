import { defineStore } from 'pinia';
import { getRouteMetadata } from '@/utils/routeMetadata';
import i18n from '@/plugins/i18n';

const STORAGE_KEY_RECENTS = 'jupiter_nav_recents';
const STORAGE_KEY_PINNED = 'jupiter_nav_pinned';

export const useNavigationHistory = defineStore('navigationHistory', {
  state: () => {
    let savedRecents = [];
    let savedPinned = [];
    try {
      savedRecents = JSON.parse(localStorage.getItem(STORAGE_KEY_RECENTS) || '[]');
      savedPinned = JSON.parse(localStorage.getItem(STORAGE_KEY_PINNED) || '[]');
    } catch (e) {
      savedRecents = [];
      savedPinned = [];
    }

    return {
      stack: [],
      currentIndex: -1,
      isNavigatingHistory: false,
      recentPages: savedRecents,
      pinnedPages: savedPinned,
      historyMenuOpen: false,
      initialized: false,
    };
  },

  getters: {
    canGoBack: (state) => state.currentIndex > 0,
    canGoForward: (state) => state.currentIndex >= 0 && state.currentIndex < state.stack.length - 1,

    currentPage: (state) => (state.currentIndex >= 0 ? state.stack[state.currentIndex] : null),
    previousPage: (state) => (state.currentIndex > 0 ? state.stack[state.currentIndex - 1] : null),
    nextPage: (state) =>
      state.currentIndex >= 0 && state.currentIndex < state.stack.length - 1
        ? state.stack[state.currentIndex + 1]
        : null,

    // Entries before current page (newest previous first)
    backStack: (state) =>
      state.currentIndex > 0
        ? state.stack.slice(0, state.currentIndex).reverse()
        : [],

    // Entries after current page (next immediate first)
    forwardStack: (state) =>
      state.currentIndex >= 0 && state.currentIndex < state.stack.length - 1
        ? state.stack.slice(state.currentIndex + 1)
        : [],
  },

  actions: {
    init(router) {
      if (this.initialized || !router) return;
      this.initialized = true;

      // Handle router navigation
      router.afterEach((to, from) => {
        this.recordRoute(to);
      });

      // Record current initial route if already active
      if (router.currentRoute?.value?.name) {
        this.recordRoute(router.currentRoute.value);
      }

      // Setup keyboard shortcuts: Alt+Left (Back), Alt+Right (Forward), Ctrl+H (History menu)
      if (typeof window !== 'undefined') {
        window.addEventListener('keydown', (e) => {
          // Alt + ArrowLeft => Back
          if (e.altKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            this.goBack(router);
          }
          // Alt + ArrowRight => Forward
          else if (e.altKey && e.key === 'ArrowRight') {
            e.preventDefault();
            this.goForward(router);
          }
          // Ctrl + H (or Cmd + H on Mac) => Toggle History
          else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'h' && !e.shiftKey) {
            e.preventDefault();
            this.historyMenuOpen = !this.historyMenuOpen;
          }
        });
      }
    },

    recordRoute(route) {
      if (!route || !route.name) return;
      const ignoreRoutes = ['login', 'register', 'error', 'error404', 'NotFound'];
      if (ignoreRoutes.includes(route.name)) return;

      const meta = getRouteMetadata(route);
      const fullPath = route.fullPath || route.path;

      // If navigation was triggered via our history Back / Forward buttons
      if (this.isNavigatingHistory) {
        this.isNavigatingHistory = false;
        // Verify current index matches the target
        const matchIdx = this.stack.findIndex(s => s.fullPath === fullPath);
        if (matchIdx !== -1) {
          this.currentIndex = matchIdx;
          // Refresh title and visited time
          this.stack[matchIdx].title = meta.title;
          this.stack[matchIdx].section = meta.section;
          this.stack[matchIdx].visitedAt = Date.now();
        }
        this.addToRecents(route, meta);
        return;
      }

      // If user navigates to the exact same fullPath as current, avoid duplicate
      if (this.currentPage && this.currentPage.fullPath === fullPath) {
        this.currentPage.visitedAt = Date.now();
        this.currentPage.title = meta.title;
        return;
      }

      // Truncate any forward history stack when a new route is pushed
      if (this.currentIndex >= 0 && this.currentIndex < this.stack.length - 1) {
        this.stack = this.stack.slice(0, this.currentIndex + 1);
      }

      const entry = {
        id: `${route.name}_${Date.now()}_${Math.random().toString(36).substr(2, 4)}`,
        name: route.name,
        path: route.path,
        fullPath,
        title: meta.title,
        section: meta.section,
        icon: meta.icon,
        color: meta.color,
        moduleKey: meta.moduleKey,
        siblings: meta.siblings || [],
        query: { ...route.query },
        params: { ...route.params },
        visitedAt: Date.now(),
      };

      this.stack.push(entry);

      // Keep stack at reasonable size
      if (this.stack.length > 50) {
        this.stack.shift();
      }

      this.currentIndex = this.stack.length - 1;
      this.addToRecents(route, meta);
    },

    addToRecents(route, meta) {
      const fullPath = route.fullPath || route.path;
      // Deduplicate by fullPath (or route.name if path matches)
      this.recentPages = this.recentPages.filter(p => p.fullPath !== fullPath && p.name !== route.name);

      const recentEntry = {
        name: route.name,
        path: route.path,
        fullPath,
        title: meta.title,
        section: meta.section,
        icon: meta.icon,
        color: meta.color,
        moduleKey: meta.moduleKey,
        visitedAt: Date.now(),
      };

      this.recentPages.unshift(recentEntry);

      if (this.recentPages.length > 25) {
        this.recentPages.pop();
      }

      try {
        localStorage.setItem(STORAGE_KEY_RECENTS, JSON.stringify(this.recentPages));
      } catch (e) {}
    },

    goBack(router) {
      if (!this.canGoBack || !router) return;
      this.isNavigatingHistory = true;
      this.currentIndex--;
      const target = this.stack[this.currentIndex];
      router.push(target.fullPath).catch(() => {
        this.isNavigatingHistory = false;
      });
    },

    goForward(router) {
      if (!this.canGoForward || !router) return;
      this.isNavigatingHistory = true;
      this.currentIndex++;
      const target = this.stack[this.currentIndex];
      router.push(target.fullPath).catch(() => {
        this.isNavigatingHistory = false;
      });
    },

    jumpTo(index, router) {
      if (index < 0 || index >= this.stack.length || !router) return;
      this.isNavigatingHistory = true;
      this.currentIndex = index;
      const target = this.stack[index];
      this.historyMenuOpen = false;
      router.push(target.fullPath).catch(() => {
        this.isNavigatingHistory = false;
      });
    },

    navigateTo(page, router) {
      if (!page || !router) return;
      this.historyMenuOpen = false;
      const target = page.fullPath || page.path || { name: page.name };
      router.push(target).catch(() => {});
    },

    togglePin(page) {
      if (!page) return;
      const fullPath = page.fullPath || page.path;
      const existingIdx = this.pinnedPages.findIndex(p => p.fullPath === fullPath || p.name === page.name);

      if (existingIdx !== -1) {
        this.pinnedPages.splice(existingIdx, 1);
      } else {
        this.pinnedPages.push({
          name: page.name,
          path: page.path,
          fullPath,
          title: page.title,
          section: page.section,
          icon: page.icon,
          color: page.color,
          pinnedAt: Date.now(),
        });
      }

      try {
        localStorage.setItem(STORAGE_KEY_PINNED, JSON.stringify(this.pinnedPages));
      } catch (e) {}
    },

    isPinned(fullPathOrName) {
      return this.pinnedPages.some(p => p.fullPath === fullPathOrName || p.name === fullPathOrName);
    },

    clearHistory() {
      if (this.currentPage) {
        this.stack = [this.currentPage];
        this.currentIndex = 0;
      } else {
        this.stack = [];
        this.currentIndex = -1;
      }
      this.recentPages = this.currentPage ? [{ ...this.currentPage }] : [];
      try {
        localStorage.removeItem(STORAGE_KEY_RECENTS);
      } catch (e) {}
    },

    getRelativeTime(timestamp) {
      if (!timestamp) return '';
      const now = Date.now();
      const diffSec = Math.floor((now - timestamp) / 1000);
      if (diffSec < 30) return i18n?.global?.t('just_now') || 'Just now';
      if (diffSec < 60) return `${diffSec}s`;
      const diffMin = Math.floor(diffSec / 60);
      if (diffMin < 60) return `${diffMin}m`;
      const diffHour = Math.floor(diffMin / 60);
      if (diffHour < 24) return `${diffHour}h`;
      return `${Math.floor(diffHour / 24)}d`;
    }
  }
});
