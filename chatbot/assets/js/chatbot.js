/**
 * CampusAI — SOET Intelligent Chatbot Frontend Manager (Vanilla JavaScript - v3 Upgrade)
 *
 * Handles client-side chat interactions, including:
 * - Multilingual switching (English, Hindi, Marathi) [PRESERVED]
 * - Web Speech API Voice Recognition (Speech-to-Text) [PRESERVED]
 * - Browser Web Speech Text-to-Speech (Audio Voice Reader) [PRESERVED]
 * - Dynamic Autocomplete Query Suggestions [PRESERVED]
 * - Downloadable plain text conversation transcript exports (.txt) [PRESERVED]
 * - Response evaluation ratings (Thumbs Up / Down feedback) [PRESERVED]
 * - Markdown tables, code blocks with 1-click copy [PRESERVED]
 * - Window drawer open/close, clear history, and fullscreen expand [PRESERVED]
 * 
 * v3 Upgrades:
 * - In-drawer conversation search with live highlight and match navigation
 * - Web Audio API synthesized gentle sound chimes (toggleable)
 * - Scroll-to-bottom Floating Action Button (FAB) with unread indicator
 * - Quick Actions Toolbar for instant common queries
 * - Message quote / reply action
 * - Delivered & processed double checkmarks (✓✓)
 * - Topic & Engine classification badge on bot responses
 * - Character counter (0/500) & live connection status monitor
 * - Anti-spam rate limiting protection
 *
 * @package SOET_College_Project
 * @subpackage Chatbot_Frontend
 */

