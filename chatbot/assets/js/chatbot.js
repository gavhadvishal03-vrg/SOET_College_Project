/**
 * 🤖 CampusAI — SOET Intelligent Chatbot Manager (Vanilla JavaScript)
 * Features:
 * - Multi-lingual (EN / HI / MR)
 * - Voice Input (Speech-to-Text)
 * - Audio Response Reader (Text-to-Speech)
 * - Real-Time Dynamic Autocomplete Suggestions
 * - 1-Click Conversation Transcript Export
 * - Maximize / Minimize Studio Expansion
 * - Dynamic Contextual Follow-up Chips
 * - Formatted Code Block Copying
 */

document.addEventListener('DOMContentLoaded', () => {
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

    let sessionToken = localStorage.getItem('soet_cb_session') || '';
    let currentLanguage = localStorage.getItem('soet_cb_lang') || 'en';
    let currentTheme = localStorage.getItem('soet_cb_theme') || 'light';
    let recognition = null;
    let isListening = false;
    let typeDebounce = null;

    // Apply stored theme
    if (currentTheme === 'dark') {
        windowDrawer?.classList.add('dark-mode');
        if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
    }

    // Theme Toggle Handler
    themeBtn?.addEventListener('click', () => {
        windowDrawer.classList.toggle('dark-mode');
        const isDark = windowDrawer.classList.contains('dark-mode');
        localStorage.setItem('soet_cb_theme', isDark ? 'dark' : 'light');
        if (themeIcon) {
            themeIcon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    });

    // Set initial language UI
    langBtns.forEach(btn => {
        if (btn.dataset.lang === currentLanguage) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // 1. Toggle Drawer Window
    triggerBtn?.addEventListener('click', () => {
        windowDrawer.classList.toggle('open');
        if (windowDrawer.classList.contains('open')) {
            inputField.focus();
            if (messagesContainer.children.length === 0) {
                showWelcomeMessage();
            }
        }
    });

    closeBtn?.addEventListener('click', () => {
        windowDrawer.classList.remove('open');
        if (window.speechSynthesis) {
            window.speechSynthesis.cancel();
        }
    });

    // Maximize / Expand Toggle
    expandBtn?.addEventListener('click', () => {
        windowDrawer.classList.toggle('maximized');
        if (windowDrawer.classList.contains('maximized')) {
            expandIcon.className = 'fa-solid fa-compress';
            expandBtn.title = 'Minimize Window';
        } else {
            expandIcon.className = 'fa-solid fa-expand';
            expandBtn.title = 'Expand Window';
        }
    });

    // 1-Click Export Chat Transcript
    exportBtn?.addEventListener('click', () => {
        if (!sessionToken) {
            alert('No active conversation transcript to export yet.');
            return;
        }
        window.open(`/chatbot/api/export_chat.php?session_token=${encodeURIComponent(sessionToken)}`, '_blank');
    });

    clearBtn?.addEventListener('click', () => {
        if (confirm('Clear chat conversation history?')) {
            localStorage.removeItem('soet_cb_session');
            sessionToken = '';
            messagesContainer.innerHTML = '';
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
            showWelcomeMessage();
        }
    });

    // 2. Language Selector
    langBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            langBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentLanguage = btn.dataset.lang;
            localStorage.setItem('soet_cb_lang', currentLanguage);
        });
    });

    // 3. Send Message Handler
    const sendMessage = async (textToSend = null) => {
        const text = textToSend || inputField.value.trim();
        if (!text) return;

        if (!textToSend) {
            inputField.value = '';
        }

        // Render User Message
        appendMessage('user', text);

        // Show Typing Indicator
        const typingId = showTypingIndicator();

        try {
            const res = await fetch('/chatbot/api/send.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: text,
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
                appendMessage('bot', data.formatted_html, data.message_id, data.source, data.source_url, data.suggested_chips);
            } else {
                appendMessage('bot', '<p class="text-danger mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i> ' + (data.message || 'Error processing request.') + '</p>');
            }
        } catch (err) {
            removeTypingIndicator(typingId);
            appendMessage('bot', '<p class="text-danger mb-0"><i class="fa-solid fa-wifi me-1"></i> Network connection error. Please try again.</p>');
        }
    };

    sendBtn?.addEventListener('click', () => sendMessage());
    inputField?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Real-Time Live Autocomplete Typing Listener
    inputField?.addEventListener('input', (e) => {
        clearTimeout(typeDebounce);
        const q = e.target.value.trim();
        typeDebounce = setTimeout(() => {
            loadSuggestions(q);
        }, 250);
    });

    // 4. Web Speech API (Voice Input)
    if ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window) {
        const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRec();
        recognition.continuous = false;
        recognition.interimResults = false;

        recognition.onstart = () => {
            isListening = true;
            micBtn.classList.add('listening');
            inputField.placeholder = 'Listening... Speak now...';
        };

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            inputField.value = transcript;
            sendMessage(transcript);
        };

        recognition.onerror = () => {
            stopListening();
        };

        recognition.onend = () => {
            stopListening();
        };

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

    // 5. Append Message Element
    function appendMessage(sender, htmlContent, messageId = null, source = 'database', sourceUrl = '', suggestedChips = []) {
        const wrapper = document.createElement('div');
        wrapper.className = `cb-msg ${sender}`;

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

        if (sender === 'bot') {
            // Copy button
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

            // Text-to-Speech (Speaker) Button
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

            // Rating Thumbs Up / Down
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

            // Append Dynamic Follow-Up Chips under bot bubble
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
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // 6. Send Rating Feedback
    async function sendFeedback(messageId, rating, upBtn, downBtn) {
        try {
            await fetch('/chatbot/api/feedback.php', {
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

    // 7. Load Dynamic Suggestions / Autocomplete Chips
    loadSuggestions();

    async function loadSuggestions(query = '') {
        if (!suggestionsContainer) return;
        try {
            const url = query ? `/chatbot/api/suggestions.php?q=${encodeURIComponent(query)}` : '/chatbot/api/suggestions.php';
            const res = await fetch(url);
            const data = await res.json();
            if (data.success && data.suggestions && data.suggestions.length > 0) {
                suggestionsContainer.innerHTML = '';
                data.suggestions.forEach(item => {
                    const chip = document.createElement('button');
                    chip.className = 'cb-chip';
                    chip.innerText = item.label;
                    chip.onclick = () => {
                        inputField.value = '';
                        sendMessage(item.query);
                    };
                    suggestionsContainer.appendChild(chip);
                });
            }
        } catch (e) {}
    }

    function showWelcomeMessage() {
        const welcomeHtml = `
            <p class="mb-2"><strong>Hello! 👋 Welcome to SOET MGM University</strong></p>
            <p class="mb-1">I am <strong>🤖 CampusAI</strong>, your official 24/7 intelligent assistant.</p>
            <p class="small text-muted mb-1">I can help you with:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li>🎓 <strong>Admissions &amp; Seats</strong> — Eligibility, application timeline, vacant seats</li>
                <li>💰 <strong>Fee Structure</strong> — 4-year tuition breakdown, scholarship quotas</li>
                <li>💻 <strong>Tech &amp; Coding</strong> — Python, Java, DSA, Web Dev, AI/ML concepts</li>
                <li>🏆 <strong>Career &amp; Placements</strong> — Recruiters, salary packages, internships</li>
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
});
