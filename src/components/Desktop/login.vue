<script setup>
import { ref } from 'vue'
import { useFilterStore } from '@/stores/filterStore2'
import { useRouter } from "vue-router"; // 引入 useRouter 和 useRoute

const router = useRouter();
const filterStore = useFilterStore()
const user_account = ref('')
const password = ref('')
const message = ref('')
const isActive = ref(true)
const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID

async function login() {
    filterStore.clearStorage();
    const formData = new FormData()
    formData.append('useraccount', user_account.value)
    formData.append('password', password.value)
    try {
        const res = await fetch(`/login.php`, {
            method: 'POST',
            credentials: 'include',    // ← 一定要加
            body: formData
        })
        const json = await res.json()
        if (res.ok && json.message === '登入成功') {
            filterStore.setUsername(json.account_name, json.user_id)
            // 存入 email
            filterStore.setEmail(json.account_email)
            filterStore.setPhone(json.account_phone)
            filterStore.setUserprofile(json.account_lastname, json.account_firstname, json.account_address)
            filterStore.setRole(json.role);
            // 導到購物頁
            await filterStore.loadCart()
            await filterStore.loadFavorite()
            await filterStore.loadCoupons()
            router.push({ name: 'PT' })
        } else {
            message.value = `❌ 登入失敗：${json.message}`
            user_account.value = ''
            password.value = ''
        }

    } catch (err) {
        console.error(err)
        message.value = '❌ 發生錯誤，請稍後再試'
    }
}

function signup() {
    router.push({ name: "Register" })
};