function initCampusAIChatbot() {
    // Prevent double initialization
    if (window.__soet_chatbot_initialized) return;
    window.__soet_chatbot_initialized = true;

    // -------------------------------------------------------------------------
    // Core DOM Element Bindings
    // -------------------------------------------------------------------------
    const triggerBtn = document.getElementById('soet-chatbot-trigger');
    const windowDrawer = document.getElementById('soet-chatbot-window');
    const closeBtn = document.getElementById('soet-cb-close-btn');
    const clearBtn = document.getElementById('soet-cb-clear-btn');
    const exportBtn = document.getElementById('soet-cb-export-btn');
    const expandBtn = document.getElementById('soet-cb-expand-btn');
    const expandIcon = document.getElementById('expandIcon');
    const themeBtn = document.getElementById('soet-cb-theme-btn');
    const themeIcon = document.getElementById('themeIcon');
    const inputField = document.getElementById('soet-cb-input');
    const sendBtn = document.getElementById('soet-cb-send-btn');
    const micBtn = document.getElementById('soet-cb-mic-btn');
    const messagesContainer = document.getElementById('soet-cb-messages');
    const suggestionsContainer = document.getElementById('soet-cb-suggestions');
    const langBtns = document.querySelectorAll('.soet-cb-lang-btn');

    // v3 Upgraded DOM Elements
    const searchToggleBtn = document.getElementById('soet-cb-search-toggle-btn');
    const searchBar = document.getElementById('soet-cb-search-bar');
    const searchInput = document.getElementById('soet-cb-search-input');
    const searchCountSpan = document.getElementById('soet-cb-search-count');
    const searchPrevBtn = document.getElementById('soet-cb-search-prev');
    const searchNextBtn = document.getElementById('soet-cb-search-next');
    const searchCloseBtn = document.getElementById('soet-cb-search-close');
    const soundBtn = document.getElementById('soet-cb-sound-btn');
    const soundIcon = document.getElementById('soundIcon');
    const scrollFab = document.getElementById('soet-cb-scroll-fab');
    const fabBadge = document.getElementById('soet-cb-fab-badge');
    const quickActionBtns = document.querySelectorAll('.cb-qa-btn');
    const replyPreview = document.getElementById('soet-cb-reply-preview');
    const replySnippet = document.getElementById('soet-cb-reply-snippet');
    const replyCancelBtn = document.getElementById('soet-cb-reply-cancel');
    const charCounter = document.getElementById('soet-cb-char-counter');
    const typingStatus = document.getElementById('soet-cb-typing-status');
    const offlineBanner = document.getElementById('soet-cb-offline-banner');

    // -------------------------------------------------------------------------
    // State Variables
    // -------------------------------------------------------------------------
    let sessionToken = localStorage.getItem('soet_cb_session') || '';
    let currentLanguage = localStorage.getItem('soet_cb_lang') || 'en';
    let currentTheme = localStorage.getItem('soet_cb_theme') || 'light';
    let soundEnabled = localStorage.getItem('soet_cb_sound') !== 'false'; // default true
    let recognition = null;
    let isListening = false;
    let typeDebounce = null;
    let isSending = false;
    let activeQuote = '';

    // Search state
    let searchMatches = [];
    let currentSearchIndex = -1;

    // API Base URL Resolution
    const getApiBase = () => {
        if (typeof window.APP_URL !== 'undefined' && window.APP_URL) {
            return window.APP_URL.replace(/\/$/, '');
        }
        const path = window.location.pathname;
        const projectPrefix = path.startsWith('/project') ? '/project' : '';
        return window.location.origin + projectPrefix;
    };
    const apiBase = getApiBase();

    // -------------------------------------------------------------------------
    // Sound Generator (Web Audio API - Zero External Audio Files)
    // -------------------------------------------------------------------------
    const playChime = (type = 'receive') => {
        if (!soundEnabled) return;
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();

            if (type === 'send') {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(480, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(640, ctx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.04, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.09);
            } else {
                // Gentle two-tone chime for bot response
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
                gain1.gain.setValueAtTime(0.05, ctx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start();
                osc1.stop(ctx.currentTime + 0.15);

                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(659.25, ctx.currentTime + 0.08); // E5
                gain2.gain.setValueAtTime(0.05, ctx.currentTime + 0.08);
                gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(ctx.currentTime + 0.08);
                osc2.stop(ctx.currentTime + 0.25);
            }
        } catch (e) {
            // Audio Context restricted or unavailable
        }
    };

    // Sound Toggle Handler
    const updateSoundUI = () => {
        if (soundIcon) {
            soundIcon.className = soundEnabled ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark text-danger';
        }
        if (soundBtn) {
            soundBtn.title = soundEnabled ? 'Mute Sound Chimes' : 'Unmute Sound Chimes';
        }
    };
    updateSoundUI();

    soundBtn?.addEventListener('click', () => {
        soundEnabled = !soundEnabled;
        localStorage.setItem('soet_cb_sound', soundEnabled ? 'true' : 'false');
        updateSoundUI();
    });

    // -------------------------------------------------------------------------
    // Theme Initializer & Toggle Handler [PRESERVED]
    // -------------------------------------------------------------------------
    if (currentTheme === 'dark') {
        windowDrawer?.classList.add('dark-mode');
        if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
    }

    themeBtn?.addEventListener('click', () => {
        windowDrawer.classList.toggle('dark-mode');
        const isDark = windowDrawer.classList.contains('dark-mode');
        localStorage.setItem('soet_cb_theme', isDark ? 'dark' : 'light');
        if (themeIcon) {
            themeIcon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    });

    // -------------------------------------------------------------------------
    // Multilingual Controls [PRESERVED]
    // -------------------------------------------------------------------------
    langBtns.forEach(btn => {
        if (btn.dataset.lang === currentLanguage) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
        btn.addEventListener('click', () => {
            langBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentLanguage = btn.dataset.lang;
            localStorage.setItem('soet_cb_lang', currentLanguage);
        });
    });

    // -------------------------------------------------------------------------
    // Window Drawer Toggle & Maximize [PRESERVED]
    // -------------------------------------------------------------------------
    const openChatbot = () => {
        if (!windowDrawer) return;
        windowDrawer.classList.add('open');
        inputField?.focus();
        if (messagesContainer && messagesContainer.children.length === 0) {
            showWelcomeMessage();
        }
    };

    const closeChatbot = () => {
        if (!windowDrawer) return;
        windowDrawer.classList.remove('open');
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
    };

    const toggleChatbot = () => {
        if (!windowDrawer) return;
        if (windowDrawer.classList.contains('open')) {
            closeChatbot();
        } else {
            openChatbot();
        }
    };

    // Expose helpers globally
    window.openCampusAIChatbot = openChatbot;
    window.closeCampusAIChatbot = closeChatbot;
    window.toggleCampusAIChatbot = toggleChatbot;

    triggerBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleChatbot();
    });

    closeBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        closeChatbot();
    });

    expandBtn?.addEventListener('click', () => {
        if (!windowDrawer) return;
        windowDrawer.classList.toggle('maximized');
        if (windowDrawer.classList.contains('maximized')) {
            expandIcon.className = 'fa-solid fa-compress';
            expandBtn.title = 'Minimize Window';
        } else {
            expandIcon.className = 'fa-solid fa-expand';
            expandBtn.title = 'Expand Window';
        }
    });

    // -------------------------------------------------------------------------
    // Chat Clear & Transcript Export [PRESERVED]
    // -------------------------------------------------------------------------
    exportBtn?.addEventListener('click', () => {
        if (!sessionToken) {
            alert('No active conversation transcript to export yet.');
            return;
        }
        window.open(`${apiBase}/chatbot/api/export_chat.php?session_token=${encodeURIComponent(sessionToken)}`, '_blank');
    });

    clearBtn?.addEventListener('click', () => {
        if (confirm('Clear chat conversation history?')) {
            localStorage.removeItem('soet_cb_session');
            sessionToken = '';
            messagesContainer.innerHTML = '';
            clearReply();
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
            showWelcomeMessage();
        }
    });

    // -------------------------------------------------------------------------
    // Character Counter & Live Status Monitor
    // -------------------------------------------------------------------------
    inputField?.addEventListener('input', () => {
        const len = inputField.value.length;
        if (charCounter) {
            charCounter.innerText = `${len}/500`;
            if (len >= 450) {
                charCounter.style.color = '#ef4444';
            } else {
                charCounter.style.color = '';
            }
        }
    });

    // Offline / Online Event Listeners
    window.addEventListener('offline', () => {
        if (offlineBanner) offlineBanner.style.display = 'block';
        if (typingStatus) typingStatus.innerHTML = '<i class="fa-solid fa-circle text-danger me-1" style="font-size: 8px;"></i>Offline';
    });
    window.addEventListener('online', () => {
        if (offlineBanner) offlineBanner.style.display = 'none';
        if (typingStatus) typingStatus.innerHTML = '<i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i>Ready';
    });

    // -------------------------------------------------------------------------
    // Quick Actions Toolbar
    // -------------------------------------------------------------------------
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const query = btn.dataset.query;
            if (query) {
                sendMessage(query);
            }
        });
    });

    // -------------------------------------------------------------------------
    // Reply / Quote Manager
    // -------------------------------------------------------------------------
    const setReply = (textSnippet) => {
        activeQuote = textSnippet.trim();
        if (replySnippet) replySnippet.innerText = activeQuote;
        if (replyPreview) replyPreview.style.display = 'block';
        inputField?.focus();
    };

    const clearReply = () => {
        activeQuote = '';
        if (replyPreview) replyPreview.style.display = 'none';
    };

    replyCancelBtn?.addEventListener('click', clearReply);

    // -------------------------------------------------------------------------
    // Scroll-to-Bottom FAB Handler
    // -------------------------------------------------------------------------
    const checkScrollPosition = () => {
        if (!messagesContainer || !scrollFab) return;
        const distFromBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight;
        if (distFromBottom > 120) {
            scrollFab.style.display = 'flex';
        } else {
            scrollFab.style.display = 'none';
            if (fabBadge) fabBadge.style.display = 'none';
        }
    };

    messagesContainer?.addEventListener('scroll', checkScrollPosition);

    scrollFab?.addEventListener('click', () => {
        if (messagesContainer) {
            messagesContainer.scrollTo({ top: messagesContainer.scrollHeight, behavior: 'smooth' });
            if (fabBadge) fabBadge.style.display = 'none';
        }
    });

    // -------------------------------------------------------------------------
    // In-Drawer Search Engine
    // -------------------------------------------------------------------------
    searchToggleBtn?.addEventListener('click', () => {
        if (!searchBar) return;
        const isHidden = searchBar.style.display === 'none';
        searchBar.style.display = isHidden ? 'flex' : 'none';
        if (isHidden) {
            searchInput?.focus();
        } else {
            clearSearch();
        }
    });

    searchCloseBtn?.addEventListener('click', () => {
        if (searchBar) searchBar.style.display = 'none';
        clearSearch();
    });

    const clearSearch = () => {
        if (searchInput) searchInput.value = '';
        if (searchCountSpan) searchCountSpan.innerText = '';
        document.querySelectorAll('.cb-search-highlight').forEach(el => {
            const parent = el.parentNode;
            parent.replaceChild(document.createTextNode(el.innerText), el);
            parent.normalize();
        });
        searchMatches = [];
        currentSearchIndex = -1;
    };

    const performSearch = () => {
        clearSearch();
        const query = searchInput?.value.trim().toLowerCase();
        if (!query || query.length < 2) return;

        const bubbles = messagesContainer.querySelectorAll('.cb-msg-bubble');
        bubbles.forEach(bubble => {
            const text = bubble.innerText;
            if (text.toLowerCase().includes(query)) {
                highlightTextInNode(bubble, query);
            }
        });

        searchMatches = Array.from(messagesContainer.querySelectorAll('.cb-search-highlight'));
        if (searchMatches.length > 0) {
            currentSearchIndex = 0;
            updateSearchFocus();
        } else if (searchCountSpan) {
            searchCountSpan.innerText = '0 found';
        }
    };

    const highlightTextInNode = (node, query) => {
        const innerHTML = node.innerHTML;
        // Simple case-insensitive mark replacement (avoiding HTML tag corruption)
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        // Only replace text outside tags
        node.innerHTML = innerHTML.replace(/(<[^>]*>)|([^<]+)/g, (match, tag, text) => {
            if (tag) return tag;
            return text.replace(regex, '<mark class="cb-search-highlight">$1</mark>');
        });
    };

    const updateSearchFocus = () => {
        searchMatches.forEach((m, idx) => {
            if (idx === currentSearchIndex) {
                m.classList.add('active');
                m.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                m.classList.remove('active');
            }
        });
        if (searchCountSpan) {
            searchCountSpan.innerText = `${currentSearchIndex + 1}/${searchMatches.length}`;
        }
    };

    searchInput?.addEventListener('input', () => {
        clearTimeout(typeDebounce);
        typeDebounce = setTimeout(performSearch, 300);
    });

    searchNextBtn?.addEventListener('click', () => {
        if (searchMatches.length === 0) return;
        currentSearchIndex = (currentSearchIndex + 1) % searchMatches.length;
        updateSearchFocus();
    });

    searchPrevBtn?.addEventListener('click', () => {
        if (searchMatches.length === 0) return;
        currentSearchIndex = (currentSearchIndex - 1 + searchMatches.length) % searchMatches.length;
        updateSearchFocus();
    });

    // -------------------------------------------------------------------------
    // Message Dispatcher & Rate Limiting
    // -------------------------------------------------------------------------
    const sendMessage = async (textToSend = null) => {
        if (isSending) return; // Anti-spam protection
        const rawText = textToSend || inputField.value.trim();
        if (!rawText) return;

        let fullMessage = rawText;
        if (activeQuote) {
            fullMessage = `[Regarding: "${activeQuote}"]\n${rawText}`;
            clearReply();
        }

        if (!textToSend && inputField) {
            inputField.value = '';
            if (charCounter) charCounter.innerText = '0/500';
        }

        // Render User Message & Play Send Sound
        appendMessage('user', fullMessage);
        playChime('send');

        // Rate limit lock for 600ms
        isSending = true;
        if (sendBtn) sendBtn.disabled = true;
        if (typingStatus) typingStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-primary me-1" style="font-size: 8px;"></i>Thinking...';

        // Show Typing Indicator
        const typingId = showTypingIndicator();

        try {
            const res = await fetch(`${apiBase}/chatbot/api/send.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: fullMessage,
                    session_token: sessionToken,
                    language: currentLanguage
                })
            });

            removeTypingIndicator(typingId);

            const data = await res.json();
            if (data.success) {
                if (data.session_token) {
                    sessionToken = data.session_token;
                    localStorage.setItem('soet_cb_session', sessionToken);
                }
                appendMessage('bot', data.formatted_html, data.message_id, data.source, data.source_url, data.suggested_chips, data.intent, data.model_engine);
                playChime('receive');
            } else {
                appendMessage('bot', '<p class="text-danger mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i> ' + (data.message || 'Error processing request.') + '</p>');
            }
        } catch (err) {
            removeTypingIndicator(typingId);
            appendMessage('bot', '<p class="text-danger mb-0"><i class="fa-solid fa-wifi me-1"></i> Network connection issue. Your request will retry automatically when connected.</p>');
        } finally {
            isSending = false;
            if (sendBtn) sendBtn.disabled = false;
            if (typingStatus) typingStatus.innerHTML = '<i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i>Ready';
        }
    };

    sendBtn?.addEventListener('click', () => sendMessage());
    inputField?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Real-Time Live Autocomplete Typing Listener [PRESERVED]
    inputField?.addEventListener('input', (e) => {
        clearTimeout(typeDebounce);
        const q = e.target.value.trim();
        typeDebounce = setTimeout(() => {
            loadSuggestions(q);
        }, 250);
    });

    // -------------------------------------------------------------------------
    // Web Speech API Voice Recognition [PRESERVED]
    // -------------------------------------------------------------------------
    if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
        const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRec();
        recognition.continuous = false;
        recognition.interimResults = false;

        recognition.onstart = () => {
            isListening = true;
            micBtn?.classList.add('listening');
            if (inputField) inputField.placeholder = 'Listening... Speak now...';
        };

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            if (inputField) inputField.value = transcript;
            sendMessage(transcript);
        };

        recognition.onerror = () => stopListening();
        recognition.onend = () => stopListening();

        micBtn?.addEventListener('click', () => {
            if (isListening) {
                recognition.stop();
            } else {
                const langMap = { en: 'en-US', hi: 'hi-IN', mr: 'mr-IN' };
                recognition.lang = langMap[currentLanguage] || 'en-US';
                recognition.start();
            }
        });
    } else {
        if (micBtn) micBtn.style.display = 'none';
    }

    const stopListening = () => {
        isListening = false;
        micBtn?.classList.remove('listening');
        if (inputField) inputField.placeholder = 'Ask CampusAI anything...';
    };

    function formatTimeNow() {
        const d = new Date();
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // -------------------------------------------------------------------------
    // Message Renderer & Bubble Formatter
    // -------------------------------------------------------------------------
    function appendMessage(sender, htmlContent, messageId = null, source = 'database', sourceUrl = '', suggestedChips = [], intent = '', modelEngine = '') {
        const wrapper = document.createElement('div');
        wrapper.className = `cb-msg ${sender}`;

        // Top Topic/Model Badge on Bot Bubbles
        if (sender === 'bot') {
            const badge = document.createElement('div');
            badge.className = 'cb-topic-badge';
            if (source === 'database' || source === 'DATABASE') {
                badge.innerHTML = '<i class="fa-solid fa-building-columns text-primary"></i> SOET Official Database';
            } else if (source === 'WEBSITE') {
                badge.innerHTML = '<i class="fa-solid fa-globe text-primary"></i> Campus Information';
            } else if (source === 'MIXED') {
                badge.innerHTML = '<i class="fa-solid fa-layer-group text-warning"></i> Hybrid Verified';
            } else {
                badge.innerHTML = '<i class="fa-solid fa-bolt text-warning"></i> CampusAI Neural Model';
            }
            wrapper.appendChild(badge);
        }

        const bubble = document.createElement('div');
        bubble.className = 'cb-msg-bubble';
        bubble.innerHTML = htmlContent;

        wrapper.appendChild(bubble);

        const actions = document.createElement('div');
        actions.className = 'cb-msg-actions';

        // Message Timestamp
        const timeSpan = document.createElement('span');
        timeSpan.className = 'text-muted';
        timeSpan.style.fontSize = '10.5px';
        timeSpan.innerText = formatTimeNow();
        actions.appendChild(timeSpan);

        if (sender === 'user') {
            // Read receipts (double checkmark)
            const check = document.createElement('span');
            check.className = 'cb-read-receipt';
            check.innerHTML = '<i class="fa-solid fa-check-double" title="Delivered & Processed"></i>';
            actions.appendChild(check);
        }

        if (sender === 'bot') {
            // Copy button [PRESERVED]
            const copyBtn = document.createElement('button');
            copyBtn.className = 'cb-action-btn';
            copyBtn.innerHTML = '<i class="fa-regular fa-copy me-1"></i>Copy';
            copyBtn.onclick = () => {
                const plainText = bubble.innerText;
                navigator.clipboard.writeText(plainText);
                copyBtn.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i>Copied';
                setTimeout(() => copyBtn.innerHTML = '<i class="fa-regular fa-copy me-1"></i>Copy', 2000);
            };
            actions.appendChild(copyBtn);

            // Quote / Reply Action (v3 Upgrade)
            const quoteBtn = document.createElement('button');
            quoteBtn.className = 'cb-action-btn';
            quoteBtn.innerHTML = '<i class="fa-solid fa-reply me-1"></i>Reply';
            quoteBtn.title = 'Quote and ask follow-up';
            quoteBtn.onclick = () => {
                const plainText = bubble.innerText.replace(/[\n\r]+/g, ' ').substring(0, 80);
                setReply(plainText);
            };
            actions.appendChild(quoteBtn);

            // Text-to-Speech (Speaker) Button [PRESERVED]
            if ('speechSynthesis' in window) {
                const speakBtn = document.createElement('button');
                speakBtn.className = 'cb-action-btn';
                speakBtn.innerHTML = '<i class="fa-solid fa-volume-high me-1"></i>Listen';
                speakBtn.onclick = () => {
                    if (window.speechSynthesis.speaking) {
                        window.speechSynthesis.cancel();
                        speakBtn.innerHTML = '<i class="fa-solid fa-volume-high me-1"></i>Listen';
                        return;
                    }
                    const textToRead = bubble.innerText.replace(/🤖 CampusAI.*$/g, '');
                    const utterance = new SpeechSynthesisUtterance(textToRead);
                    const langMap = { en: 'en-US', hi: 'hi-IN', mr: 'mr-IN' };
                    utterance.lang = langMap[currentLanguage] || 'en-US';
                    utterance.rate = 1.0;
                    utterance.onstart = () => speakBtn.innerHTML = '<i class="fa-solid fa-stop text-danger me-1"></i>Stop';
                    utterance.onend = () => speakBtn.innerHTML = '<i class="fa-solid fa-volume-high me-1"></i>Listen';
                    utterance.onerror = () => speakBtn.innerHTML = '<i class="fa-solid fa-volume-high me-1"></i>Listen';
                    window.speechSynthesis.speak(utterance);
                };
                actions.appendChild(speakBtn);
            }

            // Rating Thumbs Up / Down [PRESERVED]
            if (messageId) {
                const upBtn = document.createElement('button');
                upBtn.className = 'cb-action-btn';
                upBtn.title = 'Helpful response';
                upBtn.innerHTML = '<i class="fa-regular fa-thumbs-up"></i>';
                upBtn.onclick = () => sendFeedback(messageId, 'positive', upBtn, downBtn);

                const downBtn = document.createElement('button');
                downBtn.className = 'cb-action-btn';
                downBtn.title = 'Unhelpful response';
                downBtn.innerHTML = '<i class="fa-regular fa-thumbs-down"></i>';
                downBtn.onclick = () => sendFeedback(messageId, 'negative', upBtn, downBtn);

                actions.appendChild(upBtn);
                actions.appendChild(downBtn);
            }

            wrapper.appendChild(actions);

            // Append Dynamic Follow-Up Chips under bot bubble [PRESERVED]
            if (suggestedChips && suggestedChips.length > 0) {
                const chipsWrapper = document.createElement('div');
                chipsWrapper.className = 'cb-msg-followups';
                suggestedChips.forEach(chip => {
                    const chipBtn = document.createElement('button');
                    chipBtn.className = 'cb-followup-chip';
                    chipBtn.innerText = chip.label;
                    chipBtn.onclick = () => sendMessage(chip.query);
                    chipsWrapper.appendChild(chipBtn);
                });
                wrapper.appendChild(chipsWrapper);
            }
        } else {
            wrapper.appendChild(actions);
        }

        messagesContainer.appendChild(wrapper);

        // Check if user is scrolled up; if so, show unread badge on FAB
        const distFromBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight;
        if (distFromBottom > 120 && sender === 'bot') {
            if (fabBadge) fabBadge.style.display = 'block';
        } else {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    // -------------------------------------------------------------------------
    // Rating Feedback API [PRESERVED]
    // -------------------------------------------------------------------------
    async function sendFeedback(messageId, rating, upBtn, downBtn) {
        try {
            await fetch(`${apiBase}/chatbot/api/feedback.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message_id: messageId, rating: rating })
            });

            if (rating === 'positive') {
                upBtn.className = 'cb-action-btn text-success font-bold';
                downBtn.style.display = 'none';
            } else {
                downBtn.className = 'cb-action-btn text-danger font-bold';
                upBtn.style.display = 'none';
            }
        } catch (e) {}
    }

    // -------------------------------------------------------------------------
    // Dynamic Suggestions / Autocomplete Chips [PRESERVED]
    // -------------------------------------------------------------------------
    loadSuggestions();

    async function loadSuggestions(query = '') {
        if (!suggestionsContainer) return;
        try {
            const url = query ? `${apiBase}/chatbot/api/suggestions.php?q=${encodeURIComponent(query)}` : `${apiBase}/chatbot/api/suggestions.php`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.success && data.suggestions && data.suggestions.length > 0) {
                suggestionsContainer.innerHTML = '';
                data.suggestions.forEach(item => {
                    const chip = document.createElement('button');
                    chip.className = 'cb-chip';
                    chip.innerText = item.label;
                    chip.onclick = () => {
                        if (inputField) inputField.value = '';
                        sendMessage(item.query);
                    };
                    suggestionsContainer.appendChild(chip);
                });
            }
        } catch (e) {}
    }

    // -------------------------------------------------------------------------
    // Welcome Greeting with Initial Chips [PRESERVED]
    // -------------------------------------------------------------------------
    function showWelcomeMessage() {
        const hour = new Date().getHours();
        let greeting = 'Hello! 👋';
        if (hour < 12) greeting = 'Good morning! ☀️';
        else if (hour < 17) greeting = 'Good afternoon! 🌤️';
        else greeting = 'Good evening! 🌙';

        const welcomeHtml = `
            <p class="mb-2"><strong>${greeting} Welcome to SOET MGM University</strong></p>
            <p class="mb-1">I am <strong>🤖 CampusAI</strong>, your 24/7 intelligent assistant powered by the autonomous Neural Knowledge Engine.</p>
            <p class="small text-muted mb-1">I can help you with:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li>🎓 <strong>Admissions &amp; Seats</strong> — Eligibility, application timeline, vacant seat matrix</li>
                <li>💰 <strong>Fee Structure &amp; Aid</strong> — 4-year tuition breakdown, scholarship quotas</li>
                <li>💻 <strong>Tech &amp; Coding</strong> — Python, Java, DSA, Web Dev, AI/ML concepts &amp; code</li>
                <li>🏆 <strong>Career &amp; Placements</strong> — Recruiters, ₹18.5 LPA top packages, internships</li>
            </ul>
        `;
        const initialChips = [
            { label: '🎓 Admissions 2026', query: 'How to apply for B.Tech admission?' },
            { label: '💰 Fee Structure', query: 'What is the fee structure for B.Tech courses?' },
            { label: '📊 Seat Availability', query: 'What is the seat availability in CSE?' },
            { label: '🏆 Placements & Packages', query: 'Tell me about placements and highest salary packages' }
        ];
        appendMessage('bot', welcomeHtml, null, 'system', '', initialChips);
    }

    // -------------------------------------------------------------------------
    // Typing Indicator Animation [PRESERVED]
    // -------------------------------------------------------------------------
    function showTypingIndicator() {
        const id = 'typing_' + Date.now();
        const wrapper = document.createElement('div');
        wrapper.className = 'cb-msg bot';
        wrapper.id = id;
        wrapper.innerHTML = `
            <div class="cb-msg-bubble p-2">
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        messagesContainer.appendChild(wrapper);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        return id;
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }
}

// Auto-initialize when ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCampusAIChatbot);
} else {
    initCampusAIChatbot();
}

// Global click delegation as fallback for any trigger button across pages
document.addEventListener('click', (e) => {
    const trigger = e.target.closest('#soet-chatbot-trigger, #chatbotToggle, .chatbot-trigger, [data-chatbot-trigger="true"], a[href="#chatbot"], a[href="#campusai"]');
    if (trigger) {
        const windowDrawer = document.getElementById('soet-chatbot-window');
        if (windowDrawer && windowDrawer.contains(trigger)) return;
        e.preventDefault();
        if (typeof window.toggleCampusAIChatbot === 'function') {
            window.toggleCampusAIChatbot();
        } else {
            initCampusAIChatbot();
            if (typeof window.toggleCampusAIChatbot === 'function') {
                window.toggleCampusAIChatbot();
            }
        }
    }
});
