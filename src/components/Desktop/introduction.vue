
<template>
    <div class="main">
        <div class="avatar-container">
            <div class="avatar"></div>
            <p class="name">李宸懿</p>
        </div>
        <div class="project-wrapper">
            <div class="project">
                <router-link to="/login" class="button">作品一</router-link>
                <div v-if="false" class="button">作品二<br>(尚未製作)</div>
                <div v-if="false" class="button">作品三<br>(尚未製作)</div>
                <div v-if="false" class="button">作品四<br>(尚未製作)</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.main {
    padding: 40px;
    background: #fafafa;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 60px;
}

/* 頭像區 */
.avatar-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 200px;
    user-select: none;
}

.avatar {
    width: 200px;
    aspect-ratio: 1/1;
    border-radius: 50%;
    background-image: url('img/selfpic.jpg');
    background-size: cover;
    background-position: center;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.15);
    transition: box-shadow 0.3s ease;
}

.avatar:hover {
    box-shadow: 0 0 30px rgba(184, 136, 82, 0.6);
}

.name {
    margin-top: 15px;
    font-size: 28px;
    font-weight: 700;
    color: #4a4a4a;
    letter-spacing: 1.2px;
}

/* 按鈕區 */
.project-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
}

.project {
    display: flex;
    gap: 24px;
    width: auto;
    max-width: 1200px;
}


.button {
    display: flex;
    justify-content: center;
    align-items: center;
    flex: 1;
    width: 300px;
    height: 300px;
    /* 可以調整高度 */
    font-size: 40px;
    font-weight: 700;
    color: #b88852;
    background-color: white;
    border: 3px solid #b88852;
    border-radius: 16px;
    text-align: center;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    user-select: none;
    transition: background-color 0.4s ease, color 0.4s ease, box-shadow 0.4s ease;
    text-decoration: none;
}

.button:hover {
    background-color: #b88852;
    color: white;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* 作品一專屬背景圖 */
.button:nth-child(1)::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    background: url('img/work1.png') center center / cover no-repeat;
    opacity: 0;
    transition: opacity 0.4s ease;
    z-index: 0;
}

.button:nth-child(1):hover::after {
    opacity: 0.3;
}

/* 調整手機板 */
@media (max-width: 900px) {
    .project {
        flex-direction: column;
        align-items: center;
    }

    .button {
        width: 100%;
        line-height: 100px;
        font-size: 36px;
    }

    .avatar-container {
        width: 150px;
    }

    .avatar {
        width: 150px;
    }

    .name {
        font-size: 24px;
    }
}
</style>
