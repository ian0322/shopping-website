import {
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from "vue-router";
import { useFilterStore } from "./stores/filterStore2";
import Login from "./components/Desktop/login.vue";
import Register from "./components/Desktop/register.vue";
import Shop from "../src/Shop.vue";
import Shopcart from "../src/Shopcart.vue";
import Shopcartform from "../src/Shopcartform.vue";
import Upload from "../src/Upload.vue";
import UploadCP from "../src/UploadCP.vue";
import Favorite from "../src/Favorite.vue";
import UserInfo from "../src/UserInfo.vue";
import HistoryOrder from "../src/HistoryOrder.vue";
import LostPwd from "../src/LostPwd.vue";
import Introduction from "../src/Introduction.vue";
import PT from "../src/Producttest.vue";
import CP from "../src/CouponList.vue";
import test from "./components/Desktop/test.vue";
import MPT from "../src/Mobile.vue";
const routes = [
  { path: "/", redirect: "/login" },
  { path: "/login", name: "login", component: Login },
  { path: "/register", name: "Register", component: Register },
  { path: "/shop", name: "Shop", component: Shop },
  { path: "/shopcart", name: "Shopcart", component: Shopcart },
  { path: "/shopcartform", name: "Shopcartform", component: Shopcartform },
  { path: "/favorite", name: "FavoritePage", component: Favorite },
  { path: "/historyorder", name: "HistoryPage", component: HistoryOrder },
  { path: "/userInfo", name: "UserInfo", component: UserInfo },
  {
    path: "/upload",
    name: "Upload",
    component: Upload,
    meta: { requiresAdmin: true },
  },
  {
    path: "/uploadCP",
    name: "/UploadCP",
    component: UploadCP,
    meta: { requiresAdmin: true },
  },
  { path: "/lostpwd", name: "LostPwd", component: LostPwd },
  { path: "/introduction", name: "Inctroduction", component: Introduction },
  { path: "/PT", name: "PT", component: PT },
  { path: "/CP", name: "CP", component: CP },
  { path: "/test", name: "test", component: test },
  { path: "/MPT", name: "MPT", component: MPT },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});
router.beforeEach(async (to, from, next) => {
  const filterStore = useFilterStore();

  // 若頁面需要 admin 權限
  if (to.meta.requiresAdmin) {
    try {
      const res = await fetch(`/adminCheck.php`, {
        credentials: "include", // 很重要！要讓 cookie 帶上
      });
      const data = await res.json();

      if (data.status === "ok" && data.role === "admin") {
        filterStore.setRole("admin"); // 可同步更新前端 store
        next();
      } else {
        alert("權限不足");
        next("/PT"); // 或導向登入或其他頁面
      }
    } catch (err) {
      console.error("權限驗證錯誤:", err);
      next("/PT"); // 錯誤也不允許進入
    }
  } else {
    next();
  }
});

export default router;
