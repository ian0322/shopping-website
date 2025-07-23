import { createApp } from "vue";
import { createPinia } from "pinia";
import GoogleLoginPlugin from "vue3-google-login";
import App from "./App.vue"; // ✅ 注意：這裡建議掛 App.vue，不是 login.vue
import router from "./router";

const app = createApp(App); // ✅ 正確：使用 App.vue 作為入口元件
app.use(createPinia());
app.use(router);
app.use(GoogleLoginPlugin, {
  clientId:
    "486114677132-50ldrfrloe48chpmr9rflf10dnciiv4o.apps.googleusercontent.com",
});

app.mount("#app");
