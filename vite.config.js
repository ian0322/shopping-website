import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
  base: './',  // 這行加上，讓資源路徑變成相對路徑
  server: {
    host: "0.0.0.0",
    port: 5173,
    open: "/login",
    headers: {
      "Cross-Origin-Opener-Policy": "same-origin-allow-popups",
      "Cross-Origin-Embedder-Policy": "unsafe-none",
    },
    proxy: {
      "/public": {
        target: "http://localhost",
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/public/, ""),
      },
    },
  },
  plugins: [vue()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "src"),
    },
  },
  build: {
    minify: "esbuild", // 啟用生產環境的優化
    target: "esnext", // 或指定其他適合的目標版本
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, "index.html"), // 所有頁面共用 index.html
      },
    },
  },
});
