<!-- 🤖 CampusAI — SOET Intelligent Chatbot UI Component -->
<link rel="stylesheet" href="<?php echo APP_URL; ?>/chatbot/assets/css/chatbot.css">

<!-- Floating Trigger Button -->
<div id="soet-chatbot-trigger" title="🤖 CampusAI Assistant">
    <span class="campus-ai-icon">🤖</span>
    <span class="badge-ping"></span>
</div>

<!-- Chatbot Drawer Window -->
<div id="soet-chatbot-window">
    <!-- Header -->
    <div class="soet-cb-header">
        <div class="soet-cb-title">
            <div class="soet-cb-avatar">
                <span style="font-size: 22px;">🤖</span>
            </div>
            <div>
                <span class="d-block font-bold lh-1 text-white" style="font-size: 15px;">CampusAI</span>
                <span class="d-block text-white-50" style="font-size: 11px;"><i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i> NextGen AI Assistant</span>
            </div>
        </div>
        <div class="soet-cb-controls">
            <!-- Language Selector Pills -->
            <button class="soet-cb-lang-btn active" data-lang="en">EN</button>
            <button class="soet-cb-lang-btn" data-lang="hi">HI</button>
            <button class="soet-cb-lang-btn" data-lang="mr">MR</button>

            <!-- Dark / Light Theme Toggle -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-theme-btn" title="Toggle Dark/Light Mode">
                <i class="fa-solid fa-moon" id="themeIcon"></i>
            </button>

            <!-- Export Chat Transcript -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-export-btn" title="Download Conversation Transcript">
                <i class="fa-solid fa-arrow-down-to-bracket"></i>
            </button>

            <!-- Expand / Maximize Toggle -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-expand-btn" title="Expand / Minimize Window">
                <i class="fa-solid fa-expand" id="expandIcon"></i>
            </button>

            <!-- Clear Chat -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-clear-btn" title="Clear Chat History">
                <i class="fa-solid fa-trash-can"></i>
            </button>
            <!-- Close Chat -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-close-btn" title="Close CampusAI">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Message Thread Container -->
    <div class="soet-cb-messages" id="soet-cb-messages">
        <!-- Messages rendered via JavaScript -->
    </div>

    <!-- Live Autocomplete Popover / Suggestions -->
    <div class="soet-cb-suggestions" id="soet-cb-suggestions">
        <!-- Chips loaded dynamically -->
    </div>

    <!-- Input Bar -->
    <div class="soet-cb-input-bar">
        <button class="soet-cb-mic-btn" id="soet-cb-mic-btn" title="Voice Input (Speech-to-Text)">
            <i class="fa-solid fa-microphone"></i>
        </button>
        <input type="text" id="soet-cb-input" class="soet-cb-input" placeholder="Ask CampusAI anything..." autocomplete="off">
        <button class="soet-cb-send-btn" id="soet-cb-send-btn" title="Send Message">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<script src="<?php echo APP_URL; ?>/chatbot/assets/js/chatbot.js"></script>
