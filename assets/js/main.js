// SOET College Website - Global JavaScript
if (typeof APP_URL === 'undefined') { var APP_URL = window.location.origin + '/project'; }
document.addEventListener('DOMContentLoaded', function() {
    // Chatbot functionality
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSend = document.getElementById('chatbotSend');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const promptButtons = document.querySelectorAll('.prompt-btn');

    if (chatbotToggle && chatbotWindow) {
        // Toggle chatbot window
        chatbotToggle.addEventListener('click', function() {
            chatbotWindow.classList.toggle('active');
            if (chatbotWindow.classList.contains('active')) {
                chatbotInput.focus();
                // Scroll to bottom
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }
        });

        // Close chatbot
        chatbotClose.addEventListener('click', function() {
            chatbotWindow.classList.remove('active');
        });

        // Send message function
        const sendMessage = function(queryText) {
            if (!queryText.trim()) return;

            // Append User message
            appendMessage(queryText, 'user');
            chatbotInput.value = '';

            // Loading indicator or small delay
            const botMsgDiv = document.createElement('div');
            botMsgDiv.className = 'chat-msg bot';
            botMsgDiv.innerHTML = '<div class="msg-bubble">Typing...</div>';
            chatbotMessages.appendChild(botMsgDiv);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

            // AJAX request to backend
            fetch(APP_URL + '/api/chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ query: queryText })
            })
            .then(response => response.json())
            .then(data => {
                botMsgDiv.remove();
                if (data.success || data.status === 'success') {
                    appendMessage(data.response, 'bot');
                } else {
                    appendMessage("Sorry, I encountered an issue processing your query. Please try again.", 'bot');
                }
            })
            .catch(error => {
                botMsgDiv.remove();
                appendMessage("Sorry, I couldn't reach the chatbot server. Please check your internet connection.", 'bot');
                console.error('Error:', error);
            });
        };

        // Append Message Helper
        const appendMessage = function(text, sender) {
            const msgDiv = document.createElement('div');
            msgDiv.className = `chat-msg ${sender}`;
            msgDiv.innerHTML = `<div class="msg-bubble">${text}</div>`;
            chatbotMessages.appendChild(msgDiv);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        };

        // Send on click
        chatbotSend.addEventListener('click', function() {
            sendMessage(chatbotInput.value);
        });

        // Send on Enter keypress
        chatbotInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage(chatbotInput.value);
            }
        });

        // Prompt buttons click
        promptButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                sendMessage(btn.innerText);
            });
        });
    }

    // Smooth scroll for page anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Handle alert auto-close
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 5000);
    });

    // Back to Top button logic
    let bttBtn = document.getElementById('backToTopBtn');
    if (!bttBtn) {
        bttBtn = document.createElement('button');
        bttBtn.id = 'backToTopBtn';
        bttBtn.setAttribute('title', 'Back to top');
        bttBtn.setAttribute('aria-label', 'Back to top');
        bttBtn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';
        document.body.appendChild(bttBtn);
    }

    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            bttBtn.style.display = 'flex';
        } else {
            bttBtn.style.display = 'none';
        }
    });

    bttBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Spotlight Command Palette Controller (Ctrl+K)
    const spotlightModalEl = document.getElementById('spotlightModal');
    const spotlightTrigger = document.getElementById('spotlightTriggerBtn');
    const spotlightInput = document.getElementById('spotlightInput');
    const spotlightResults = document.getElementById('spotlightResults');
    let spotlightModal = null;

    if (spotlightModalEl && typeof bootstrap !== 'undefined') {
        spotlightModal = new bootstrap.Modal(spotlightModalEl);

        const openSpotlight = () => {
            spotlightModal.show();
            setTimeout(() => {
                spotlightInput?.focus();
                loadSpotlightResults('');
            }, 300);
        };

        spotlightTrigger?.addEventListener('click', (e) => {
            e.preventDefault();
            openSpotlight();
        });

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                openSpotlight();
            }
        });

        let searchTimeout = null;
        spotlightInput?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const q = e.target.value.trim();
            searchTimeout = setTimeout(() => {
                loadSpotlightResults(q);
            }, 200);
        });

        function loadSpotlightResults(q) {
            if (!spotlightResults) return;
            spotlightResults.innerHTML = '<div class="text-center py-4 text-white-50"><div class="spinner-border spinner-border-sm text-warning me-2"></div> Searching portal...</div>';

            const fetchUrl = (window.APP_URL || '') + '/api/quick_search.php' + (q ? ('?q=' + encodeURIComponent(q)) : '');
            fetch(fetchUrl)
                .then(res => res.json())
                .then(data => {
                    if (!data.success || !data.results) {
                        spotlightResults.innerHTML = '<div class="text-center py-4 text-white-50">No matching results found.</div>';
                        return;
                    }

                    let html = '';
                    const r = data.results;

                    const renderCategory = (title, items) => {
                        if (!items || items.length === 0) return '';
                        let catHtml = `<div class="mb-3"><div class="text-uppercase text-warning font-semibold small px-2 mb-1" style="font-size: 11px; letter-spacing: 0.5px;">${title}</div>`;
                        items.forEach(item => {
                            catHtml += `
                                <a href="${item.url}" class="spotlight-item d-flex align-items-center justify-content-between p-2 rounded text-decoration-none mb-1">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="spotlight-icon-box bg-dark text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fa-solid ${item.icon || 'fa-arrow-right'}"></i>
                                        </div>
                                        <div>
                                            <div class="text-white font-semibold" style="font-size: 13.5px;">${item.title}</div>
                                            <div class="text-white-50 small" style="font-size: 11.5px;">${item.desc}</div>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-white-50 small"></i>
                                </a>
                            `;
                        });
                        catHtml += `</div>`;
                        return catHtml;
                    };

                    html += renderCategory('Quick Navigation', r.pages);
                    html += renderCategory('Academic Courses & Fees', r.courses);
                    html += renderCategory('Departments & Labs', r.departments);
                    html += renderCategory('Faculty Directory', r.faculty);
                    html += renderCategory('Campus Events', r.events);
                    html += renderCategory('Official Notices', r.notices);

                    if (!html) {
                        html = '<div class="text-center py-4 text-white-50"><i class="fa-solid fa-circle-question fs-2 mb-2 d-block text-warning"></i>No results found for "<b>' + q + '</b>"</div>';
                    }

                    spotlightResults.innerHTML = html;
                })
                .catch(err => {
                    spotlightResults.innerHTML = '<div class="text-center py-4 text-danger small">Error searching portal.</div>';
                });
        }
    }
});