async function requestResetPassword() {
    if (!user_account.value) {
        message.value = "請先輸入 Email"
        return
    }
    message.value = "寄信中…"

    const formData = new FormData()
    formData.append("email", user_account.value)

    try {
        const res = await fetch(`/check_email.php`, {
            method: "POST",
            body: formData,
        })
        const data = await res.json()
        if (data.success) {
            message.value = "已寄送重設密碼的郵件，請查看您的信箱"
        } else {
            message.value = "❌ 查無此帳號，請確認輸入是否正確"
        }
    } catch (err) {
        console.error("Error:", err)
        message.value = "系統錯誤，請稍後再試"
    }
}
const callback = async (response) => {
    // response 物件會包含 Google 回傳的 credential (id_token)
    if (!response || !response.credential) {
        message.value = 'Google 登入失敗'
        return
    }
    filterStore.clearStorage();
    try {
        const res = await fetch(`/google_login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ credential: response.credential }),
        });

        const text = await res.text();

        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            console.error('JSON parsing failed:', e);
            message.value = '❌ 系統回傳格式錯誤，請稍後再試';
            return;
        }

        if (json.success) {
            filterStore.setUsername(json.account_name, json.user_id);
            filterStore.setEmail(json.account_email);
            filterStore.setPhone(json.account_phone);
            filterStore.setUserprofile(json.account_lastname, json.account_firstname, json.account_address);
            filterStore.setRole(json.role);

            await filterStore.loadCart();
            await filterStore.loadFavorite();
            await filterStore.loadCoupons();
            router.push({ name: 'PT' });
        } else if (json.need_register) {
            router.push({
                name: 'Register',
                query: {
                    email: json.email,
                    name: json.name,
                },
            });
        } else {
            message.value = `❌ Google 登入失敗：${json.message || '未知錯誤'}`;
        }
    } catch (error) {
        message.value = '❌ 系統錯誤，請稍後再試';
        console.error(error);
    }

}

</script>

<template>
    <div class="container">
        <div class="top">
            <router-link to="/introduction" class="logo-link">
                <img src="/img/home_img.png" alt="首頁" />
            </router-link>
            <div class="title">登入</div>
        </div>
        <div class="middle">
            <div class="login_area">
                <div class="login-word">登入</div>
                <form class="input_wrapper" @submit.prevent="login">
                    <input type="text" class="user_name" v-model="user_account" placeholder="電話號碼 / 使用者名稱 / Email"
                        required autocomplete />
                    <div class="showpwd">
                        <input :type="isActive ? 'password' : 'text'" class="pwd" v-model="password"
                            autocomplete="current-password" placeholder="密碼" required />
                        <i id="checkEye" :class="['fas', isActive ? 'fa-eye' : 'fa-eye-slash']"
                            @click="isActive = !isActive" aria-label="切換密碼顯示" role="button" tabindex="0"></i>
                    </div>
                    <button type="submit" class="login_button">登入</button>
                </form>
                <span v-if="message" class="message">{{ message }}</span>
                <div class="reset">
                    <button @click="requestResetPassword" class="requestResetPassword">忘記密碼？</button>
                    <button @click="filterStore.guestLogin()" class="guest-login">訪客登入</button>
                </div>
                <div class="divider">
                    <div class="line"></div>
                    <span class="or">或</span>
                    <div class="line"></div>
                </div>
                <div style="margin-bottom: 24px;">
                    <GoogleLogin :clientId="googleClientId" :callback="callback" class="google-login" />
                </div>
                <button @click="signup" class="signup">註冊新帳號</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f9fafb;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

.top {
    display: flex;
    align-items: center;
    padding: 20px 40px;
    background: white;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
}

.logo-link img {
    width: 48px;
    height: 48px;
    object-fit: contain;
}

.title {
    font-size: 28px;
    font-weight: 700;
    padding-left: 20px;
    color: #4a4a4a;
    opacity: 0.85;
}

.middle {
    flex-grow: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 20px;
}

.login_area {
    width: 380px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 24px rgb(0 0 0 / 0.12);
    padding: 40px 32px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.login-word {
    font-size: 28px;
    font-weight: 700;
    color: #222;
    align-self: flex-start;
    margin-bottom: 28px;
}

.input_wrapper {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.input_wrapper input {
    height: 44px;
    border: 1.5px solid #ccc;
    border-radius: 6px;
    padding: 0 12px;
    font-size: 16px;
    transition: border-color 0.25s ease;
}

.input_wrapper input:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 6px #3b82f6aa;
}

input::placeholder {
    color: #999;
}

.showpwd {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
}

.pwd {
    flex-grow: 1;
    /* 撐滿剩餘空間 */
    height: 40px;
    padding-left: 10px;
    padding-right: 40px;
    /* 預留眼睛圖示空間 */
    box-sizing: border-box;
    border: 1px solid gray;
    border-radius: 3px;
}

#checkEye {
    cursor: pointer;
    position: absolute;
    right: 10px;
    user-select: none;
}

#checkEye:hover {
    color: #3b82f6;
}

.login_button {
    margin-top: 24px;
    background: #3b82f6;
    border: none;
    color: white;
    height: 44px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    width: 100%;
    box-shadow: 0 4px 10px rgb(59 130 246 / 0.5);
}

.login_button:hover {
    background: #2563eb;
}

.message {
    color: #e53e3e;
    margin-top: 12px;
    font-size: 14px;
    min-height: 20px;
    text-align: center;
}

.reset {
    margin-top: 16px;
    width: 100%;
    display: flex;
    justify-content: space-between;
}

.reset button {
    background: none;
    border: none;
    color: #3b82f6;
    font-size: 13px;
    cursor: pointer;
    transition: color 0.25s ease;
    padding: 0;
    user-select: none;
}

.reset button:hover {
    color: #1e40af;
}

.divider {
    margin: 30px 0 24px;
    display: flex;
    align-items: center;
    width: 100%;
}

.line {
    flex-grow: 1;
    height: 1px;
    background: #ccc;
    opacity: 0.6;
}

.or {
    margin: 0 16px;
    color: #666;
    font-size: 14px;
    font-weight: 500;
}

.google-login {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.google-login>* {
    width: auto !important;
    /* 避免 100% 寬度的預設 */
    max-width: 100%;
}

.signup {
    background: #f97316;
    border: none;
    color: white;
    height: 44px;
    width: 100%;
    border-radius: 8px;
    font-weight: 600;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    box-shadow: 0 4px 10px rgb(249 115 22 / 0.5);
}

.signup:hover {
    background: #c2410c;
}

.guest-login {
    font-size: 12px;
    color: #555 !important;
    cursor: pointer;
    border-radius: 4px;
    user-select: none;
    padding: 2px 6px;
    border: 1px solid #bbb;
    transition: all 0.3s ease;
    background: #fafafa;
}

.guest-login:hover {
    background: #f0f0f0;
    border-color: #888;
}

@media (max-width: 480px) {
    .login_area {
        width: 90vw;
        padding: 32px 24px;
    }

    .top {
        padding: 12px 20px;
    }

    .title {
        font-size: 22px;
        padding-left: 12px;
    }
}
</style>
