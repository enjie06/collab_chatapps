import './bootstrap';

window.Echo.join(`conversation.${conversationId}`)
    .listen('.message.sent', (e) => {

        // Buat bubble chat baru
        const chat = document.getElementById('chat-body');

        const bubble = document.createElement('div');
        bubble.className = `mb-2 flex ${e.user.id == userId ? 'justify-end' : 'justify-start'}`;
        bubble.innerHTML = `
            <div class="max-w-[70%] px-3 py-2 rounded-xl break-words ${e.user.id == userId ? 'bg-rose-600 text-white rounded-br-none' : 'bg-gray-200 text-gray-800 rounded-bl-none'}">
                ${e.content}
                <div class="text-[10px] opacity-70 mt-1 text-right">${e.created_at}</div>
            </div>
        `;

        chat.appendChild(bubble);
        chat.scrollTop = chat.scrollHeight;
    });

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();