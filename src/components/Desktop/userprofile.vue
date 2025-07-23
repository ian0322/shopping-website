<script setup>
import { reactive } from 'vue';
import { storeToRefs } from 'pinia';
import { useFilterStore } from '@/stores/filterStore2'

const filterStore = useFilterStore();
const {
  userId, username, email, phone, lastname, firstname, address
} = storeToRefs(filterStore);
const { apiHost } = filterStore;

const form = reactive({
  lastname: localStorage.getItem("lastname"),
  firstname: localStorage.getItem("firstname"),
  address: localStorage.getItem("address"),
});

const editing = reactive({
  lastname: false,
  firstname: false,
  address: false,
});

const changeUser = async (field) => {
  // 進入編輯模式：清空 input
  if (!editing[field]) {
    form[field] = ''; // 清空 input
    editing[field] = true;
    return;
  }

  // 處理送出邏輯
  const originalValue = {
    lastname: lastname.value,
    firstname: firstname.value,
    address: address.value,
  }[field];

  const formValue = form[field].trim() || originalValue; // 如果空值，就使用舊值

  const formData = new URLSearchParams();
  formData.append(field, formValue);

  try {
    const response = await fetch(`/changeprofile.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString(),
      mode: 'cors',
      credentials: 'include',
    });

    const data = await response.json();

    if (data.success) {
      alert('修改成功');

      // 更新對應欄位
      if (field === 'lastname') lastname.value = formValue;
      if (field === 'firstname') firstname.value = formValue;
      if (field === 'address') address.value = formValue;

      localStorage.setItem(field, formValue);
      form[field] = '';
      editing[field] = false;
    } else {
      alert('修改失敗');
    }
  } catch (error) {
    console.error('錯誤:', error);
    alert('發生錯誤，請稍後再試');
  }
};

</script>

<template>
  <div class="profile-container">
    <transition name="fade-slide" appear>

      <div v-if="userId" class="userfile">
        <div class="username">{{ username }}您好</div>

        <div class="lastname">
          姓氏：
          <template v-if="editing.lastname">
            <input type="text" v-model="form.lastname" required>
          </template>
          <template v-else>
            {{ lastname }}
          </template>
          <button @click="changeUser('lastname')">
            {{ editing.lastname ? '送出' : '設定' }}
          </button>
        </div>

        <div class="firstname">
          名字：
          <template v-if="editing.firstname">
            <input type="text" v-model="form.firstname" required>
          </template>
          <template v-else>
            {{ firstname }}
          </template>
          <button @click="changeUser('firstname')">
            {{ editing.firstname ? '送出' : '設定' }}
          </button>
        </div>

        <div class="email">
          Email：{{ email }}
        </div>

        <div class="phone">
          電話號碼：{{ phone }}
        </div>

        <div class="address">
          居住地址：
          <template v-if="editing.address">
            <input type="text" v-model="form.address" required>
          </template>
          <template v-else>
            {{ address }}
          </template>
          <button @click="changeUser('address')">
            {{ editing.address ? '送出' : '設定' }}
          </button>
        </div>
      </div>

      <div v-else>
        請登入使用此功能
      </div>
    </transition>

  </div>
</template>

<style scoped>
.profile-container {
  max-width: 600px;
  margin: 40px auto;
  padding: 20px;
  font-family: 'Segoe UI', sans-serif;
  background-color: #f9f9f9;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.userfile {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.username {
  font-size: 24px;
  font-weight: bold;
  color: #333;
  margin-bottom: 10px;
}

.userfile div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: none;
  padding: 10px 0;
  border-bottom: 1px solid #ddd;
}

.userfile input[type="text"] {
  flex: 1;
  margin-left: 10px;
  padding: 6px 10px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 6px;
  background-color: #fff;
}

button {
  margin-left: 10px;
  padding: 6px 14px;
  background-color: #4CAF50;
  color: white;
  font-size: 14px;
  font-weight: bold;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

button:hover {
  background-color: #45a049;
}

.email,
.phone {
  color: #555;
  font-size: 16px;
}

@media (max-width: 500px) {
  .profile-container {
    padding: 15px;
  }

  .userfile input[type="text"] {
    font-size: 14px;
  }

  button {
    font-size: 13px;
    padding: 5px 10px;
  }
}

.fade-slide-enter-active,
.fade-slide-leave-active,
.fade-slide-appear-active {
  transition: all 0.4s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to,
.fade-slide-appear-from {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-slide-enter-to,
.fade-slide-leave-from,
.fade-slide-appear-to {
  opacity: 1;
  transform: translateY(0);
}
</style>