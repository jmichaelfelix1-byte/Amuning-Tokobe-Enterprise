// Chatbot Widget JavaScript

class Chatbot {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.init();
    }

    init() {
        // Create chatbot HTML
        this.createChatbotHTML();
        this.attachEventListeners();
        this.loadPredefinedQuestions();
    }

    createChatbotHTML() {
        const container = document.createElement('div');
        container.className = 'chatbot-container';
        container.id = 'chatbot-container';
        container.innerHTML = `
            <button class="chatbot-toggle" id="chatbot-toggle" title="Open Chat">
                <i class="fas fa-comments"></i>
            </button>
        `;
        document.body.appendChild(container);
    }

    attachEventListeners() {
        const toggleBtn = document.getElementById('chatbot-toggle');
        toggleBtn.addEventListener('click', () => this.toggleChatbot());
    }

    toggleChatbot() {
        const container = document.getElementById('chatbot-container');
        
        if (this.isOpen) {
            this.closeChatbot();
        } else {
            this.openChatbot();
        }
    }

    openChatbot() {
        const container = document.getElementById('chatbot-container');
        container.innerHTML = `
            <div class="chatbot-widget">
                <div class="chatbot-header">
                    <h3>Amuning Support</h3>
                    <button class="chatbot-close" id="chatbot-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chatbot-messages" id="chatbot-messages">
                    <div class="message bot">
                        <div class="message-content">
                            👋 Hello! Welcome to Amuning. How can I help you today?
                        </div>
                    </div>
                </div>
                <div class="predefined-questions" id="predefined-questions"></div>
                <div class="chatbot-input-area">
                    <input type="text" class="chatbot-input" id="chatbot-input" 
                           placeholder="Type your question..." autocomplete="off">
                    <button class="chatbot-send" id="chatbot-send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;

        this.isOpen = true;
        this.messages = [{ type: 'bot', content: '👋 Hello! Welcome to Amuning. How can I help you today?' }];

        // Attach event listeners to new elements
        document.getElementById('chatbot-close').addEventListener('click', () => this.closeChatbot());
        document.getElementById('chatbot-send').addEventListener('click', () => this.sendMessage());
        document.getElementById('chatbot-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });

        // Load predefined questions
        this.loadPredefinedQuestions();

        // Focus input
        setTimeout(() => document.getElementById('chatbot-input').focus(), 100);
    }

    closeChatbot() {
        const container = document.getElementById('chatbot-container');
        container.innerHTML = `
            <button class="chatbot-toggle" id="chatbot-toggle" title="Open Chat">
                <i class="fas fa-comments"></i>
            </button>
        `;
        this.isOpen = false;
        document.getElementById('chatbot-toggle').addEventListener('click', () => this.toggleChatbot());
    }

    loadPredefinedQuestions() {
        fetch('includes/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_predefined'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && this.isOpen) {
                const container = document.getElementById('predefined-questions');
                if (container) {
                    container.innerHTML = '';
                    data.questions.forEach(q => {
                        const btn = document.createElement('button');
                        btn.className = 'predefined-btn';
                        btn.textContent = q.text;
                        btn.addEventListener('click', () => this.handlePredefinedQuestion(q.id));
                        container.appendChild(btn);
                    });
                }
            }
        })
        .catch(error => console.error('Error loading questions:', error));
    }

    handlePredefinedQuestion(questionId) {
        const question = this.getPredefinedQuestionText(questionId);
        document.getElementById('chatbot-input').value = '';
        this.addMessage('user', question);
        setTimeout(() => {
            const input = document.getElementById('chatbot-input');
            if (input) input.focus();
        }, 100);
        this.sendMessageToBot(questionId);
    }

    getPredefinedQuestionText(id) {
        const questions = {
            'services': 'What services do you offer?',
            'booking': 'How do I book a service?',
            'price': 'What are your prices?',
            'hours': 'What are your operating hours?',
            'contact': 'How can I contact you?',
            'payment': 'What payment methods do you accept?',
            'cancel': 'Can I cancel my booking?',
            'photos': 'When will I get my photos?',
            'delivery': 'Do you offer delivery?',
            'group': 'Do you offer group discounts?',
        };
        return questions[id] || '';
    }

    sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();

        if (message === '') return;

        input.value = '';
        this.addMessage('user', message);
        setTimeout(() => {
            const input = document.getElementById('chatbot-input');
            if (input) input.focus();
        }, 100);
        this.sendMessageToBot(message);
    }

    hideAndFocusInput() {
        setTimeout(() => {
            const input = document.getElementById('chatbot-input');
            if (input) input.focus();
        }, 100);
    }

    sendMessageToBot(message) {
        // Show loading state
        this.addMessage('bot', '<div class="chat-loading"><span></span><span></span><span></span></div>');

        fetch('includes/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=send_message&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            // Remove loading message
            const messages = document.getElementById('chatbot-messages');
            const lastMessage = messages.lastElementChild;
            if (lastMessage && lastMessage.querySelector('.chat-loading')) {
                lastMessage.remove();
            }

            if (data.success) {
                this.addMessage('bot', data.response);
            } else {
                this.addMessage('bot', 'Sorry, I encountered an error. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.addMessage('bot', 'Sorry, I encountered an error. Please try again.');
        });
    }

    addMessage(type, content) {
        const messagesContainer = document.getElementById('chatbot-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.innerHTML = content;

        messageDiv.appendChild(contentDiv);
        messagesContainer.appendChild(messageDiv);

        // Scroll to bottom
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 10);

        this.messages.push({ type, content });
    }
}

// Initialize chatbot when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new Chatbot();
});
