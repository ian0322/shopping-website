import { defineStore } from "pinia";
import { ref, computed, watch, nextTick } from "vue";
import { useRouter } from "vue-router";
export const useFilterStore = defineStore("filter", () => {
  const cached = ref(false);

  //const apiHost = fetch(`/`;
  const apiHost = import.meta.env.VITE_API_HOST;
  const router = useRouter();
  const products = ref([]);
  const allTags = ref([]);
  const searchInput = ref("");
  const searchText = ref("");
  const productToDelete = ref(null);
  const showModal = ref(false);
  const animatingId = ref(null); // 記住哪個愛心在動畫中
  const hoveredNavSub = ref(null);

  const role = ref(localStorage.getItem("role") || ""); // 初始化時嘗試從 localStorage 讀取
  const username = ref(localStorage.getItem("username") || ""); // 初始化時嘗試從 localStorage 讀取
  const email = ref(localStorage.getItem("email") || "");
  const phone = ref(localStorage.getItem("phone") || "");
  const userId = ref(localStorage.getItem("user_id") || "");
  const lastname = ref(localStorage.getItem("lastname"));
  const firstname = ref(localStorage.getItem("firstname"));
  const address = ref(localStorage.getItem("address"));

  const guestId = ref(localStorage.getItem("guest_id") || "");
  const oldguestId = ref(localStorage.getItem("guest_id") || "");

  const coupons = ref([]);

  // 若不存在，生成一個新的 guestId 並儲存到 localStorage
  const Usershopcart = ref([]);
  const Guestshopcart = ref([]);
  const favoriteList = ref([]);
  const confirmtobuy = ref([]);

  const multiSelectableTags = ["全新商品", "優惠商品"];
  const checkboxTags = ["男裝", "女裝", "童裝"];
  const checkboxSelectedTags = ref([]);

  const shopcart = computed(() => {
    if (userId.value) return Usershopcart.value;
    if (guestId.value) return Guestshopcart.value;
    return [];
  });

  const tagHierarchy = {
    上衣: ["長袖上衣", "短袖上衣"],
    褲子: ["長褲", "短褲"],
  };

  const expandedTag = ref(null);
  const selectedTag = ref(null);
  const selectedMultiTag = ref(null);
  const selectedSize = ref(null);

  const navTopTags = [
    "所有商品",
    "全新商品",
    "優惠商品",
    "男裝",
    "女裝",
    "童裝",
  ];
  const hoveredNavTop = ref(null);
  const activeNavTop = ref(null);
  const activeNavSub = ref(null);

  const mainCategories = Object.keys(tagHierarchy);
  const setPhone = (p) => {
    phone.value = p;
    localStorage.setItem("phone", p);
  };
  const setEmail = (e) => {
    email.value = e;
    localStorage.setItem("email", e);
  };
  const setRole = (r) => {
    role.value = r;
    localStorage.setItem("role", r);
  };
  const setUsername = (name, id) => {
    username.value = name;
    userId.value = id;
    localStorage.setItem("username", name);
    localStorage.setItem("user_id", id);
  };
  const setUserprofile = (last, first, addr) => {
    lastname.value = last ?? "";
    firstname.value = first ?? "";
    address.value = addr ?? "";
    localStorage.setItem("lastname", lastname.value);
    localStorage.setItem("firstname", firstname.value);
    localStorage.setItem("address", address.value);
  };

  const setGuestId = (id) => {
    guestId.value = id;
    oldguestId.value = guestId.value;
    localStorage.setItem("guest_id", id);
  };

  const loadProducts = async (force = false) => {
    try {
      if (userId.value) {
        await loadFavorite(); // 等待 favorite 載入完成
        await loadCoupons();
      }

      const res = await fetch(`/shopping.php`, {
        method: "GET",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
      });

      const data = await res.json();

      products.value = data.images.map((p) => ({
        ...p,
        tagList: p.tags
          ? p.tags
              .split(",")
              .map((t) => t.trim())
              .filter((t) => t)
          : [],
      }));

      allTags.value = [...new Set(products.value.flatMap((p) => p.tagList))];
    } catch (err) {
      console.error("Error fetching:", err);
    }
  };

  const otherTags = computed(() => {
    if (!Array.isArray(allTags.value)) return [];

    const subTags = Object.values(tagHierarchy).flat();
    return allTags.value.filter(
      (tag) =>
        !mainCategories.includes(tag) &&
        !subTags.includes(tag) &&
        !multiSelectableTags.includes(tag) &&
        !checkboxTags.includes(tag)
    );
  });
  const hoveredNavSubTags = computed(() => {
    if (!hoveredNavTop.value) return [];

    const filtered = products.value.filter((p) =>
      p.tagList.includes(hoveredNavTop.value)
    );
    const exclude = checkboxTags;
    const set = new Set();

    filtered.forEach((p) => {
      p.tagList.forEach((tag) => {
        if (!exclude.includes(tag) && tag !== hoveredNavTop.value) {
          set.add(tag);
        }
      });
    });

    const sortPriority = [
      "全新商品",
      "優惠商品",
      "短袖上衣",
      "長袖上衣",
      "短褲",
      "長褲",
    ];

    return Array.from(set).sort((a, b) => {
      const indexA = sortPriority.indexOf(a);
      const indexB = sortPriority.indexOf(b);

      if (indexA === -1 && indexB === -1) return a.localeCompare(b);
      if (indexA === -1) return 1;
      if (indexB === -1) return -1;
      return indexA - indexB;
    });
  });

  const filteredProducts = computed(() => {
    if (!Array.isArray(products.value)) return [];

    let result = products.value;

    if (selectedTag.value) {
      if (tagHierarchy[selectedTag.value]) {
        const subs = tagHierarchy[selectedTag.value];
        result = result.filter((p) =>
          p.tagList.some((tag) => subs.includes(tag))
        );
      } else {
        result = result.filter((p) => p.tagList.includes(selectedTag.value));
      }
    }

    if (selectedMultiTag.value) {
      result = result.filter((p) => p.tagList.includes(selectedMultiTag.value));
    }

    if (checkboxSelectedTags.value.length > 0) {
      result = result.filter((p) =>
        checkboxSelectedTags.value.some((tag) => p.tagList.includes(tag))
      );
    }

    if (searchText.value.trim()) {
      const keyword = searchText.value.trim().toLowerCase();
      result = result.filter(
        (p) =>
          p.name.toLowerCase().includes(keyword) ||
          p.tagList.some((tag) => tag.toLowerCase().includes(keyword))
      );
    }
    //console.log("result", result);
    return result;
  });

  const availableTags = computed(() => {
    const tagSet = new Set();
    filteredProducts.value.forEach((p) => {
      p.tagList.forEach((tag) => tagSet.add(tag));
    });
    return tagSet;
  });

  const availableCheckboxTags = computed(() => {
    const tagsInFiltered = new Set();
    filteredProducts.value.forEach((p) => {
      p.tagList.forEach((tag) => {
        if (checkboxTags.includes(tag)) {
          tagsInFiltered.add(tag);
        }
      });
    });
    return Array.from(tagsInFiltered).sort((a, b) => {
      if (a.length !== b.length) return b.length - a.length;
      else return b.localeCompare(a);
    });
  });

  const applySearch = () => {
    searchText.value = searchInput.value.trim();
    activeNavTop.value = null;
    activeNavSub.value = null;
    selectedTag.value = null;
    expandedTag.value = null;
    checkboxSelectedTags.value = [];
    selectedMultiTag.value = null;
    searchInput.value = "";
  };

  const toggleTag = (tag) => {
    if (multiSelectableTags.includes(tag)) {
      if (multiSelectableTags.includes(activeNavSub.value)) {
        activeNavSub.value = tag;
      } else if (multiSelectableTags.includes(activeNavTop.value)) {
        activeNavTop.value = tag;
      }
      selectedMultiTag.value = selectedMultiTag.value === tag ? null : tag;
      return;
    } else {
      searchText.value = "";
      const isMain = tagHierarchy[tag] !== undefined;
      const parentMain = Object.entries(tagHierarchy).find(([, subs]) =>
        subs.includes(tag)
      )?.[0];

      if (isMain) {
        if (selectedTag.value === tag && expandedTag.value === tag) {
          // 代表是再次點自己 → 關閉
          expandedTag.value = null;
          selectedTag.value = null;
        } else {
          // 不論是第一次點，還是從 sub 回來點 main → 開啟
          expandedTag.value = tag;
          selectedTag.value = tag;
        }
        return;
      }

      if (parentMain) {
        expandedTag.value = parentMain; // 🔧 加上這行保持父分類展開

        if (selectedTag.value === tag) {
          selectedTag.value = parentMain;
        } else {
          selectedTag.value = tag;
        }
        return;
      }
      selectedTag.value = selectedTag.value === tag ? null : tag;
    }
  };
  const clearFilter = () => {
    activeNavTop.value = null;
    activeNavSub.value = null;
    selectedTag.value = null;
    expandedTag.value = null;
    selectedMultiTag.value = null;
    checkboxSelectedTags.value = [];
    searchText.value = "";
  };
  const onNavTopClick = (tag) => {
    nextTick(() => {
      if (router.name !== "PT") {
        router.push({ name: "PT" });
      }
    });
    if (tag === "所有商品") {
      activeNavTop.value = null;
      activeNavSub.value = null;
      selectedTag.value = null;
      expandedTag.value = null;
      selectedMultiTag.value = null;
      checkboxSelectedTags.value = [];
      searchText.value = "";
      return;
    }
    if (multiSelectableTags.includes(tag)) {
      selectedMultiTag.value = tag;
    } else {
      selectedMultiTag.value = null;
    }
    activeNavTop.value = activeNavTop.value === tag ? null : tag;
    activeNavSub.value = null;
    searchText.value = "";
  };

  const onNavSubClick = (subTag) => {
    nextTick(() => {
      if (router.name !== "PT") {
        router.push({ name: "PT" });
      }
    });
    activeNavSub.value = activeNavSub.value === subTag ? null : subTag;
    if (hoveredNavTop.value) activeNavTop.value = hoveredNavTop.value;
    selectedMultiTag.value = null;
    searchText.value = "";
  };
  const updateCart = (cartKey, product, cartStore) => {
    const cart = cartStore.value;

    const sizeStock = parseSizeToStock(product.sizeRaw || "");
    const maxStock = sizeStock[product.size] || 0;

    // 找是否已有相同 id + 尺寸的商品
    const sameIdx = cart.findIndex(
      (item) => item.id === product.id && item.size === product.size
    );

    if (sameIdx > -1) {
      if (cart[sameIdx].quantity < maxStock) {
        cart[sameIdx].quantity++;
      } else {
        alert(`已達庫存上限，最多只能購買 ${maxStock} 件`);
        return false;
      }
    } else {
      // 新增全新項目
      if (maxStock > 0) {
        cart.push({
          id: product.id,
          name: product.name,
          price: product.price,
          file_path: product.file_path,
          size: product.size,
          quantity: 1,
          sizeRaw: product.sizeRaw,
        });
      } else {
        alert("該尺寸無庫存，無法加入購物車");
        return false;
      }
    }

    localStorage.setItem(cartKey, JSON.stringify(cart));
    return true;
  };

  const updateCartSize = (cartKey, product, oldSize, newSize, cartStore) => {
    const cart = cartStore.value;
    const sizeStock = parseSizeToStock(product.sizeRaw || "");
    const maxStock = sizeStock[newSize] || 0;
    if (maxStock === 0) {
      alert("該尺寸無庫存，無法更改");
      return false;
    }

    const oldIndex = cart.findIndex(
      (item) => item.id === product.id && item.size === oldSize
    );
    if (oldIndex === -1) {
      alert("找不到原商品");
      return false;
    }

    if (oldSize === newSize) {
      return true;
    }

    const newIndex = cart.findIndex(
      (item) => item.id === product.id && item.size === newSize
    );

    if (newIndex > -1) {
      const totalQty =
        (parseInt(cart[oldIndex].quantity) || 0) +
        (parseInt(cart[newIndex].quantity) || 0);
      if (totalQty > maxStock) {
        alert(
          `${product.name} 尺寸:${cart[newIndex].size} 已達庫存上限，最多只能購買 ${maxStock} 件`
        );
        return false;
      }
      // 合併數量並保持 selected 狀態
      cart[newIndex].quantity = totalQty;
      if (cart[oldIndex].selected !== undefined) {
        cart[newIndex].selected =
          cart[newIndex].selected || cart[oldIndex].selected;
      }
      cart.splice(oldIndex, 1);
    } else {
      if ((parseInt(cart[oldIndex].quantity) || 0) > maxStock) {
        alert(
          `${product.name} 尺寸:${newSize}已達庫存上限，最多只能購買 ${maxStock} 件`
        );
        return false;
      }
      // 改用 Vue.set 或用展開運算子替換，確保 reactivity
      cart[oldIndex] = {
        ...cart[oldIndex],
        size: newSize,
      };
    }

    cartStore.value = [...cart];
    sessionStorage.setItem("confirmtobuy", JSON.stringify(cartStore.value));
    localStorage.setItem(cartKey, JSON.stringify(cartStore.value));
    return true;
  };

  const addToFavorite = async (product) => {
    if (!userId.value) {
      alert("請登入會員使用此功能");
      return;
    }
    animatingId.value = product.id;

    // 👉 清除動畫 class
    setTimeout(() => {
      animatingId.value = null;
    }, 800); // 0.8s 動畫一次
    const isFavorite = favoriteList.value.some((i) => i.id == product.id);

    if (!isFavorite) {
      favoriteList.value = [...favoriteList.value, { ...product }];
      await updateFavoriteToBackend(product.id, "add");
    } else {
      favoriteList.value = favoriteList.value.filter((i) => i.id != product.id);
      await updateFavoriteToBackend(product.id, "remove");
    }

    localStorage.setItem(
      `favorite_${userId.value}`,
      JSON.stringify(favoriteList.value)
    );

    await nextTick(); // 等待 Vue 完成 DOM 更新
    //console.log("favoriteList 更新完成:", favoriteList.value);
  };

  // 👉 初始化 favoriteList（頁面重新整理時）
  const updateFavoriteToBackend = async (productId, action) => {
    await fetch(`/save_favorite_list.php`, {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ product_id: productId, action }),
    }).then((res) => {
      if (!res.ok) throw new Error("後端錯誤");
      return res.json();
    });
  };
  function parseSizeToStock(sizeString) {
    if (!sizeString) return {}; // 防止 undefined 跑錯
    return sizeString.split(",").reduce((acc, item) => {
      const [size, qty] = item.split(":");
      acc[size] = parseInt(qty);
      return acc;
    }, {});
  }
  const addToCart = (product) => {
    if (!selectedSize.value) {
      alert("請先選擇尺寸");
      return false;
    }

    const productWithSize = {
      id: Number(product.id),
      name: product.name,
      price: Number(product.price),
      file_path: product.file_path?.replace(/^\//, ""),
      size: selectedSize.value,
      sizeRaw: product.size,
    };

    const cartStore = userId.value ? Usershopcart : Guestshopcart;
    const cartKey = userId.value ? userId.value : guestId.value;

    const success = updateCart(cartKey, productWithSize, cartStore);
    if (success) syncCart();

    return success; // 🔴 回傳是否成功加入
  };

  const removeFromFav = (id) => {
    if (userId.value) {
      const index = favoriteList.value.findIndex((i) => i.id === id);
      if (index !== -1) {
        favoriteList.value.splice(index, 1); // ✅ Vue 能偵測這種操作，會套用 leave 動畫
      }
      updateFavoriteToBackend(id, "remove");
    }
  };
  const removeFromCart = (id, size) => {
    if (userId.value) {
      Usershopcart.value = Usershopcart.value.filter(
        (i) => !(i.id === id && i.size === size)
      );
      syncCart();
    } else if (guestId.value) {
      Guestshopcart.value = Guestshopcart.value.filter(
        (i) => !(i.id === id && i.size === size)
      );
      syncCart();
    }
  };
  const removeSelectedFromCart = () => {
    if (userId.value) {
      // 會員
      Usershopcart.value = Usershopcart.value.filter((i) => !i.selected);
      syncCart(); // 先儲存購物車
    } else if (guestId.value) {
      Guestshopcart.value = Guestshopcart.value.filter((i) => !i.selected);
      syncCart(); // 先儲存購物車
    }
  };
  const increase = (product) => {
    const item = shopcart.value.find(
      (i) => i.id === product.id && i.size === product.size
    );

    const sizeStock = parseSizeToStock(product.sizeRaw || ""); // 轉為 {S:2, M:3, L:0}
    const maxStock = sizeStock[product.size] || 0;

    if (item && item.quantity < maxStock) {
      item.quantity += 1;
      syncCart();
    } else if (item && item.quantity >= maxStock) {
      alert(
        `${product.name} 尺寸:${product.size} 已達庫存上限，最多只能購買 ${maxStock} 件`
      );
    }
  };
  const decrease = (product) => {
    const item = shopcart.value.find(
      (i) => i.id === product.id && i.size === product.size
    );
    if (item && item.quantity === 1) {
      productToDelete.value = product;
      showModal.value = true; //****控制小於一刪除
    } else if (item && item.quantity > 1) {
      item.quantity -= 1;
    }
    syncCart();
  };

  const deleteSelected = () => {
    // 確保選擇了至少一個商品
    const selectedProducts = shopcart.value.filter((item) => item.selected);
    if (selectedProducts.length > 0) {
      showModal.value = true; // 顯示 modal
    } else {
      alert("請選擇要刪除的商品");
    }
  };
  const confirmDelete = () => {
    if (productToDelete.value) {
      // 單筆刪除
      removeFromCart(productToDelete.value.id, productToDelete.value.size);
      productToDelete.value = null;
    } else {
      // 多筆刪除
      removeSelectedFromCart();
    }
    showModal.value = false;
  };

  const cancelDelete = () => {
    // 不刪除並還原數量為 1
    if (productToDelete.value) {
      productToDelete.value.quantity = 1;
    }
    productToDelete.value = null;
    showModal.value = false;
  };
  function toBuy(items) {
    confirmtobuy.value = items.map((item) => ({
      ...item,
      selected: true, // 或 false，看你預設
    }));
    sessionStorage.setItem("confirmtobuy", JSON.stringify(confirmtobuy.value));
  }
  async function loadFavorite(retry = 1) {
    try {
      if (!userId.value) return;

      const res = await fetch(`/load_favorite_list.php`, {
        method: "GET",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
      });

      const contentType = res.headers.get("content-type");
      if (
        !res.ok ||
        !contentType ||
        !contentType.includes("application/json")
      ) {
        const text = await res.text();
        throw new Error(`後端回傳非 JSON 或錯誤: ${text.slice(0, 100)}`);
      }

      const { favorite = [] } = await res.json();
      favoriteList.value = [...favorite];
      localStorage.setItem(
        `favorite_${userId.value}`,
        JSON.stringify(favorite)
      );
    } catch (err) {
      console.error("loadFavorite 錯誤:", err);
      if (retry > 0) {
        console.warn(`loadFavorite 重試中，剩餘次數: ${retry}`);
        await new Promise((resolve) => setTimeout(resolve, 500)); // 延遲 0.5 秒
        return loadFavorite(retry - 1);
      }
    }
  }
  async function loadCart() {
    //console.log("呼叫 loadCart");
    try {
      if (userId.value) {
        const res = await fetch(`/load_cart.php`, {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ user_id: userId.value }),
        });
        const { cart = [] } = await res.json();

        // ✅ 檢查每個商品尺寸是否還有庫存
        cart.forEach((item) => {
          const stockObj = parseSizeToStock(item.sizeRaw);
          item.outOfStock = !stockObj[item.size] || stockObj[item.size] <= 0;
          item.showRemove = false;
        });

        Usershopcart.value = [...cart];
        localStorage.setItem(userId.value, JSON.stringify(cart));
      } else if (guestId.value) {
        // 訪客
        const res = await fetch(`/load_guest_cart.php`, {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ user_id: guestId.value }),
        });
        const { guest_cart = [] } = await res.json();

        guest_cart.forEach((item) => {
          const stockObj = parseSizeToStock(item.sizeRaw);
          item.outOfStock = !stockObj[item.size] || stockObj[item.size] <= 0;
          item.showRemove = false;
        });

        Guestshopcart.value = [...guest_cart];
        localStorage.setItem(guestId.value, JSON.stringify(guest_cart));
      }
    } catch (e) {
      console.error("載入購物車資料失敗:", e);
    }
  }
  async function loadCoupons() {
    try {
      const response = await fetch(`/loadCoupon.php`, {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
      });
      const result = await response.json();

      if (response.ok && result.status === "success") {
        coupons.value = result.data;
        localStorage.setItem("Coupon", JSON.stringify(coupons.value));
      } else {
        console.error("優惠券回傳錯誤:", result.message);
      }
    } catch (e) {
      console.error("載入優惠券資料失敗:", e);
    }
  }
  const selectedCoupon = ref(null);

  // 選擇或取消優惠券
  function selectCoupon(coupon) {
    if (
      selectedCoupon.value &&
      selectedCoupon.value.user_coupon_id === coupon.user_coupon_id
    ) {
      // 如果已選的是同一張，點擊則取消
      selectedCoupon.value = null;
      localStorage.removeItem("selectedCoupon");
    } else {
      // 選擇新的優惠券
      selectedCoupon.value = coupon;
      localStorage.setItem("selectedCoupon", JSON.stringify(coupon));
    }
  }

  // 頁面初始化時從 localStorage 載入
  function loadSelectedCoupon() {
    const stored = localStorage.getItem("selectedCoupon");
    if (stored) {
      selectedCoupon.value = JSON.parse(stored);
    }
  }
  watch([activeNavTop, activeNavSub], ([top, sub]) => {
    if (!top) {
      checkboxSelectedTags.value = [];
      selectedTag.value = null;
      expandedTag.value = null;
      selectedMultiTag.value = null;
      return;
    }

    // 判斷 top 是否為 checkbox
    checkboxSelectedTags.value = checkboxTags.includes(top) ? [top] : [];

    // 判斷 top 或 sub 是否為 multiSelectable
    if (multiSelectableTags.includes(top)) {
      selectedMultiTag.value = top;
    } else if (multiSelectableTags.includes(sub)) {
      selectedMultiTag.value = sub;
    } else {
      selectedMultiTag.value = null;
    }

    // ✅ 關鍵判斷：只要 sub 存在，且不是特殊類別，就設 selectedTag
    if (
      sub &&
      !multiSelectableTags.includes(sub) &&
      !checkboxTags.includes(sub)
    ) {
      selectedTag.value = sub;

      // 嘗試展開主分類
      let foundMain = null;
      for (const main of mainCategories) {
        if (tagHierarchy[main]?.includes(sub)) {
          foundMain = main;
          break;
        }
      }
      expandedTag.value = foundMain;
    } else {
      // fallback，sub 無效時才用 top
      if (!multiSelectableTags.includes(top) && !checkboxTags.includes(top)) {
        selectedTag.value = top;
        expandedTag.value = mainCategories.includes(top) ? top : null;
      } else {
        selectedTag.value = null;
        expandedTag.value = null;
      }
    }
  });
  watch(
    Usershopcart,
    (val) => {
      if (userId.value) {
        localStorage.setItem(userId.value, JSON.stringify(val));
      }
    },
    { deep: true }
  );

  watch(
    Guestshopcart,
    (val) => {
      if (guestId.value) {
        localStorage.setItem(guestId.value, JSON.stringify(val));
      }
    },
    { deep: true }
  );

  watch(
    [userId, guestId, oldguestId],
    ([newUserId, newGuestId, tempguestId]) => {
      if (newUserId || newGuestId || tempguestId) {
        loadCart();
      }
    },
    { immediate: true }
  );

  // 加 immediate 會在初始化就執行一次
  // 加入／移除時，同步後端
  async function syncCart() {
    // 如果有 userId，儲存會員的購物車
    if (userId.value) {
      await fetch(`/save_cart.php`, {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(
          shopcart.value.map((i) => ({
            product_id: i.id,
            size: i.size,
            quantity: i.quantity,
          }))
        ),
      });
    }
    // 如果沒有 userId，但有 guestId，儲存訪客的購物車
    else if (guestId.value) {
      await fetch(`/save_guest_cart.php`, {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(
          shopcart.value.map((i) => ({
            product_id: i.id,
            size: i.size,
            quantity: i.quantity,
          }))
        ),
      });
    }
  }
  async function guestLogin() {
    const res = await fetch(`/login.php`, {
      method: "POST",
      credentials: "include",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        is_guest: "1",
      }),
    });
    const json = await res.json();
    // 刪除 localStorage：只刪會員 cart & 待購清單
    clearStorage();
    // 重置 store
    username.value = "訪客";
    userId.value = null;

    setGuestId(json.guest_id);
    await loadCart(); // 登入後立即載入購物車
    router.push({ name: "PT" });
  }
  async function clearStorage() {
    localStorage.removeItem("username");
    localStorage.removeItem("email");
    localStorage.removeItem("phone");
    localStorage.removeItem("user_id");
    localStorage.removeItem("lastname");
    localStorage.removeItem("firstname");
    localStorage.removeItem("address");
    localStorage.removeItem(userId.value);
    sessionStorage.removeItem("confirmtobuy"); // ← 清除 confirmtobuy
    localStorage.removeItem(`favorite_${userId.value}`);
    localStorage.removeItem("role");
    localStorage.removeItem("Coupon");
    localStorage.removeItem("selectedCoupon");
  }
  return {
    role,
    userId,
    guestId,
    username,
    email,
    phone,
    lastname,
    firstname,
    address,
    apiHost,
    products,
    allTags,
    searchInput,
    searchText,
    multiSelectableTags,
    checkboxTags,
    checkboxSelectedTags,
    tagHierarchy,
    expandedTag,
    selectedTag,
    selectedMultiTag,
    navTopTags,
    hoveredNavTop,
    activeNavTop,
    activeNavSub,
    mainCategories,
    hoveredNavSubTags,
    filteredProducts,
    availableTags,
    availableCheckboxTags,
    otherTags,
    shopcart,
    favoriteList,
    showModal,
    animatingId,
    checkboxSelectedTags,
    hoveredNavSub,
    confirmtobuy,
    selectedSize,
    Usershopcart,
    Guestshopcart,
    coupons,
    selectedCoupon,
    setPhone,
    setEmail,
    setUsername,
    setUserprofile,
    setGuestId,
    loadProducts,
    applySearch,
    toggleTag,
    onNavTopClick,
    onNavSubClick,
    updateCart, //
    addToFavorite,
    addToCart,
    removeFromFav,
    removeFromCart,
    removeSelectedFromCart,
    increase,
    decrease,
    deleteSelected,
    confirmDelete,
    cancelDelete,
    toBuy,
    loadFavorite, //
    loadCart, //
    syncCart, //
    guestLogin,
    parseSizeToStock,
    updateCartSize,
    clearFilter,
    clearStorage,
    setRole,
    loadCoupons,
    selectCoupon,
    loadSelectedCoupon,
  };
});
