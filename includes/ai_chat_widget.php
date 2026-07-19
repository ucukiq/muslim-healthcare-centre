<!-- AI Chat Widget -->
<div id="ai-chat-widget">
    <!-- Chat Button -->
    <div id="chat-button" onclick="toggleChat()">
        <i class="fas fa-robot"></i>
        <span>AI Assistant</span>
    </div>
    
    <!-- Chat Window -->
    <div id="chat-window">
        <div id="chat-header">
            <div class="d-flex align-items-center">
                <i class="fas fa-robot me-2"></i>
                <span>Medical AI Assistant</span>
            </div>
            <button onclick="toggleChat()" class="btn-close btn-close-white"></button>
        </div>
        
        <div id="chat-messages">
            <div class="message ai-message">
                <div class="message-content">
                    <strong>Medical AI:</strong> Hello! I'm your intelligent healthcare assistant powered by advanced AI. I can help you with:
                    <br><br>
                    🏥 Booking appointments<br>
                    📋 Understanding medical services<br>
                    ⏰ Clinic information<br>
                    💊 General health guidance<br>
                    📞 Emergency contacts<br><br>
                    How may I assist you today?
                </div>
                <div class="message-time">Just now</div>
            </div>
        </div>
        
        <div id="chat-input">
            <div class="input-group">
                <input type="text" id="message-input" placeholder="Type your health question..." class="form-control">
                <button onclick="sendMessage()" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="quick-actions">
                <button onclick="quickReply('Book appointment')" class="btn btn-sm btn-outline-primary">📅 Book</button>
                <button onclick="quickReply('Services')" class="btn btn-sm btn-outline-primary">🏥 Services</button>
                <button onclick="quickReply('Emergency')" class="btn btn-sm btn-outline-danger">🚨 Emergency</button>
                <button onclick="quickReply('Medicine info')" class="btn btn-sm btn-outline-info">💊 Medicine</button>
            </div>
        </div>
    </div>
</div>

<style>
#ai-chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

#chat-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 15px 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    font-weight: 500;
}

#chat-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
}

#chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e0e0e0;
}

#chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 500;
}

#chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: #f8f9fa;
}

.message {
    margin-bottom: 15px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message-content {
    padding: 10px 15px;
    border-radius: 18px;
    max-width: 80%;
    word-wrap: break-word;
}

.ai-message .message-content {
    background: #e3f2fd;
    color: #1976d2;
    border-bottom-left-radius: 5px;
}

.user-message .message-content {
    background: #667eea;
    color: white;
    border-bottom-right-radius: 5px;
    margin-left: auto;
}

.message-time {
    font-size: 0.75rem;
    color: #666;
    margin-top: 5px;
    text-align: right;
}

.ai-message .message-time {
    text-align: left;
}

#chat-input {
    padding: 15px;
    border-top: 1px solid #e0e0e0;
    background: white;
}

.quick-actions {
    display: flex;
    gap: 5px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.quick-actions .btn {
    font-size: 0.8rem;
    padding: 5px 10px;
    border-radius: 15px;
}

.typing-indicator {
    display: none;
    padding: 10px 15px;
    background: #e3f2fd;
    border-radius: 18px;
    border-bottom-left-radius: 5px;
    max-width: 80px;
}

.typing-dots {
    display: flex;
    gap: 4px;
}

.typing-dots span {
    width: 8px;
    height: 8px;
    background: #1976d2;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-10px); }
}

@media (max-width: 768px) {
    #chat-window {
        width: 300px;
        height: 450px;
        right: -50px;
    }
    
    #chat-button span {
        display: none;
    }
    
    #chat-button {
        padding: 15px;
        border-radius: 50%;
    }
}
</style>

<script>
function toggleChat() {
    const chatWindow = document.getElementById('chat-window');
    chatWindow.style.display = chatWindow.style.display === 'flex' ? 'none' : 'flex';
    
    if (chatWindow.style.display === 'flex') {
        document.getElementById('message-input').focus();
    }
}

