// vite.config.js
import { defineConfig } from "file:///c:/xampp/htdocs/main_project/node_modules/vite/dist/node/index.js";
import laravel from "file:///c:/xampp/htdocs/main_project/node_modules/laravel-vite-plugin/dist/index.js";
import vue from "file:///c:/xampp/htdocs/main_project/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import { fileURLToPath, URL } from "node:url";
import Fonts from "file:///c:/xampp/htdocs/main_project/node_modules/unplugin-fonts/dist/vite.mjs";
import Layouts from "file:///c:/xampp/htdocs/main_project/node_modules/vite-plugin-vue-layouts/dist/index.mjs";
var __vite_injected_original_import_meta_url = "file:///c:/xampp/htdocs/main_project/vite.config.js";
var vite_config_default = defineConfig({
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.js"],
      refresh: true
    }),
    Layouts(),
    vue(),
    Fonts({
      google: {
        families: [{
          name: "Roboto",
          styles: "wght@100;300;400;500;700;900"
        }]
      }
    })
  ],
  resolve: {
    alias: {
      vue: "vue/dist/vue.esm-bundler.js",
      "@": fileURLToPath(new URL("./resources/js", __vite_injected_original_import_meta_url))
    },
    extensions: [
      ".js",
      ".json",
      ".jsx",
      ".mjs",
      ".ts",
      ".tsx",
      ".vue"
    ]
  },
  build: { chunkSizeWarningLimit: 11600 },
  css: {
    preprocessorOptions: {
      scss: {
        api: "modern-compiler"
        // or "modern"
      }
    }
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJjOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxcbWFpbl9wcm9qZWN0XCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ZpbGVuYW1lID0gXCJjOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxcbWFpbl9wcm9qZWN0XFxcXHZpdGUuY29uZmlnLmpzXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ltcG9ydF9tZXRhX3VybCA9IFwiZmlsZTovLy9jOi94YW1wcC9odGRvY3MvbWFpbl9wcm9qZWN0L3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCB2dWUgZnJvbSBcIkB2aXRlanMvcGx1Z2luLXZ1ZVwiO1xuaW1wb3J0IHsgZmlsZVVSTFRvUGF0aCwgVVJMIH0gZnJvbSBcIm5vZGU6dXJsXCI7XG5pbXBvcnQgRm9udHMgZnJvbSAndW5wbHVnaW4tZm9udHMvdml0ZSc7XG5pbXBvcnQgTGF5b3V0cyBmcm9tICd2aXRlLXBsdWdpbi12dWUtbGF5b3V0cyc7XG5leHBvcnQgZGVmYXVsdCBkZWZpbmVDb25maWcoe1xuICAgIHBsdWdpbnM6IFtcbiAgICAgICAgbGFyYXZlbCh7XG4gICAgICAgICAgICBpbnB1dDogWydyZXNvdXJjZXMvY3NzL2FwcC5jc3MnLCAncmVzb3VyY2VzL2pzL2FwcC5qcyddLFxuICAgICAgICAgICAgcmVmcmVzaDogdHJ1ZSxcbiAgICAgICAgfSksXG4gICAgICAgIExheW91dHMoKSxcbiAgICAgICAgdnVlKCksXG4gICAgICAgIEZvbnRzKHtcbiAgICAgICAgICAgIGdvb2dsZToge1xuICAgICAgICAgICAgICAgIGZhbWlsaWVzOiBbe1xuICAgICAgICAgICAgICAgICAgICBuYW1lOiAnUm9ib3RvJyxcbiAgICAgICAgICAgICAgICAgICAgc3R5bGVzOiAnd2dodEAxMDA7MzAwOzQwMDs1MDA7NzAwOzkwMCcsXG4gICAgICAgICAgICAgICAgfV0sXG4gICAgICAgICAgICB9LFxuICAgICAgICB9KSxcbiAgICBdLFxuICAgIHJlc29sdmU6IHtcbiAgICAgICAgYWxpYXM6IHtcbiAgICAgICAgICAgIHZ1ZTogXCJ2dWUvZGlzdC92dWUuZXNtLWJ1bmRsZXIuanNcIixcbiAgICAgICAgICAgICdAJzogZmlsZVVSTFRvUGF0aChuZXcgVVJMKCcuL3Jlc291cmNlcy9qcycsIGltcG9ydC5tZXRhLnVybCkpXG4gICAgICAgIH0sXG4gICAgICAgIGV4dGVuc2lvbnM6IFtcbiAgICAgICAgICAgICcuanMnLFxuICAgICAgICAgICAgJy5qc29uJyxcbiAgICAgICAgICAgICcuanN4JyxcbiAgICAgICAgICAgICcubWpzJyxcbiAgICAgICAgICAgICcudHMnLFxuICAgICAgICAgICAgJy50c3gnLFxuICAgICAgICAgICAgJy52dWUnLFxuICAgICAgICBdLFxuICAgIH0sXG4gICAgYnVpbGQ6IHsgY2h1bmtTaXplV2FybmluZ0xpbWl0OiAxMTYwMCwgfSxcbiAgICBjc3M6IHtcbiAgICAgICAgcHJlcHJvY2Vzc29yT3B0aW9uczoge1xuICAgICAgICAgICAgc2Nzczoge1xuICAgICAgICAgICAgICAgIGFwaTogJ21vZGVybi1jb21waWxlcicgLy8gb3IgXCJtb2Rlcm5cIlxuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuXG59KTtcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFBOFEsU0FBUyxvQkFBb0I7QUFDM1MsT0FBTyxhQUFhO0FBQ3BCLE9BQU8sU0FBUztBQUNoQixTQUFTLGVBQWUsV0FBVztBQUNuQyxPQUFPLFdBQVc7QUFDbEIsT0FBTyxhQUFhO0FBTG1KLElBQU0sMkNBQTJDO0FBTXhOLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLFNBQVM7QUFBQSxJQUNMLFFBQVE7QUFBQSxNQUNKLE9BQU8sQ0FBQyx5QkFBeUIscUJBQXFCO0FBQUEsTUFDdEQsU0FBUztBQUFBLElBQ2IsQ0FBQztBQUFBLElBQ0QsUUFBUTtBQUFBLElBQ1IsSUFBSTtBQUFBLElBQ0osTUFBTTtBQUFBLE1BQ0YsUUFBUTtBQUFBLFFBQ0osVUFBVSxDQUFDO0FBQUEsVUFDUCxNQUFNO0FBQUEsVUFDTixRQUFRO0FBQUEsUUFDWixDQUFDO0FBQUEsTUFDTDtBQUFBLElBQ0osQ0FBQztBQUFBLEVBQ0w7QUFBQSxFQUNBLFNBQVM7QUFBQSxJQUNMLE9BQU87QUFBQSxNQUNILEtBQUs7QUFBQSxNQUNMLEtBQUssY0FBYyxJQUFJLElBQUksa0JBQWtCLHdDQUFlLENBQUM7QUFBQSxJQUNqRTtBQUFBLElBQ0EsWUFBWTtBQUFBLE1BQ1I7QUFBQSxNQUNBO0FBQUEsTUFDQTtBQUFBLE1BQ0E7QUFBQSxNQUNBO0FBQUEsTUFDQTtBQUFBLE1BQ0E7QUFBQSxJQUNKO0FBQUEsRUFDSjtBQUFBLEVBQ0EsT0FBTyxFQUFFLHVCQUF1QixNQUFPO0FBQUEsRUFDdkMsS0FBSztBQUFBLElBQ0QscUJBQXFCO0FBQUEsTUFDakIsTUFBTTtBQUFBLFFBQ0YsS0FBSztBQUFBO0FBQUEsTUFDVDtBQUFBLElBQ0o7QUFBQSxFQUNKO0FBRUosQ0FBQzsiLAogICJuYW1lcyI6IFtdCn0K
