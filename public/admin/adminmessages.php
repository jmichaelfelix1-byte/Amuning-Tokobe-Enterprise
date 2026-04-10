<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../signin.php');
    exit();
}

require_once '../includes/config.php';

$admin_id = $_SESSION['user_id'];
$page_title = 'Messages - Admin - Amuning Tokobe Enterprise';
$additional_css = ['../assets/css/common.css'];

// Get conversation ID from URL if provided
$selected_conversation_id = intval($_GET['conv_id'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="../../images/amuninglogo.ico">
    <link rel="shortcut icon" href="../../images/amuninglogo.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/adminmessages.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>

        <main class="admin-main">
            <header class="main-header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Messages</h1>
            </header>

            <div class="main-content">
                <div class="messages-container">
                    <div class="conversations-list">
                        <div class="conversations-header">
                            <h2>Conversations</h2>
                            <input type="text" class="search-box" id="searchConversations" placeholder="Search conversations...">
                        </div>
                        <div id="conversationsList"></div>
                    </div>

                    <div class="chat-area" id="chatArea">
                        <div class="chat-header">
                            <div class="chat-info">
                                <h3 id="chatOrderInfo">Select a conversation</h3>
                                <p id="chatOrderType"></p>
                            </div>
                            <div class="customer-info">
                                <p><strong id="chatCustomerName">-</strong></p>
                                <p id="chatCustomerEmail">-</p>
                            </div>
                        </div>

                        <div class="messages-window" id="messagesWindow">
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <p>Select a conversation to view messages</p>
                            </div>
                        </div>

                        <div class="input-area" id="inputArea" style="display: none;">
                            <input type="text" id="messageInput" placeholder="Type your reply...">
                            <button id="sendBtn" title="Send reply"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const conversationsList = document.getElementById('conversationsList');
        const messagesWindow = document.getElementById('messagesWindow');
        const inputArea = document.getElementById('inputArea');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const chatArea = document.getElementById('chatArea');
        const chatOrderInfo = document.getElementById('chatOrderInfo');
        const chatOrderType = document.getElementById('chatOrderType');
        const chatCustomerName = document.getElementById('chatCustomerName');
        const chatCustomerEmail = document.getElementById('chatCustomerEmail');
        const searchInput = document.getElementById('searchConversations');

        let currentConversationId = <?php echo $selected_conversation_id; ?>;
        let conversations = [];

        // Load conversations on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadConversations();
        });

        function loadConversations() {
            fetch('api/messages.php?action=get_all_conversations')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        conversations = data.conversations;
                        displayConversations(conversations);
                        
                        // If a conversation was selected, load it
                        if (currentConversationId > 0) {
                            selectConversation(currentConversationId);
                        }
                    } else {
                        conversationsList.innerHTML = '<div class="loading"><p>' + data.message + '</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    conversationsList.innerHTML = '<div class="loading"><p>Error loading conversations</p></div>';
                });
        }

        function displayConversations(convs) {
            conversationsList.innerHTML = '';
            
            if (convs.length === 0) {
                conversationsList.innerHTML = '<div class="loading"><p>No conversations</p></div>';
                return;
            }

            convs.forEach(conversation => {
                const item = document.createElement('div');
                item.className = 'conversation-item' + (conversation.id === currentConversationId ? ' active' : '');
                item.setAttribute('data-id', conversation.id);
                
                const timeStr = conversation.last_message_time ? formatTime(new Date(conversation.last_message_time)) : 'No messages';
                const unreadHtml = conversation.unread_count > 0 ? `<span class="unread-badge">${conversation.unread_count}</span>` : '';
                const displayText = conversation.decline_reason ? conversation.decline_reason : `${conversation.order_type === 'printing_order' ? 'Print Order #' : 'Photo Booking #'}${conversation.order_id}`;
                
                item.innerHTML = `
                    <div class="conversation-item-content">
                        <div class="user-info">
                            <span class="user-name">${conversation.user_name}</span>
                            ${unreadHtml}
                        </div>
                        <div class="order-info">${displayText}</div>
                        <div class="last-message">${conversation.last_message ? conversation.last_message.substring(0, 50) + (conversation.last_message.length > 50 ? '...' : '') : 'No messages'}</div>
                        <div class="time">${timeStr}</div>
                    </div>
                `;
                
                item.addEventListener('click', () => selectConversation(conversation.id));
                conversationsList.appendChild(item);
            });
        }

        function selectConversation(conversationId) {
            currentConversationId = conversationId;
            
            // Update active state
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`.conversation-item[data-id="${conversationId}"]`)?.classList.add('active');
            
            // Load conversation details
            loadConversation(conversationId);
        }

        function loadConversation(conversationId) {
            fetch(`api/messages.php?action=get_conversation&conversation_id=${conversationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const conversation = data.conversation;
                        const messages = data.messages;
                        
                        // Update header
                        chatOrderInfo.textContent = `Order #${conversation.order_id}`;
                        chatOrderType.textContent = conversation.order_type === 'printing_order' ? 'Print Order' : 'Photo Booking';
                        chatCustomerName.textContent = conversation.user_name;
                        chatCustomerEmail.textContent = conversation.user_email;
                        
                        displayMessages(messages);
                        inputArea.style.display = 'flex';
                        messageInput.focus();
                        
                        // Scroll to bottom
                        setTimeout(() => {
                            messagesWindow.scrollTop = messagesWindow.scrollHeight;
                        }, 100);
                    } else {
                        messagesWindow.innerHTML = '<div class="loading"><p>' + data.message + '</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    messagesWindow.innerHTML = '<div class="loading"><p>Error loading conversation</p></div>';
                });
        }

        function displayMessages(messages) {
            messagesWindow.innerHTML = '';
            
            if (messages.length === 0) {
                messagesWindow.innerHTML = '<div class="loading"><p>No messages in this conversation</p></div>';
                return;
            }

            messages.forEach(message => {
                const isAdminMessage = message.sender_type === 'admin';
                const messageGroup = document.createElement('div');
                messageGroup.className = 'message-group' + (isAdminMessage ? ' admin-message' : ' user-message');
                
                const messageBubble = document.createElement('div');
                messageBubble.className = 'message-bubble';
                messageBubble.textContent = message.message_text;
                
                const messageTime = document.createElement('div');
                messageTime.className = 'message-time';
                messageTime.textContent = formatTime(new Date(message.created_at));
                
                messageGroup.appendChild(messageBubble);
                messageGroup.appendChild(messageTime);
                messagesWindow.appendChild(messageGroup);
            });

            // Scroll to bottom
            messagesWindow.scrollTop = messagesWindow.scrollHeight;
        }

        // Send message
        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        function sendMessage() {
            const messageText = messageInput.value.trim();
            
            if (!messageText || !currentConversationId) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('conversation_id', currentConversationId);
            formData.append('message_text', messageText);

            fetch('api/messages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadConversation(currentConversationId);
                    loadConversations();
                } else {
                    alert('Error sending message: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending message');
            });
        }

        // Search conversations
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filtered = conversations.filter(conv => {
                const orderNum = conv.order_id.toString();
                const userName = (conv.user_name || '').toLowerCase();
                const lastMsg = (conv.last_message || '').toLowerCase();
                return orderNum.includes(searchTerm) || userName.includes(searchTerm) || lastMsg.includes(searchTerm);
            });
            displayConversations(filtered);
        });

        function formatTime(date) {
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const yesterdayOnly = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate());

            if (dateOnly.getTime() === todayOnly.getTime()) {
                return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            } else if (dateOnly.getTime() === yesterdayOnly.getTime()) {
                return 'Yesterday';
            } else {
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }
        }

        // Refresh conversations every 1 second for real-time updates
        setInterval(() => {
            loadConversations();
        }, 1000);
    </script>
</body>
</html>
