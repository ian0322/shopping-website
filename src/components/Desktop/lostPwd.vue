<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()

const newPassword = ref('')
const confirmPassword = ref('')
const token = ref('')  // 這個保持原樣，是反應式的變量
const isActive = ref(true)
const passwordError = ref('')

onMounted(() => {
  const hash = window.location.search; // "#/lostpwd?token=..."
  console.log("URL hash:", hash);

  // 只取 hash 中的 token 部分
  const queryString = hash.slice(hash.indexOf('?') + 1);  // 只取 "token=..."
  const urlParams = new URLSearchParams(queryString);
  token.value = urlParams.get('token');  // 這裡修改反應式變量 token.value

  console.log("token", token.value);

  // 使用 nextTick 延遲執行
  nextTick(() => {
    if (!token.value) {  // 檢查是否有有效的 token
      alert('無效的 token');
      router.push({ name: 'login' });
    }
  });
});

const resetPassword = async () => {
  if (newPassword.value !== confirmPassword.value) {
    alert('兩次輸入的密碼不一致！')
    return
  }

  if (!token.value) {
    alert('無效的 token')
    return
  }

  console.log('送出的資料:', {
    token: token.value,
    newPassword: newPassword.value,
    confirmPWD: confirmPassword.value
  });

  try {
    const res = await axios.post(`/forget_pwd.php`, {
      token: token.value,
      newPassword: newPassword.value,
      confirmPWD: confirmPassword.value
    }, {
      headers: {
        'Content-Type': 'application/json'  // 設定 Content-Type 為 application/json
      }
    });
    console.log("res.data.success", res.data.success)
    if (res.data && res.data.success) {
      alert(res.data.message)
      router.push({ name: 'login' })
    } else {
      alert('錯誤: ' + (res.data.message || '未知錯誤'))
    }
  } catch (err) {
    console.error('API 錯誤', err)
    alert('系統錯誤，請稍後再試')
  }
}
const validatePassword = () => {
  const pwd = newPassword.value;
  if (pwd.length < 8) {
    passwordError.value = '密碼至少需要 8 個字元'
  } else if (!/[a-zA-Z]/.test(pwd)) {
    passwordError.value = '密碼必須包含至少一個英文字母'
  } else {
    passwordError.value = ''
  }
}
</script>

<template>
  <div class="container">
    <div class="top">
      <router-link to="/login" class="logo-link">
        <img src="/img/home_img.png" alt="首頁" />
      </router-link>
      <div class="title">忘記密碼</div>
    </div>
    <div class="middle">
      <form @submit.prevent="resetPassword" class="input_box">
        <div class="signup-word">重設密碼</div>
        <input :type="isActive ? 'password' : 'text'" class="password" v-model="newPassword" placeholder="New password"
          required autocomplete="new-password" @input="validatePassword" />
        <span v-if="passwordError">{{ passwordError }}</span>
        <div class="confirm_pwddiv">
          <input :type="isActive ? 'password' : 'text'" class="confirm_pwd" v-model="confirmPassword"
            placeholder="Confirm new password" required autocomplete="new-password" />
          <i id="checkEye" :class="['fas', isActive ? 'fa-eye' : 'fa-eye-slash']" @click="isActive = !isActive"
            aria-label="切換密碼顯示" role="button" tabindex="0"></i>
        </div>
        <button type="submit" class="sign_up">確認</button>
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
  border-color: #6b7280;
  box-shadow: 0 0 5px #6b7280;
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

.sign_up {
  width: 100%;
  height: 45px;
  background-color: #6b7280;
  /* 酒紅 */
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
  background-color: #4b5563;
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