// document.addEventListener('DOMContentLoaded', function () {
//     const chatMessages = document.getElementById('chat-messages');
//     const receiverId = chatMessages?.dataset.receiverId;

//     if (!chatMessages || !receiverId) return;

//     if (window.location.protocol !== 'file:') {
//         setInterval(() => {
//             fetch(`/chat/${receiverId}`, {
//                 method: 'GET',
//                 headers: {
//                     'Accept': 'text/html',
//                     'X-Requested-With': 'XMLHttpRequest'
//                 }
//             })
//             .then(response => {
//                 if (!response.ok) throw new Error('Erreur réseau : ' + response.status);
//                 return response.text();
//             })
//             .then(html => {
//                 const parser = new DOMParser();
//                 const doc = parser.parseFromString(html, 'text/html');
//                 const newMessages = doc.getElementById('chat-messages');
//                 if (newMessages) {
//                     chatMessages.innerHTML = newMessages.innerHTML;
//                 }
//             })
//             .catch(error => {
//                 console.error('Erreur lors du polling :', error);
//                 chatMessages.innerHTML += `
//                     <div class="alert alert-danger mt-3">
//                         Erreur de chargement des messages.
//                     </div>`;
//             });
//         }, 5000);
//     } else {
//         console.log("Mode local détecté - polling désactivé.");
//         chatMessages.innerHTML += `
//             <div class="alert alert-info mt-3">
//                 <strong>Note :</strong> Polling désactivé en local.
//             </div>`;
//     }
// });
// import $ from 'jquery';
// $(document).ready(function () {
//     // Send message via AJAX
//     $('#send-message').click(function (e) {
//         e.preventDefault();

//         var content = $('#message-content').val();
//         if (!content.trim()) return;

//         $.ajax({
//             url: '/chat/send',
//             type: 'POST',
//             data: {
//                 content: content,
//                 receiver: $('#receiver-id').val()
//             },
//             success: function () {
//                 $('#message-content').val('');
//                 loadMessages(); // Refresh messages
//             },
//             error: function () {
//                 alert("Erreur lors de l'envoi du message");
//             }
//         });
//     });

//     // Start polling for new messages every 5 seconds
//     if (window.location.protocol !== 'file:') {
//         setInterval(loadMessages, 5000);
//     } else {
//         console.log("Mode local détecté – polling désactivé.");
//     }
// });

// // Function to reload messages
// function loadMessages() {
//     const receiverId = $('#receiver-id').val();

//     $.ajax({
//         url: '/chat/' + receiverId,
//         type: 'GET',
//         headers: {
//             'X-Requested-With': 'XMLHttpRequest'
//         },
//         success: function (data) {
//             const doc = new DOMParser().parseFromString(data, 'text/html');
//             const updated = doc.getElementById('chat-messages');
//             if (updated) {
//                 $('#chat-messages').html($(updated).html());

//                 // Scroll to bottom if new messages
//                 const container = document.getElementById('chat-messages');
//                 container.scrollTop = container.scrollHeight;
//             }
//         },
//         error: function () {
//             $('#chat-messages').append(
//                 '<div class="alert alert-danger mt-2">Erreur de chargement des messages.</div>'
//             );
//         }
//     });
// }

import $ from 'jquery';

$(document).ready(function () {
    const receiverId = $('#receiver-id').val();
    const senderId = $('#sender-id').val();

    $('#send-message').click(function (e) {
        e.preventDefault();

        const content = $('#message-content').val().trim();
        if (!content) return;
        $.ajax({
            url: '/chat/send',
            type: 'POST',
            data: {
                content: content,
                receiver: receiverId
            },
            success: function () {
                $('#message-content').val('');
            },
            error: function () {
                alert("Erreur lors de l'envoi du message");
            }
        });
    });
    const minId = Math.min(senderId, receiverId);
    const maxId = Math.max(senderId, receiverId);

    const topic = `http://localhost:8000/conversation/${minId}-${maxId}`;
    const url = new URL('http://localhost:3000/.well-known/mercure');
    url.searchParams.append('topic', topic);

    const eventSource = new EventSource(url, {
        withCredentials: true
    });
    eventSource.onopen = () => console.log('Mercure connection opened');
    eventSource.onerror = function(event) {
        console.error('Mercure connection error:', event);
    };
    
    eventSource.onmessage = function (event) {
        console.log('Message reçu', event.data);
        const data = JSON.parse(event.data);
        console.log('Données parsées:', data);

        const messageHtml = `
           <div class="mb-2">
                <strong>${data.sender.email || data.sender.id}</strong>
                <small class="text-muted">(${new Date(data.timestamp).toLocaleTimeString()})</small>:<br>
                <span>${data.content}</span>
            </div>
        `;

        $('#chat-messages').append(messageHtml);

        const container = document.getElementById('chat-messages');
        container.scrollTop = container.scrollHeight;
    };
});

