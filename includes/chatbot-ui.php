<!-- 
  CampusAI — SOET Intelligent Chatbot Frontend Widget (v3 Upgrade)
  
  Provides the floating interactive AI drawer interface for prospective students and visitors.
  Features:
  - 100% Preserved Features: Multilingual, Dark/Light mode, Chat clear & export, Fullscreen expand, Voice Speech-to-Text, Suggestion chips.
  - v3 Upgrades: In-drawer message search, Audio chime feedback toggle, Scroll-to-bottom FAB, Quick actions toolbar, Reply/Quote action, Character counter, Live connection monitor.
-->
<link rel="stylesheet" href="<?php echo APP_URL; ?>/chatbot/assets/css/chatbot.css">

<!-- Floating Trigger Button -->
<div id="soet-chatbot-trigger" title="🤖 CampusAI Assistant">
    <span class="campus-ai-icon">🤖</span>
    <span class="badge-ping"></span>
</div>

<!-- Chatbot Drawer Window -->
<div id="soet-chatbot-window">
    <!-- Offline Alert Banner -->
    <div id="soet-cb-offline-banner" class="soet-cb-offline-banner" style="display: none;">
        <i class="fa-solid fa-wifi-slash me-1"></i> You are offline. Reconnecting...
    </div>

    <!-- Header -->
    <div class="soet-cb-header">
        <div class="soet-cb-title">
            <div class="soet-cb-avatar">
                <span style="font-size: 22px;">🤖</span>
            </div>
            <div>
                <span class="d-block font-bold lh-1 text-white" style="font-size: 15px;">CampusAI</span>
                <span class="d-block text-white-50" style="font-size: 14px;"><i class="fa-solid fa-circle text-success me-1" style="font-size: 14px;"></i> NextGen Neural AI</span>
            </div>
        </div>
        <div class="soet-cb-controls">
            <!-- Language Selector Pills -->
            <button class="soet-cb-lang-btn active" data-lang="en" title="English">EN</button>
            <button class="soet-cb-lang-btn" data-lang="hi" title="Hindi">HI</button>
            <button class="soet-cb-lang-btn" data-lang="mr" title="Marathi">MR</button>

            <!-- Search Toggle -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-search-toggle-btn" title="Search in Conversation">
                <i class="fa-solid fa-magnifying-glass" id="searchToggleIcon"></i>
            </button>

            <!-- Sound Effects Toggle -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-sound-btn" title="Toggle Sound Chimes">
                <i class="fa-solid fa-volume-high" id="soundIcon"></i>
            </button>

            <!-- Dark / Light Theme Toggle -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-theme-btn" title="Toggle Dark/Light Mode">
                <i class="fa-solid fa-moon" id="themeIcon"></i>
            </button>

            <!-- Export Chat Transcript -->
            <button class="soet-cb-icon-btn ms-1" id="soet-cb-export-btn" title="Download Conversation Transcript (.txt)">
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

    <!-- In-Drawer Search Bar (Slide down when toggled) -->
    <div id="soet-cb-search-bar" class="soet-cb-search-bar" style="display: none;">
        <i class="fa-solid fa-magnifying-glass text-muted me-1.5" style="font-size: 13px;"></i>
        <input type="text" id="soet-cb-search-input" placeholder="Search in chat..." autocomplete="off">
        <span id="soet-cb-search-count" class="text-muted small me-2"></span>
        <button id="soet-cb-search-prev" class="soet-cb-search-nav" title="Previous match"><i class="fa-solid fa-chevron-up"></i></button>
        <button id="soet-cb-search-next" class="soet-cb-search-nav" title="Next match"><i class="fa-solid fa-chevron-down"></i></button>
        <button id="soet-cb-search-close" class="soet-cb-search-nav" title="Close search"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <!-- Message Thread Container -->
    <div class="soet-cb-messages" id="soet-cb-messages">
        <!-- Messages rendered via JavaScript -->
    </div>

    <!-- Floating Scroll-to-Bottom Button (FAB) -->
    <button id="soet-cb-scroll-fab" class="soet-cb-scroll-fab" title="Scroll to bottom" style="display: none;">
        <i class="fa-solid fa-arrow-down"></i>
        <span id="soet-cb-fab-badge" class="soet-cb-fab-badge" style="display: none;"></span>
    </button>

    <!-- Dynamic Quick Actions Toolbar -->
    <div class="soet-cb-quick-actions" id="soet-cb-quick-actions">
        <button class="cb-qa-btn" data-query="How to apply for B.Tech admission?"><i class="fa-solid fa-graduation-cap me-1"></i>Admissions 2026</button>
        <button class="cb-qa-btn" data-query="What is the fee structure for B.Tech courses?"><i class="fa-solid fa-receipt me-1"></i>Fees</button>
        <button class="cb-qa-btn" data-query="How many vacant seats in engineering courses?"><i class="fa-solid fa-chart-pie me-1"></i>Seat Matrix</button>
        <button class="cb-qa-btn" data-query="Tell me about placements and highest salary packages"><i class="fa-solid fa-trophy me-1"></i>Placements</button>
        <button class="cb-qa-btn" data-query="What is the college contact number, email, and address?"><i class="fa-solid fa-phone me-1"></i>Helpline</button>
    </div>

    <!-- Live Autocomplete Popover / Suggestions -->
    <div class="soet-cb-suggestions" id="soet-cb-suggestions">
        <!-- Chips loaded dynamically -->
    </div>

    <!-- Active Reply / Quote Preview -->
    <div id="soet-cb-reply-preview" class="soet-cb-reply-preview" style="display: none;">
        <div class="d-flex align-items-center justify-content-between">
            <span class="small font-semibold text-primary"><i class="fa-solid fa-reply me-1"></i> Replying to bot</span>
            <button id="soet-cb-reply-cancel" class="btn-link text-muted p-0 border-0 bg-transparent" title="Cancel reply"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <p id="soet-cb-reply-snippet" class="small text-muted mb-0 text-truncate"></p>
    </div>

    <!-- Input Bar -->
    <div class="soet-cb-input-bar">
        <button class="soet-cb-mic-btn" id="soet-cb-mic-btn" title="Voice Input (Speech-to-Text)">
            <i class="fa-solid fa-microphone"></i>
        </button>
        <input type="text" id="soet-cb-input" class="soet-cb-input" placeholder="Ask CampusAI anything..." autocomplete="off" maxlength="500">
        <button class="soet-cb-send-btn" id="soet-cb-send-btn" title="Send Message">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>

    <!-- Sub-input Status & Character Counter -->
    <div class="soet-cb-input-status">
        <span id="soet-cb-typing-status" class="soet-cb-status-indicator"><i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i>Ready</span>
        <span id="soet-cb-char-counter" class="soet-cb-char-count">0/500</span>
    </div>
</div>

<script src="<?php echo APP_URL; ?>/chatbot/assets/js/chatbot.js" defer></script>
