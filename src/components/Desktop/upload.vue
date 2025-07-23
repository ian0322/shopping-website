<script setup>
import { ref } from 'vue'
import { useFilterStore } from '@/stores/filterStore2'
const filterStore = useFilterStore()
const files = ref([])
const showTagSelectorIndex = ref(null)
const message = ref("")
const {
    apiHost,
} = filterStore;
const tagOptions = [
    "男裝", "女裝", "童裝", "短袖上衣", "長袖上衣", "短褲", "長褲", "裙子", "外套", "洋裝", "優惠商品", "全新商品"
]

const handleFileChange = (event) => {
    const selectedFiles = Array.from(event.target.files)
    files.value = []

    selectedFiles.forEach((file) => {
        const reader = new FileReader()
        reader.onload = (e) => {
            files.value.push({
                file,
                previewUrl: e.target.result,
                tags: [],
                name: '',
                price: '',
                size: ''  // 新增尺寸屬性
            })
        }
        reader.readAsDataURL(file)
    })

    event.target.value = null
}


const handleMouseEnter = (index) => {
    showTagSelectorIndex.value = index
}
const handleMouseLeave = () => {
    showTagSelectorIndex.value = null
}

const upload = async () => {
    if (files.value.length === 0) {
        message.value = "請選擇至少一張圖片"
        return
    }
    const formData = new FormData()
    files.value.forEach(file => {
        formData.append('files[]', file.file)
        formData.append('tags[]', JSON.stringify(file.tags))
        formData.append('names[]', file.name)
        formData.append('price[]', file.price)
        formData.append('size[]', file.size)  // 新增尺寸欄位
    })

    try {
        const response = await fetch(`/upload.php`, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })

        const text = await response.text()

        if (!response.ok) {
            // 這裡是 PHP 回傳了錯誤的 HTTP 狀態碼，例如 500
            message.value = `上傳失敗：${text}`
        } else {
            message.value = `上傳成功：${text}`
        }

    } catch (err) {
        console.error(err)
        message.value = "上傳失敗：網路或伺服器錯誤"
    }
}

</script>
<template>
    <div style="font-size:32px;margin-top: 16px;">上傳圖片加標籤</div>

    <input class="file" type="file" accept="image/*" multiple @change="handleFileChange" />

    <p v-if="files.length.length > 0">圖片預覽：</p>

    <div class="preview_img" v-if="files.length > 0">
        <div v-for="(fileObj, index) in files" :key="index" class="preview-wrapper"
            @mouseenter="handleMouseEnter(index)" @mouseleave="handleMouseLeave" style="position: relative;">
            <img :src="fileObj.previewUrl" alt="圖片預覽" class="preview" />

            <transition name="fade">
                <div v-show="showTagSelectorIndex === index" class="tag-selector">
                    <div style="margin-bottom: 8px;">
                        <label>商品名稱：
                            <input type="text" v-model="files[index].name" placeholder="name" />
                        </label>
                    </div>

                    <div style="margin-bottom: 8px;">
                        <label>商品價格：
                            <input type="number" v-model="files[index].price" placeholder="$" />
                        </label>
                    </div>

                    <div>標籤選擇：</div>
                    <label v-for="(tag, tagIndex) in tagOptions" :key="tagIndex">
                        <input type="checkbox" :value="tag" v-model="files[index].tags" />
                        {{ tag }}
                    </label>
                    <div style="margin-bottom: 8px;">
                        <label>商品尺寸與庫存（格式例如：S:6,XL:10）：
                            <input type="text" v-model="files[index].size" placeholder="S:6,XL:10" />
                        </label>
                    </div>
                </div>
            </transition>
        </div>
    </div>

    <p v-if="message">{{ message }}</p>
    <button class="upload" @click="upload">上傳</button>
</template>
<style scoped>
* {
    font-family: 'cwtexyen', sans-serif;
    font-size: 20px;
    margin-left: 10px;
}

body {
    background-color: slategray;
}

.preview_img {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.preview-wrapper {
    position: relative;
    width: 300px;
    height: 300px;
}

.preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 0 6px rgba(0, 0, 0, 0.2);
}

.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
    transform: translateY(0);
}

/* 讓tag選單不要擠在圖片上 */
.tag-selector {
    position: absolute;
    top: 0;
    left: 0;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #ccc;
    padding: 8px;
    width: auto;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 10;
}

.tag-selector label {
    display: block;
    margin-bottom: 4px;
}

button {
    cursor: pointer;
    background-color: rgb(218, 218, 218);
    border: 2px solid black;
    border-radius: 10px;
    margin-top: 20px;
    padding: 8px 16px;
    font-size: 20px;
    margin-left: 20px;
}

.file {
    cursor: pointer;
    background-color: rgb(218, 218, 218);
    border: 2px solid black;
    border-radius: 10px;
    margin-top: 20px;
    padding: 8px 16px;
    font-size: 14px;
}

.upload {
    width: auto;
}

input {
    padding-left: 5px;
}
</style>