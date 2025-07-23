<script setup>
import { ref, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
const router = useRouter();
import { onMounted } from 'vue';
const route = useRoute();

const passwordError = ref('')

const form = reactive({
    username: '',
    email: '',
    password: '',
    confirm_pwd: '',
    phone: '',
    birth: '',
    captcha: '',
});

const isActive = ref(true)
const errors = ref({}); // 用來儲存表單錯誤訊息
const captchaUrl = ref(
    `/captcha.php?` + new Date().getTime()
);

const refreshCaptcha = () => {
    captchaUrl.value = `/captcha.php?` + new Date().getTime();
    form.captcha = "";
};

const registerUser = async () => {
    if (form.password !== form.confirm_pwd) {
        alert("兩次輸入的密碼不一致！");
        return;
    }

    const formData = new URLSearchParams();
    formData.append("username", form.username);
    formData.append("email", form.email);
    formData.append("password", form.password);
    formData.append("confirm_pwd", form.confirm_pwd);
    formData.append("phone", form.phone);
    formData.append("birth", form.birth);
    formData.append("captcha", form.captcha);

    try {
        const response = await fetch(`/register.php`, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: formData.toString(),
            mode: "cors",
            credentials: "include", // <--- 這行很關鍵
        });

        const data = await response.json();
        if (data.success) {
            // 清空表單和錯誤訊息
            Object.keys(form).forEach((key) => form[key] = '');
            errors.value = {};
            refreshCaptcha();
            router.push({ name: "login" });
        } else {
            errors.value = {}; // 清空之前的錯誤
            if (Array.isArray(data.messages)) {
                data.messages.forEach((msg) => {
                    if (msg.includes("用戶名")) {
                        errors.value.username = msg;
                    }
                    if (msg.includes("電子信箱")) {
                        errors.value.email = msg;
                    }
                    if (msg.includes("手機號碼")) {
                        errors.value.phone = msg;
                    }
                    if (msg.includes("驗證碼")) {
                        errors.value.captcha = msg;
                    }
                });
            }
            refreshCaptcha();
        }
    } catch (error) {
        console.error("錯誤:", error);
    }
};
const validatePassword = () => {
    const pwd = form.password
    if (pwd.length < 8) {
        passwordError.value = '密碼至少需要 8 個字元'
    } else if (!/[a-zA-Z]/.test(pwd)) {
        passwordError.value = '密碼必須包含至少一個英文字母'
    } else {
        passwordError.value = ''
    }
}
onMounted(() => {
    if (route.query.email) {
        form.email = route.query.email;
    }
    if (route.query.name) {
        form.username = route.query.name;
    }
});
</script>

<template>
    <div class="container">
        <div class="top">
            <router-link to="/login" class="logo-link">
                <img src="/img/home_img.png" />
            </router-link>
            <div class="title">註冊</div>
        </div>
        <div class="middle">
            <form @submit.prevent="registerUser" class="input_box">
                <div class="signup-word">註冊您的帳號</div>
                <input type="text" class="username" v-model="form.username" placeholder="User name" required />
                <span v-if="errors.username">{{ errors.username }}</span>
                <input type="email" class="email" v-model="form.email" placeholder="Email" required
                    autocomplete="email" />
                <span v-if="errors.email">{{ errors.email }}</span>
                <input :type="isActive ? 'password' : 'text'" class="password" v-model="form.password"
                    placeholder="Password" required autocomplete="new-password" @input="validatePassword" />
                <span v-if="passwordError">{{ passwordError }}</span>
                <div class="confirm_pwddiv">
                    <input :type="isActive ? 'password' : 'text'" class="confirm_pwd" v-model="form.confirm_pwd"
                        placeholder="Confirm password" required autocomplete="new-password" />
                    <i id="checkEye" :class="['fas', isActive ? 'fa-eye' : 'fa-eye-slash']"
                        @click="isActive = !isActive" aria-label="切換密碼顯示" role="button" tabindex="0"></i>
                </div>
                <input type="tel" class="phone" v-model="form.phone" pattern="\d{10}" placeholder="Phone number"
                    required />
                <span v-if="errors.phone">{{ errors.phone }}</span>
                <input type="date" class="birth" v-model="form.birth" required />
                <div class="captcha_box">
                    <input type="text" class="captcha" v-model="form.captcha" name="captcha" placeholder="驗證碼"
                        required />
                    <img :src="captchaUrl" alt="驗證碼圖片" @click="refreshCaptcha" style="cursor: pointer"
                        crossorigin="use-credentials" />
                </div>
                <span v-if="errors.captcha" class="captcha_error">{{ errors.captcha }}</span>
                <button type="submit" class="sign_up"><a>註冊</a></button>
            </form>
        </div>
    </div>
</template>
<style scoped>
input {
    padding-left: 10px;
}

input::placeholder {
    color: rgba(0, 0, 0, 0.3);
}

.container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f9fafb;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    overflow: hidden;
    justify-content: center;
    /* 防止畫面滾動 */
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

form {
    width: 100%;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
}

.signup-word {
    margin-bottom: 40px;
    font-size: 36px;
    font-weight: 700;
    text-align: center;
    color: #333;
}

.input_box {
    width: 380px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 24px rgb(0 0 0 / 0.12);
    padding: 40px 32px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.input_box input {
    width: 100%;
    height: 40px;
    border: 1px solid #ccc;
    border-radius: 10px;
    font-size: 16px;
    transition: border-color 0.3s;
    padding-left: 10px;
    box-sizing: border-box;
}

.input_box input:focus {
    outline: none;
    border-color: #4caf50;
    box-shadow: 0 0 5px #4caf50;
}

.input_box span,
.input_box p {
    width: 100%;
    color: #e53935;
    font-size: 14px;
    text-align: left;
    padding-left: 5px;
}

.confirm_pwddiv {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
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

.captcha_box {
    width: 100%;
    display: flex;
    gap: 12px;
    align-items: center;
}

.captcha_box input {
    flex-grow: 1;
    height: 40px;
    border: 1px solid #ccc;
    border-radius: 10px;
    font-size: 16px;
    padding-left: 10px;
    transition: border-color 0.3s;
}

.captcha_box input:focus {
    outline: none;
    border-color: #4caf50;
    box-shadow: 0 0 5px #4caf50;
}

.captcha_box img {
    height: 40px;
    border-radius: 10px;
    cursor: pointer;
    user-select: none;
    border: 1px solid #ccc;
}

.captcha_error {
    color: #e53935;
    font-size: 14px;
    width: 100%;
    text-align: left;
    padding-left: 5px;
    margin-top: -15px;
    margin-bottom: 5px;
}

.sign_up {
    width: 100%;
    height: 45px;
    background-color: #4caf50;
    color: white;
    font-weight: 600;
    font-size: 18px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 15px;
    transition: background-color 0.3s;
}

.sign_up:hover {
    background-color: #43a047;
}

/* 響應式調整 */
@media (max-width: 480px) {
    .input_box {
        width: 90vw;
        padding: 32px 24px;
    }

    .signup-word {
        font-size: 28px;
        margin-bottom: 30px;
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