function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (message === '') return;
    
    // Add user message
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    showTypingIndicator();
    
    // Simulate AI response
    setTimeout(() => {
        hideTypingIndicator();
        const response = generateAIResponse(message);
        addMessage(response, 'ai');
    }, 1000 + Math.random() * 1000);
}

function quickReply(text) {
    document.getElementById('message-input').value = text;
    sendMessage();
}

function addMessage(message, sender) {
    const messagesContainer = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}-message`;
    
    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <strong>${sender === 'ai' ? 'AI Assistant' : 'You'}:</strong> ${message}
        </div>
        <div class="message-time">${time}</div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function showTypingIndicator() {
    const messagesContainer = document.getElementById('chat-messages');
    const typingDiv = document.createElement('div');
    typingDiv.id = 'typing-indicator';
    typingDiv.className = 'typing-indicator';
    typingDiv.innerHTML = `
        <div class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>
    `;
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function hideTypingIndicator() {
    const typingIndicator = document.getElementById('typing-indicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

function generateAIResponse(message) {
    const lowerMessage = message.toLowerCase();
    
    // Enhanced healthcare-specific responses
    if (lowerMessage.includes('appointment') || lowerMessage.includes('book')) {
        return "📅 I can help you book an appointment! Our intelligent booking system matches you with the right doctor based on your needs. You can:\n\n• Book online through the patient portal\n• Choose your preferred specialization\n• Select convenient time slots\n• Get instant confirmation\n\nOur clinic hours: Mon-Fri, 8:00 AM - 5:00 PM\n\nWould you like me to guide you to the booking page?";
    }
    
    if (lowerMessage.includes('service')) {
        return "🏥 We provide comprehensive healthcare services with cutting-edge technology:\n\n• 🫀 Cardiology - Heart and vascular care\n• 🧠 Neurology - Brain and nervous system\n• 🦴 Orthopedics - Bones and joints\n• 👶 Pediatrics - Child healthcare\n• 👁️ Ophthalmology - Eye care\n• 🦷 Dental Services\n• 🩸 Laboratory Services\n• 📷 Medical Imaging\n\nAll our specialists are board-certified with extensive experience. Which service would you like to know more about?";
    }
    
    if (lowerMessage.includes('emergency') || lowerMessage.includes('urgent')) {
        return "🚨 MEDICAL EMERGENCY INFORMATION:\n\nFor life-threatening emergencies:\n• Call 999 immediately\n• Go to nearest Emergency Room\n• Don't wait for appointments\n\nFor urgent but non-life-threatening issues:\n• Call our emergency line: 1-800-MEDIC\n• Visit our Urgent Care Unit\n\nRemember: I'm an AI assistant and cannot provide medical advice for emergencies. Please seek immediate medical help for serious conditions.";
    }
    
    if (lowerMessage.includes('medicine') || lowerMessage.includes('drug') || lowerMessage.includes('medication')) {
        return "💊 MEDICINE INFORMATION:\n\nI can provide general information about medications but cannot prescribe or give medical advice. For medication-related queries:\n\n• Consult our pharmacists during clinic hours\n• Bring your prescription to our pharmacy\n• Ask about drug interactions\n• Get information about side effects\n\n⚠️ Never take medication without proper medical supervision. Always consult with healthcare professionals.";
    }
    
    if (lowerMessage.includes('hour') || lowerMessage.includes('time')) {
        return "⏰ CLINIC HOURS:\n\n• Monday - Friday: 8:00 AM - 5:00 PM\n• Saturday & Sunday: CLOSED\n• Emergency: 24/7 through emergency services\n\n📅 Appointment scheduling:\n• Online booking available 24/7\n• Phone support during clinic hours\n• Same-day appointments (subject to availability)\n\nWould you like to book an appointment?";
    }
    
    if (lowerMessage.includes('doctor')) {
        return "👨‍⚕️ FIND THE RIGHT DOCTOR:\n\nOur AI-powered system matches you with the best specialist:\n\n• All doctors are board-certified\n• Average 10+ years experience\n• Specialized in their fields\n• Patient-rated excellence\n\nYou can view doctor profiles, specializations, and availability when booking. Our system ensures you get the right care from the right professional.\n\nReady to find your doctor?";
    }
    
    if (lowerMessage.includes('register') || lowerMessage.includes('signup')) {
        return "📝 SMART REGISTRATION:\n\nJoin our healthcare network in 3 simple steps:\n\n1. Click 'Register' on main page\n2. Fill in your health profile\n3. Verify your email\n\n✅ Benefits of registration:\n• Online appointment booking\n• Access to medical records\n• Prescription management\n• Health reminders\n• Telemedicine options\n\nRegistration takes less than 2 minutes. Ready to join?";
    }
    
    if (lowerMessage.includes('login') || lowerMessage.includes('sign')) {
        return "🔐 SECURE ACCESS:\n\nLogin to your patient portal using:\n• Your registered email\n• Your secure password\n\n🛡️ Security features:\n• Encrypted connection\n• Two-factor authentication available\n• Session timeout protection\n\n❓ Trouble logging in?\n• Use 'Forgot Password' link\n• Contact our support team\n• Reset via email verification\n\nNeed help accessing your account?";
    }
    
    if (lowerMessage.includes('payment') || lowerMessage.includes('insurance') || lowerMessage.includes('cost')) {
        return "💳 PAYMENT & INSURANCE:\n\nWe accept multiple payment options:\n• All major insurance providers\n• Credit/Debit cards\n• Digital payments\n• Cash\n\n📋 Insurance coverage:\n• Direct billing available\n• Pre-authorization assistance\n• Claims processing help\n\n💡 Cost transparency:\n• Clear pricing information\n• Payment plans available\n• Financial assistance programs\n\nNeed help with insurance verification?";
    }
    
    if (lowerMessage.includes('symptom') || lowerMessage.includes('pain') || lowerMessage.includes('feel')) {
        return "⚕️ SYMPTOM ASSESSMENT:\n\nWhile I can't diagnose medical conditions, I can guide you:\n\n📋 For symptom tracking:\n• Note when symptoms started\n• Record severity (1-10 scale)\n• Document triggers\n• Monitor changes\n\n🏥 When to see a doctor:\n• Symptoms persist > 3 days\n• Severe pain or discomfort\n• Unusual changes in health\n• Preventive checkups needed\n\n🚨 Red flags - Seek immediate care for:\n• Chest pain, breathing difficulty\n• Severe injuries\n• High fever\n• Loss of consciousness\n\nWould you like to book an appointment for evaluation?";
    }
    
    // Default intelligent response
    return "🤖 AI HEALTHCARE ASSISTANT:\n\nI'm here to help with your healthcare needs! I can assist with:\n\n• 📅 Booking appointments\n• 🏥 Service information\n• 💊 General medication info\n• ⏰ Clinic hours\n• 📞 Emergency guidance\n• 📝 Registration help\n\nI use advanced AI to provide accurate healthcare information, but remember:\n⚠️ I cannot provide medical diagnosis or replace professional medical advice.\n\nFor specific medical concerns, please consult with our healthcare professionals.\n\nHow else can I assist you today?";
    // Default responses
    const defaultResponses = [
        "Thank you for your question! Our healthcare team is here to help you. You can book appointments, learn about our services, or ask about our working hours. How else can I assist you?",
        "I'm here to help with your healthcare needs! We offer comprehensive medical services including cardiology, neurology, orthopedics, pediatrics, and ophthalmology. What would you like to know?",
        "Welcome to Muslim Healthcare Centre! I can help you with booking appointments, information about our services, or answer general healthcare questions. What can I help you with today?"
    ];
    
    return defaultResponses[Math.floor(Math.random() * defaultResponses.length)];
}

// Enter key to send message
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
});
</script>
