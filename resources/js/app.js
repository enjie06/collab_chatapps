import "./bootstrap";

import Alpine from "alpinejs";
window.Alpine = Alpine;
Alpine.start();

// === Pesan Realtime ===
window.Echo.join(`conversation.${conversationId}`)
    .listen(".message.sent", (e) => {
        const chat = document.getElementById("chat-body");

        const bubble = document.createElement("div");
        bubble.className = `mb-2 flex ${
            e.user.id == userId ? "justify-end" : "justify-start"
        }`;

        bubble.innerHTML = `
            <div class="max-w-[70%] px-3 py-2 rounded-xl break-words 
                ${e.user.id == userId 
                    ? "bg-rose-600 text-white rounded-br-none"
                    : "bg-gray-200 text-gray-800 rounded-bl-none"}">
                ${e.content}
                <div class="text-[10px] opacity-70 mt-1 text-right">${e.created_at}</div>
            </div>
        `;

        chat.appendChild(bubble);
        chat.scrollTop = chat.scrollHeight;
    });


// === Presence Channel untuk Typing ===
const typingChannel = window.Echo.join(`presence-conversation.${conversationId}`);

// Group typing list
let typingUsers = new Set();

// Receive whisper
typingChannel.listenForWhisper("typing", (e) => {
    const el = document.getElementById("typingIndicator");

    // PRIVATE
    if (!isGroup) {
        if (e.user_id !== userId) {
            el.innerText = `${e.name} sedang mengetik...`;
        } else {
            el.innerText = "";
        }
        return;
    }

    // GROUP
    if (e.user_id && e.user_id !== userId) {
        typingUsers.add(e.name);
    } else {
        typingUsers.delete(e.name);
    }

    if (typingUsers.size === 0) {
        el.innerText = "";
    } else {
        el.innerText = [...typingUsers].join(", ") + " sedang mengetik...";
    }
});


// === SEND Typing Whisper ===
let typingTimeout;

document.getElementById("chatInput")?.addEventListener("input", () => {

    typingChannel.whisper("typing", {
        user_id: userId,
        name: userName,
        is_group: isGroup,
    });

    clearTimeout(typingTimeout);

    typingTimeout = setTimeout(() => {
        typingChannel.whisper("typing", {
            user_id: null,
            name: userName,
            is_group: isGroup,
        });
    }, 900);
});
