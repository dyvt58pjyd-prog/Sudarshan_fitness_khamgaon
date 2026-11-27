/**
 * Standalone Gate Attendance Portal
 * High-Speed Search & Validation Engine
 * Sudarshan Fitness
 */

(function () {
    'use strict';

    // State Variables
    let currentCandidates = [];
    let highlightedIndex = -1;
    let searchAbortController = null;
    let searchCache = new Map();
    let isSubmitting = false;
    let autoDismissTimer = null;
    let autoDismissInterval = null;

    // Preferences (Stored locally)
    let isSoundEnabled = localStorage.getItem('sf_att_sound') !== '0';
    let isVoiceEnabled = localStorage.getItem('sf_att_voice') !== '0';

    // Elements
    const searchInput = document.getElementById('attendanceSearchInput');
    const clearBtn = document.getElementById('btnClearSearch');
    const dropdown = document.getElementById('searchResultsDropdown');
    const outcomeCard = document.getElementById('outcomeDisplayCard');
    const outcomeIcon = document.getElementById('outcomeStatusIcon');
    const outcomeTitle = document.getElementById('outcomeTitleText');
    const outcomeAvatar = document.getElementById('outcomeMemberAvatar');
    const outcomeName = document.getElementById('outcomeMemberName');
    const outcomeMeta = document.getElementById('outcomeMemberMeta');
    const expiryBox = document.getElementById('outcomeExpiryBox');
    const expiryLabel = document.getElementById('outcomeExpiryLabel');
    const expiryDate = document.getElementById('outcomeExpiryDate');
    const expiryPill = document.getElementById('outcomeExpiryPill');
    const outcomeFooter = document.getElementById('outcomeMetaFooter');
    const countdownFill = document.getElementById('countdownProgressFill');

    // Stats & Feed Elements
    const statTotal = document.getElementById('statTotalToday');
    const statInside = document.getElementById('statInsideNow');
    const statOut = document.getElementById('statCheckedOut');
    const statDenied = document.getElementById('statDeniedCount');
    const feedContainer = document.getElementById('recentAttendanceFeed');

    // Audio Context Setup
    let audioCtx = null;
    function getAudioContext() {
        if (!audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                audioCtx = new AudioContext();
            }
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    // Play synthesized Web Audio sounds (0ms latency, zero external downloads)
    function playAudioTone(type) {
        if (!isSoundEnabled) return;
        try {
            const ctx = getAudioContext();
            if (!ctx) return;

            if (type === 'success') {
                // Pleasant ascending two-tone chime (C5 -> E5)
                const osc1 = ctx.createOscillator();
                const osc2 = ctx.createOscillator();
                const gain = ctx.createGain();

                osc1.type = 'sine';
                osc2.type = 'sine';

                osc1.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
                osc2.frequency.setValueAtTime(659.25, ctx.currentTime + 0.12); // E5

                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);

                osc1.connect(gain);
                osc2.connect(gain);
                gain.connect(ctx.destination);

                osc1.start(ctx.currentTime);
                osc1.stop(ctx.currentTime + 0.14);
                osc2.start(ctx.currentTime + 0.12);
                osc2.stop(ctx.currentTime + 0.5);

            } else {
                // Warning dual buzz for entry denial
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, ctx.currentTime);
                osc.frequency.setValueAtTime(180, ctx.currentTime + 0.15);

                gain.gain.setValueAtTime(0.25, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.45);
            }
        } catch (e) {
            console.warn('Audio tone error:', e);
        }
    }

    // Speech Announcer (Web Speech Synthesis)
    function speakVoiceAnnouncement(text) {
        if (!isVoiceEnabled || !('speechSynthesis' in window)) return;
        try {
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 1.05;
            utterance.pitch = 1.0;
            utterance.lang = 'en-IN';
            window.speechSynthesis.speak(utterance);
        } catch (e) {
            console.warn('Speech error:', e);
        }
    }

    // Live Digital Clock
    function initDigitalClock() {
        const timeElem = document.getElementById('liveClockTime');
        const dateElem = document.getElementById('liveClockDate');
        if (!timeElem || !dateElem) return;

        function updateClock() {
            const now = new Date();
            timeElem.textContent = now.toLocaleTimeString('en-IN', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            dateElem.textContent = now.toLocaleDateString('en-IN', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }
        setInterval(updateClock, 1000);
        updateClock();
    }

    // Toggle Audio & Voice
    window.toggleSound = function () {
        isSoundEnabled = !isSoundEnabled;
        localStorage.setItem('sf_att_sound', isSoundEnabled ? '1' : '0');
        updateAudioControlsUI();
        if (isSoundEnabled) playAudioTone('success');
    };

    window.toggleVoice = function () {
        isVoiceEnabled = !isVoiceEnabled;
        localStorage.setItem('sf_att_voice', isVoiceEnabled ? '1' : '0');
        updateAudioControlsUI();
        if (isVoiceEnabled) speakVoiceAnnouncement('Voice announcements enabled');
    };

    window.toggleFullscreenKiosk = function () {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    };

    function updateAudioControlsUI() {
        const soundBtn = document.getElementById('btnToggleSound');
        const voiceBtn = document.getElementById('btnToggleVoice');
        if (soundBtn) {
            soundBtn.innerHTML = isSoundEnabled ? '🔊 Sound On' : '🔇 Muted';
            soundBtn.classList.toggle('active-toggle', isSoundEnabled);
        }
        if (voiceBtn) {
            voiceBtn.innerHTML = isVoiceEnabled ? '🗣️ Voice On' : '🤐 Voice Off';
            voiceBtn.classList.toggle('active-toggle', isVoiceEnabled);
        }
    }

    // Live Instant Search with Debounce & Cancellation
    let debounceTimer = null;
    function handleSearchInput() {
        const query = searchInput.value.trim();

        if (query.length > 0) {
            clearBtn.classList.add('visible');
        } else {
            clearBtn.classList.remove('visible');
            closeDropdown();
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performSearch(query);
        }, 110);
    }

    async function performSearch(query) {
        if (searchCache.has(query)) {
            renderDropdown(searchCache.get(query));
            return;
        }

        if (searchAbortController) {
            searchAbortController.abort();
        }
        searchAbortController = new AbortController();

        try {
            const res = await fetch(`api/search.php?q=${encodeURIComponent(query)}`, {
                signal: searchAbortController.signal
            });
            const data = await res.json();

            if (data.success) {
                searchCache.set(query, data.results);
                // Keep cache at reasonable size
                if (searchCache.size > 80) {
                    const firstKey = searchCache.keys().next().value;
                    searchCache.delete(firstKey);
                }
                renderDropdown(data.results);
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Search request failed:', err);
            }
        }
    }

    // Render Dropdown Matches
    function renderDropdown(candidates) {
        currentCandidates = candidates;
        highlightedIndex = candidates.length > 0 ? 0 : -1;

        if (!candidates || candidates.length === 0) {
            dropdown.innerHTML = `<div class="no-results-hint">No gym member found matching your input. Press Enter to submit search directly.</div>`;
            dropdown.classList.add('active');
            return;
        }

        let html = '';
        candidates.forEach((mem, index) => {
            const isHigh = index === highlightedIndex ? 'highlighted' : '';
            const statusBadge = `<span class="badge-status ${mem.badge_class}">${mem.status_text}</span>`;
            const phoneStr = mem.mobile ? `<span>📞 ${escapeHtml(mem.mobile)}</span>` : '';
            const emailStr = mem.email ? `<span>✉️ ${escapeHtml(mem.email)}</span>` : '';
            const uidStr = `<span>#${escapeHtml(mem.userid)}</span>`;

            html += `
                <div class="result-item ${isHigh}" data-index="${index}" onclick="selectMemberByIndex(${index})">
                    <div class="result-user-meta">
                        <img src="${escapeHtml(mem.photo_url)}" alt="Avatar" class="result-avatar" onerror="this.src='../images/default_avatar.png'">
                        <div class="result-info">
                            <h3>${escapeHtml(mem.username)}</h3>
                            <p>${uidStr} ${phoneStr} ${emailStr}</p>
                        </div>
                    </div>
                    <div class="result-status-col">
                        ${statusBadge}
                        <span class="result-plan-label">${escapeHtml(mem.plan_name)}</span>
                    </div>
                </div>
            `;
        });

        dropdown.innerHTML = html;
        dropdown.classList.add('active');
    }

    function closeDropdown() {
        dropdown.classList.remove('active');
        currentCandidates = [];
        highlightedIndex = -1;
    }

    window.selectMemberByIndex = function (index) {
        if (currentCandidates[index]) {
            const member = currentCandidates[index];
            closeDropdown();
            logAttendance(member.userid);
        }
    };

    // Keyboard Navigation in Search
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (currentCandidates.length > 0) {
                highlightedIndex = (highlightedIndex + 1) % currentCandidates.length;
                updateHighlightedItem();
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (currentCandidates.length > 0) {
                highlightedIndex = (highlightedIndex - 1 + currentCandidates.length) % currentCandidates.length;
                updateHighlightedItem();
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlightedIndex >= 0 && currentCandidates[highlightedIndex]) {
                const member = currentCandidates[highlightedIndex];
                closeDropdown();
                logAttendance(member.userid);
            } else {
                const val = searchInput.value.trim();
                if (val) {
                    closeDropdown();
                    logAttendance(null, val);
                }
            }
        } else if (e.key === 'Escape') {
            closeDropdown();
            searchInput.value = '';
            clearBtn.classList.remove('visible');
            dismissOutcomeCard();
        }
    });

    function updateHighlightedItem() {
        const items = dropdown.querySelectorAll('.result-item');
        items.forEach((item, idx) => {
            if (idx === highlightedIndex) {
                item.classList.add('highlighted');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('highlighted');
            }
        });
    }

    // Submit Attendance Log
    async function logAttendance(uid, identifier = null) {
        if (isSubmitting) return;
        isSubmitting = true;

        searchInput.value = '';
        clearBtn.classList.remove('visible');
        closeDropdown();

        try {
            const payload = uid ? { uid: uid } : { identifier: identifier };
            const res = await fetch('api/log.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            displayOutcome(data);
            refreshFeedAndStats();
        } catch (err) {
            console.error('Error logging attendance:', err);
            displayOutcome({
                success: false,
                denied: true,
                message: 'Connection or Server Error. Please check your network and try again.'
            });
        } finally {
            isSubmitting = false;
            // Always keep focus on search input for rapid continuous scanning/typing
            setTimeout(() => {
                searchInput.focus();
            }, 50);
        }
    }

    // Display Big Outcome Card (Green Tick vs Red Denied)
    function displayOutcome(data) {
        // Clear any running timers
        if (autoDismissTimer) clearTimeout(autoDismissTimer);
        if (autoDismissInterval) clearInterval(autoDismissInterval);

        outcomeCard.className = 'outcome-display-card';

        if (data.success) {
            // SUCCESSFUL CHECK-IN / CHECK-OUT -> BIG GREEN TICK (✓)
            const isCheckout = data.action === 'check-out';
            outcomeCard.classList.add('state-success');

            outcomeIcon.innerHTML = isCheckout ? '👋' : '✓';
            outcomeTitle.textContent = isCheckout ? 'Check-Out Logged Successfully' : '✓ Attendance Logged Successfully';

            outcomeAvatar.src = data.photo || '../images/default_avatar.png';
            outcomeName.textContent = data.name || 'Member';
            outcomeMeta.innerHTML = `
                <span>ID: #${escapeHtml(data.uid)}</span>
                ${data.mobile ? `<span>📞 ${escapeHtml(data.mobile)}</span>` : ''}
                <span>🏋️ ${escapeHtml(data.plan)}</span>
            `;

            expiryBox.style.display = 'flex';
            expiryLabel.textContent = 'Membership Expiration Date';
            expiryDate.textContent = data.expire || 'Active';

            if (data.expiry_status === 'EXPIRING_SOON') {
                expiryPill.textContent = `⚠️ Expiring Soon (${data.days_left} Days Left)`;
            } else {
                expiryPill.textContent = `✓ Active (${data.days_left} Days Remaining)`;
            }

            const timeStr = isCheckout ? `Checked Out at ${data.exit_time}` : `Checked In at ${data.entry_time}`;
            outcomeFooter.innerHTML = `
                <span class="meta-chip">⏰ ${timeStr}</span>
                <span class="meta-chip">🔥 ${data.streak || 1} Day Streak</span>
                <span class="meta-chip">⚡ Sub-10ms Verify</span>
            `;

            playAudioTone('success');
            speakVoiceAnnouncement(`Welcome ${data.name}. Membership valid until ${data.expire}`);

        } else {
            // MEMBERSHIP INACTIVE / EXPIRED / DENIED -> BIG RED CROSS (✕)
            outcomeCard.classList.add('state-denied');

            outcomeIcon.innerHTML = '✕';
            outcomeTitle.textContent = '🚨 ENTRY DENIED • ATTENDANCE BLOCKED';

            outcomeAvatar.src = data.photo || '../images/default_avatar.png';
            outcomeName.textContent = data.name || 'Access Denied';
            outcomeMeta.innerHTML = `
                ${data.uid ? `<span>ID: #${escapeHtml(data.uid)}</span>` : ''}
                ${data.mobile ? `<span>📞 ${escapeHtml(data.mobile)}</span>` : ''}
                <span>${escapeHtml(data.plan || 'No Active Plan')}</span>
            `;

            expiryBox.style.display = 'flex';
            expiryLabel.textContent = 'Membership Status';
            expiryDate.textContent = data.expire || 'EXPIRED';

            if (data.days_expired) {
                expiryPill.textContent = `🚨 Expired ${data.days_expired} Days Ago - Renewal Required`;
            } else {
                expiryPill.textContent = `🚨 Membership Inactive - Access Restricted`;
            }

            const searchParam = data.mobile || data.uid || data.name || '';
            outcomeFooter.innerHTML = `
                <a href="../desk/index.php?search=${encodeURIComponent(searchParam)}" target="_blank" class="btn-renew-action">
                    ⚡ Renew at Front Desk ➔
                </a>
                <span class="meta-chip" style="color: #fca5a5;">${escapeHtml(data.message || 'Please consult reception to renew.')}</span>
            `;

            playAudioTone('error');
            speakVoiceAnnouncement(`Access Denied. Membership for ${data.name || 'member'} is expired.`);
        }

        outcomeCard.style.display = 'flex';

        // Auto dismiss progress bar (4 seconds)
        const duration = 4000;
        const interval = 50;
        let elapsed = 0;
        countdownFill.style.width = '100%';

        autoDismissInterval = setInterval(() => {
            elapsed += interval;
            const pct = Math.max(0, 100 - (elapsed / duration) * 100);
            countdownFill.style.width = pct + '%';
            if (elapsed >= duration) {
                clearInterval(autoDismissInterval);
                dismissOutcomeCard();
            }
        }, interval);
    }

    function dismissOutcomeCard() {
        if (autoDismissInterval) clearInterval(autoDismissInterval);
        outcomeCard.style.display = 'none';
        searchInput.focus();
    }

    // Refresh Live Stats & Recent Feed
    async function refreshFeedAndStats() {
        try {
            const res = await fetch('api/recent.php');
            const data = await res.json();
            if (!data.success) return;

            // Update stats numbers
            statTotal.textContent = data.stats.total_present;
            statInside.textContent = data.stats.inside_now;
            statOut.textContent = data.stats.checked_out;
            statDenied.textContent = data.stats.denied_today;

            // Update recent log items
            if (data.logs.length === 0) {
                feedContainer.innerHTML = `<div class="empty-feed">No check-ins logged yet today.</div>`;
                return;
            }

            let html = '';
            data.logs.forEach(log => {
                const actionBadge = log.is_inside 
                    ? `<span class="time-badge">🟢 ${log.entry_time}</span><span class="feed-action-tag">Inside</span>`
                    : `<span class="time-badge" style="color:#94a3b8">👋 ${log.exit_time || log.entry_time}</span><span class="feed-action-tag">Out</span>`;

                html += `
                    <div class="feed-item">
                        <div class="feed-user-meta">
                            <img src="${escapeHtml(log.photo_url)}" alt="Avatar" class="feed-avatar" onerror="this.src='../images/default_avatar.png'">
                            <div>
                                <h4>${escapeHtml(log.username)}</h4>
                                <p>#${escapeHtml(log.uid)} &bull; ${escapeHtml(log.plan_name)}</p>
                            </div>
                        </div>
                        <div class="feed-time-col">
                            ${actionBadge}
                        </div>
                    </div>
                `;
            });

            feedContainer.innerHTML = html;
        } catch (e) {
            console.warn('Feed refresh error:', e);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Global Key Listener: autofocus on input
    document.addEventListener('keydown', function (e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
            if (e.key === '/' || (e.ctrlKey && e.key === 'k') || (e.metaKey && e.key === 'k')) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            } else if (e.key === 'f' || e.key === 'F') {
                if (!e.ctrlKey && !e.metaKey && !e.altKey) {
                    toggleFullscreenKiosk();
                }
            } else if (e.key === 'm' || e.key === 'M') {
                toggleSound();
            } else if (/^[a-zA-Z0-9]$/.test(e.key)) {
                searchInput.focus();
            }
        }
    });

    // Close dropdown on outside click
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            closeDropdown();
        }
    });

    // Clear Button
    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        clearBtn.classList.remove('visible');
        closeDropdown();
        searchInput.focus();
    });

    // Input listener
    searchInput.addEventListener('input', handleSearchInput);

    // Initialization
    document.addEventListener('DOMContentLoaded', function () {
        initDigitalClock();
        updateAudioControlsUI();
        refreshFeedAndStats();
        setInterval(refreshFeedAndStats, 15000); // Poll every 15s

        // Prime audio on first user click
        document.body.addEventListener('click', function primeAudio() {
            getAudioContext();
            document.body.removeEventListener('click', primeAudio);
        }, { once: true });

        // Auto focus search bar
        setTimeout(() => {
            searchInput.focus();
        }, 100);
    });

})();
