/**
 * SUDARSHAN FITNESS - FRONT DESK PORTAL JAVASCRIPT
 * Powers instant member search, live renewal calculations, webcam capture & AJAX submissions.
 */

(function () {
    'use strict';

    // State
    let currentSelectedMember = null;
    let gymPlans = [];
    let searchDebounceTimer = null;
    let focusedDropdownIndex = -1;
    let currentSearchResults = [];
    let webcamStream = null;

    // DOM Elements
    const searchInput = document.getElementById('memberSearch');
    const searchDropdown = document.getElementById('searchDropdown');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const renewalConsole = document.getElementById('renewalConsole');
    const renewalWelcome = document.getElementById('renewalWelcome');
    
    // Summary Box Elements
    const summaryPhoto = document.getElementById('summaryPhoto');
    const summaryName = document.getElementById('summaryName');
    const summaryId = document.getElementById('summaryId');
    const summaryMobile = document.getElementById('summaryMobile');
    const summaryEmail = document.getElementById('summaryEmail');
    const summaryGender = document.getElementById('summaryGender');
    const summaryCurrentPlan = document.getElementById('summaryCurrentPlan');
    const summaryExpiryDate = document.getElementById('summaryExpiryDate');
    const summaryStatusBadge = document.getElementById('summaryStatusBadge');
    
    // Renewal Form Elements
    const renewalForm = document.getElementById('renewalForm');
    const renewUidInput = document.getElementById('renewUid');
    const renewPlanSelect = document.getElementById('renewPlanSelect');
    const renewStartDateInput = document.getElementById('renewStartDate');
    const renewDiscountInput = document.getElementById('renewDiscount');
    const renewPaidAmountInput = document.getElementById('renewPaidAmount');
    const renewPaymentMode = document.getElementById('renewPaymentMode');
    const renewNewExpiryText = document.getElementById('renewNewExpiryText');
    const renewPlanPriceText = document.getElementById('renewPlanPriceText');
    const renewDiscountRow = document.getElementById('renewDiscountRow');
    const renewDiscountValText = document.getElementById('renewDiscountValText');
    const renewTotalPayableText = document.getElementById('renewTotalPayableText');
    const renewSubmitBtn = document.getElementById('renewSubmitBtn');
    const balanceDueRow = document.getElementById('balanceDueRow');
    const balanceDueDateInput = document.getElementById('balanceDueDate');
    
    // Modal Elements
    const celebrationModal = document.getElementById('celebrationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalMemberName = document.getElementById('modalMemberName');
    const modalMemberId = document.getElementById('modalMemberId');
    const modalPlanName = document.getElementById('modalPlanName');
    const modalExpiry = document.getElementById('modalExpiry');
    const modalEntryPin = document.getElementById('modalEntryPin');
    const modalPaidAmount = document.getElementById('modalPaidAmount');
    const modalPrintBtn = document.getElementById('modalPrintBtn');
    const modalNewBtn = document.getElementById('modalNewBtn');

    // Registration Form Elements
    const regForm = document.getElementById('regForm');
    const regSubmitBtn = document.getElementById('regSubmitBtn');
    const webcamVideo = document.getElementById('webcam-video');
    const webcamPreview = document.getElementById('webcam-preview');
    const startWebcamBtn = document.getElementById('startWebcamBtn');
    const capturePhotoBtn = document.getElementById('capturePhotoBtn');
    const retakePhotoBtn = document.getElementById('retakePhotoBtn');
    const memberPhotoBase64 = document.getElementById('member_photo_base64');
    const filePhotoInput = document.getElementById('filePhotoInput');
    const webcamCanvas = document.getElementById('webcamCanvas');

    // Initialize Gym Plans from embedded JSON or DOM
    function initGymPlans() {
        if (window.DESK_GYM_PLANS && Array.isArray(window.DESK_GYM_PLANS)) {
            gymPlans = window.DESK_GYM_PLANS;
        } else {
            gymPlans = [];
            const planOpts = renewPlanSelect.querySelectorAll('option');
            planOpts.forEach(opt => {
                if (opt.value) {
                    gymPlans.push({
                        pid: opt.value,
                        planName: opt.getAttribute('data-name') || opt.textContent,
                        amount: parseInt(opt.getAttribute('data-price') || '0', 10),
                        validity: opt.getAttribute('data-validity') || '1m',
                        discount_lock: parseInt(opt.getAttribute('data-discount-lock') || '0', 10)
                    });
                }
            });
        }
    }

    // ==========================================
    // TAB NAVIGATION
    // ==========================================
    window.switchDeskTab = function (tabName) {
        document.querySelectorAll('.desk-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));

        const targetTab = document.getElementById('tab-' + tabName);
        const targetBtn = document.getElementById('tab-btn-' + tabName);

        if (targetTab && targetBtn) {
            targetTab.classList.add('active');
            targetBtn.classList.add('active');

            if (tabName === 'renewal') {
                setTimeout(() => searchInput.focus(), 100);
            } else if (tabName === 'history') {
                loadRecentDeskHistory();
            }
        }
    };

    // ==========================================
    // STAR FEATURE: LIVE AUTOCOMPLETE SEARCH
    // ==========================================
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();

            if (searchClearBtn) {
                searchClearBtn.style.display = query.length > 0 ? 'block' : 'none';
            }

            if (query.length === 0) {
                closeSearchDropdown();
                return;
            }

            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                fetchMemberSuggestions(query);
            }, 180);
        });

        // Keyboard navigation inside search dropdown
        searchInput.addEventListener('keydown', function (e) {
            const items = searchDropdown.querySelectorAll('.dropdown-item');
            if (!items || items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                focusedDropdownIndex = (focusedDropdownIndex + 1) % items.length;
                updateDropdownFocus(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                focusedDropdownIndex = (focusedDropdownIndex - 1 + items.length) % items.length;
                updateDropdownFocus(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (focusedDropdownIndex >= 0 && items[focusedDropdownIndex]) {
                    items[focusedDropdownIndex].click();
                } else if (items.length > 0) {
                    items[0].click();
                }
            } else if (e.key === 'Escape') {
                closeSearchDropdown();
            }
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchClearBtn.style.display = 'none';
            closeSearchDropdown();
            searchInput.focus();
        });
    }

    // Close dropdown on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.search-wrapper')) {
            closeSearchDropdown();
        }
    });

    function closeSearchDropdown() {
        if (searchDropdown) {
            searchDropdown.style.display = 'none';
            searchDropdown.innerHTML = '';
            focusedDropdownIndex = -1;
            currentSearchResults = [];
        }
    }

    function updateDropdownFocus(items) {
        items.forEach((item, idx) => {
            if (idx === focusedDropdownIndex) {
                item.classList.add('focused');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('focused');
            }
        });
    }

    function highlightMatch(text, query) {
        if (!text || !query) return text;
        const qEsc = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${qEsc})`, 'gi');
        return text.replace(regex, '<span class="highlight-match">$1</span>');
    }

    function fetchMemberSuggestions(query) {
        fetch(`api/search.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.matches && data.matches.length > 0) {
                    currentSearchResults = data.matches;
                    renderSearchDropdown(data.matches, query);
                } else {
                    currentSearchResults = [];
                    searchDropdown.innerHTML = `
                        <div class="dropdown-empty">
                            <p>No registered member found matching "<strong>${escapeHtml(query)}</strong>"</p>
                            <button type="button" class="btn-sm" style="margin-top: 10px; background: var(--primary); color: #fff; border: none;" onclick="switchDeskTab('register'); document.getElementById('reg_name').value='${escapeHtml(query)}';">
                                ➕ Register "${escapeHtml(query)}" as New Member
                            </button>
                        </div>
                    `;
                    searchDropdown.style.display = 'block';
                }
            })
            .catch(err => {
                console.error("Search error:", err);
            });
    }

    function renderSearchDropdown(matches, query) {
        searchDropdown.innerHTML = '';
        focusedDropdownIndex = -1;

        matches.forEach((member, index) => {
            const item = document.createElement('div');
            item.className = 'dropdown-item';
            item.setAttribute('data-uid', member.userid);
            item.setAttribute('data-index', index);

            const highlightedName = highlightMatch(member.username, query);
            const highlightedId = highlightMatch(member.userid, query);
            const highlightedMobile = highlightMatch(member.mobile, query);

            item.innerHTML = `
                <div class="dropdown-member-info">
                    <img src="${member.photo_thumb}" class="member-avatar-thumb" alt="${escapeHtml(member.username)}" onerror="this.src='../images/default_avatar.png'">
                    <div>
                        <div class="dropdown-member-name">
                            <span>${highlightedName}</span>
                            <span style="font-size: 11px; background: rgba(255,255,255,0.08); padding: 1px 6px; border-radius: 4px; font-family: var(--font-mono);">#${highlightedId}</span>
                        </div>
                        <div class="dropdown-member-sub">
                            📱 ${highlightedMobile} &bull; Plan: <strong>${escapeHtml(member.plan_name)}</strong>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="status-badge ${member.status_class}">${member.status_label}</span>
                </div>
            `;

            item.addEventListener('click', function () {
                selectMember(member.userid);
            });

            searchDropdown.appendChild(item);
        });

        searchDropdown.style.display = 'block';
    }

    // ==========================================
    // MEMBER SELECTION & DIRECT RENEWAL CONSOLE
    // ==========================================
    window.selectMember = function (userid) {
        closeSearchDropdown();

        if (searchInput) {
            searchInput.value = '';
            if (searchClearBtn) searchClearBtn.style.display = 'none';
        }

        // Fetch complete profile
        fetch(`api/search.php?id=${encodeURIComponent(userid)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.member) {
                    currentSelectedMember = data.member;
                    populateRenewalConsole(data.member);
                } else {
                    alert("Error: " + (data.error || "Could not retrieve member details."));
                }
            })
            .catch(err => {
                console.error("Fetch member error:", err);
                alert("Failed to load member profile.");
            });
    };

    function populateRenewalConsole(m) {
        // 1. Populate Member Summary Card
        summaryPhoto.src = m.photo_url || '../images/default_avatar.png';
        summaryPhoto.onerror = function () { this.src = '../images/default_avatar.png'; };
        summaryName.textContent = m.username;
        summaryId.textContent = `#${m.userid}`;
        summaryMobile.textContent = m.mobile || '—';
        summaryEmail.textContent = m.email || '—';
        summaryGender.textContent = m.gender || '—';
        summaryCurrentPlan.textContent = m.plan_name || 'No Plan';
        summaryExpiryDate.textContent = m.expire_date ? formatDateDisplay(m.expire_date) : 'Never Enrolled';
        
        summaryStatusBadge.textContent = m.status_label;
        summaryStatusBadge.className = `status-badge ${m.status_class}`;

        // 2. Populate Renewal Form
        renewUidInput.value = m.userid;

        // Choose Plan (default to current plan if matches, else first plan)
        let matchingPlanFound = false;
        if (m.pid) {
            for (let i = 0; i < renewPlanSelect.options.length; i++) {
                if (renewPlanSelect.options[i].value === m.pid) {
                    renewPlanSelect.selectedIndex = i;
                    matchingPlanFound = true;
                    break;
                }
            }
        }
        if (!matchingPlanFound && renewPlanSelect.options.length > 1) {
            renewPlanSelect.selectedIndex = 1; // default to first real plan
        }

        // Smart Start Date calculation
        // If currently active, start from existing expiry date so member doesn't lose paid days!
        // If expired or no plan, start from today.
        renewStartDateInput.value = m.suggested_start_date;

        // Reset discount & payments
        renewDiscountInput.value = '0';
        renewPaymentMode.value = 'Cash';

        // Recalculate live prices & new expiry
        recalculateRenewalSummary();

        // Reveal Renewal Console
        if (renewalWelcome) renewalWelcome.style.display = 'none';
        if (renewalConsole) {
            renewalConsole.style.display = 'block';
            renewalConsole.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    window.resetRenewalSelection = function () {
        currentSelectedMember = null;
        if (renewalConsole) renewalConsole.style.display = 'none';
        if (renewalWelcome) renewalWelcome.style.display = 'block';
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
    };

    window.setRenewalStartDate = function (type) {
        if (!currentSelectedMember) return;
        const todayStr = getTodayDateString();

        if (type === 'today') {
            renewStartDateInput.value = todayStr;
        } else if (type === 'expiry') {
            if (currentSelectedMember.expire_date) {
                renewStartDateInput.value = currentSelectedMember.expire_date;
            } else {
                renewStartDateInput.value = todayStr;
            }
        }
        recalculateRenewalSummary();
    };

    // ==========================================
    // LIVE EXPIRY & PRICING CALCULATIONS
    // ==========================================
    function recalculateRenewalSummary() {
        if (!renewPlanSelect) return;

        const selectedOpt = renewPlanSelect.options[renewPlanSelect.selectedIndex];
        if (!selectedOpt || !selectedOpt.value) {
            renewNewExpiryText.textContent = 'Please select a plan';
            renewPlanPriceText.textContent = '₹0.00';
            renewTotalPayableText.textContent = '₹0.00';
            renewPaidAmountInput.value = '0';
            return;
        }

        const planPrice = parseInt(selectedOpt.getAttribute('data-price') || '0', 10);
        const validityStr = (selectedOpt.getAttribute('data-validity') || '1m').toLowerCase().trim();
        const maxDiscount = parseInt(selectedOpt.getAttribute('data-discount-lock') || '0', 10);

        let discount = parseInt(renewDiscountInput.value || '0', 10);
        if (discount < 0) discount = 0;

        // Validate max discount lock
        const warningEl = document.getElementById('renewDiscountWarning');
        if (maxDiscount > 0 && discount > maxDiscount) {
            if (warningEl) {
                warningEl.textContent = `Warning: Max discount allowed is ₹${maxDiscount}`;
                warningEl.style.display = 'block';
            }
            renewSubmitBtn.disabled = true;
        } else {
            if (warningEl) warningEl.style.display = 'none';
            renewSubmitBtn.disabled = false;
        }

        const totalPayable = Math.max(0, planPrice - discount);

        // Update Price displays
        renewPlanPriceText.textContent = `₹${planPrice.toLocaleString('en-IN')}`;
        
        if (discount > 0) {
            renewDiscountRow.style.display = 'flex';
            renewDiscountValText.textContent = `- ₹${discount.toLocaleString('en-IN')}`;
        } else {
            renewDiscountRow.style.display = 'none';
        }

        renewTotalPayableText.textContent = `₹${totalPayable.toLocaleString('en-IN')}`;
        
        // Auto-fill paid amount if empty or was matching previous total
        renewPaidAmountInput.value = totalPayable;

        // Calculate Expiry Date from Start Date + Validity
        const startDateVal = renewStartDateInput.value || getTodayDateString();
        const newExpiryStr = calculateExpiryDate(startDateVal, validityStr);
        renewNewExpiryText.textContent = formatDateDisplay(newExpiryStr);

        // Update Action Button
        renewSubmitBtn.innerHTML = `⚡ Renew Membership Now — ₹${totalPayable.toLocaleString('en-IN')}`;
        
        checkBalanceVisibility();
    }

    function checkBalanceVisibility() {
        const total = parseInt(renewTotalPayableText.textContent.replace(/[^0-9]/g, '') || '0', 10);
        const paid = parseInt(renewPaidAmountInput.value || '0', 10);

        if (paid < total) {
            if (balanceDueRow) balanceDueRow.style.display = 'block';
            if (balanceDueDateInput) balanceDueDateInput.required = true;
        } else {
            if (balanceDueRow) balanceDueRow.style.display = 'none';
            if (balanceDueDateInput) balanceDueDateInput.required = false;
        }
    }

    // Attach listeners for live pricing updates
    if (renewPlanSelect) renewPlanSelect.addEventListener('change', recalculateRenewalSummary);
    if (renewStartDateInput) renewStartDateInput.addEventListener('change', recalculateRenewalSummary);
    if (renewDiscountInput) renewDiscountInput.addEventListener('input', recalculateRenewalSummary);
    if (renewPaidAmountInput) renewPaidAmountInput.addEventListener('input', checkBalanceVisibility);

    // ==========================================
    // ASYNC RENEWAL FORM SUBMISSION
    // ==========================================
    if (renewalForm) {
        renewalForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!renewPlanSelect.value) {
                alert("Please select a membership package.");
                return;
            }

            renewSubmitBtn.disabled = true;
            renewSubmitBtn.innerHTML = `<span class="spinner"></span> Processing Renewal...`;

            const formData = new FormData(renewalForm);

            fetch('api/renew.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                renewSubmitBtn.disabled = false;
                renewSubmitBtn.innerHTML = `⚡ Renew Membership Now`;

                if (data.success) {
                    showSuccessModal({
                        title: 'Membership Renewed Successfully! 🎉',
                        subtitle: `Payment of ₹${data.paid_amount.toLocaleString('en-IN')} recorded & validity updated.`,
                        name: data.member_name,
                        id: data.uid,
                        plan: data.plan_name,
                        expiry: formatDateDisplay(data.expire_date),
                        entryPin: data.entry_code,
                        paidAmount: `₹${data.paid_amount.toLocaleString('en-IN')}`,
                        receiptUrl: data.receipt_url
                    });

                    // Reset renewal console
                    resetRenewalSelection();
                } else {
                    alert("Renewal Failed: " + (data.error || "Unknown server error."));
                }
            })
            .catch(err => {
                console.error("Renewal submit error:", err);
                renewSubmitBtn.disabled = false;
                renewSubmitBtn.innerHTML = `⚡ Renew Membership Now`;
                alert("Network error processing renewal.");
            });
        });
    }

    // ==========================================
    // NEW REGISTRATION & WEBCAM LOGIC
    // ==========================================
    if (startWebcamBtn) {
        startWebcamBtn.addEventListener('click', function () {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 640, facingMode: 'user' } })
                    .then(function (stream) {
                        webcamStream = stream;
                        webcamVideo.srcObject = stream;
                        webcamVideo.play();
                        webcamVideo.style.display = 'block';
                        webcamPreview.style.display = 'none';
                        startWebcamBtn.style.display = 'none';
                        capturePhotoBtn.style.display = 'inline-block';
                        retakePhotoBtn.style.display = 'none';
                    })
                    .catch(function (err) {
                        alert("Camera access denied or unavailable: " + err.message);
                    });
            } else {
                alert("Webcam is not supported on this browser. Please use the file upload option.");
            }
        });
    }

    if (capturePhotoBtn) {
        capturePhotoBtn.addEventListener('click', function () {
            if (!webcamVideo) return;
            const ctx = webcamCanvas.getContext('2d');
            webcamCanvas.width = 400;
            webcamCanvas.height = 400;
            
            // Draw mirrored snapshot
            ctx.translate(400, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(webcamVideo, 0, 0, 400, 400);

            const base64Data = webcamCanvas.toDataURL('image/jpeg', 0.88);
            memberPhotoBase64.value = base64Data;
            webcamPreview.src = base64Data;
            webcamPreview.style.display = 'block';
            webcamVideo.style.display = 'none';

            // Stop webcam tracks
            if (webcamStream) {
                webcamStream.getTracks().forEach(t => t.stop());
                webcamStream = null;
            }

            capturePhotoBtn.style.display = 'none';
            retakePhotoBtn.style.display = 'inline-block';
        });
    }

    if (retakePhotoBtn) {
        retakePhotoBtn.addEventListener('click', function () {
            memberPhotoBase64.value = '';
            startWebcamBtn.click();
        });
    }

    if (filePhotoInput) {
        filePhotoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (evt) {
                    webcamPreview.src = evt.target.result;
                    webcamPreview.style.display = 'block';
                    if (webcamVideo) webcamVideo.style.display = 'none';
                    memberPhotoBase64.value = evt.target.result;
                    if (startWebcamBtn) startWebcamBtn.style.display = 'inline-block';
                    if (capturePhotoBtn) capturePhotoBtn.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Async Registration Submission
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            e.preventDefault();

            regSubmitBtn.disabled = true;
            regSubmitBtn.innerHTML = `<span class="spinner"></span> Registering Member...`;

            const formData = new FormData(regForm);

            fetch('api/register.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                regSubmitBtn.disabled = false;
                regSubmitBtn.innerHTML = `➕ Complete Registration & Issue Membership`;

                if (data.success) {
                    showSuccessModal({
                        title: 'New Member Registered! 🎊',
                        subtitle: `Admission complete! Member ID #${data.userid} assigned.`,
                        name: data.member_name,
                        id: data.userid,
                        plan: data.plan_name,
                        expiry: formatDateDisplay(data.expire_date),
                        entryPin: data.entry_code,
                        paidAmount: `₹${data.paid_amount.toLocaleString('en-IN')}`,
                        receiptUrl: data.receipt_url
                    });

                    regForm.reset();
                    webcamPreview.src = '../images/default_avatar.png';
                    memberPhotoBase64.value = '';
                } else {
                    alert("Registration Failed: " + (data.error || "Unknown error."));
                }
            })
            .catch(err => {
                console.error("Registration error:", err);
                regSubmitBtn.disabled = false;
                regSubmitBtn.innerHTML = `➕ Complete Registration & Issue Membership`;
                alert("Network error processing registration.");
            });
        });
    }

    // ==========================================
    // RECENT DESK HISTORY
    // ==========================================
    function loadRecentDeskHistory() {
        const tbody = document.getElementById('historyTableBody');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 24px; color: var(--text-muted);">Loading recent transactions...</td></tr>`;

        fetch('api/recent.php?limit=25')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.items && data.items.length > 0) {
                    tbody.innerHTML = '';
                    data.items.forEach((item, idx) => {
                        const tr = document.createElement('tr');
                        const isNew = item.type === 'New Registration';
                        const badgeClass = isNew ? 'status-active' : 'status-expiring';

                        tr.innerHTML = `
                            <td>${idx + 1}</td>
                            <td><span class="status-badge ${badgeClass}">${item.type}</span></td>
                            <td><strong>${escapeHtml(item.username)}</strong><br><span style="font-size: 11px; color: var(--text-muted);">📱 ${escapeHtml(item.mobile)}</span></td>
                            <td><span style="font-family: var(--font-mono); font-weight: 700;">#${item.uid}</span></td>
                            <td>${escapeHtml(item.plan_name)}</td>
                            <td><strong>₹${item.paid_amount.toLocaleString('en-IN')}</strong> (${item.payment_mode})</td>
                            <td>${formatDateDisplay(item.expire)}</td>
                            <td>
                                <a href="${item.receipt_url}" target="_blank" class="btn-sm" style="text-decoration:none; display:inline-flex; align-items:center; gap:4px; padding: 4px 10px;">
                                    🖨️ Receipt
                                </a>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 24px; color: var(--text-muted);">No desk transactions recorded yet today.</td></tr>`;
                }
            })
            .catch(err => {
                console.error("Load history error:", err);
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 24px; color: var(--danger);">Failed to load history.</td></tr>`;
            });
    }

    // ==========================================
    // MODAL DIALOG MANAGEMENT
    // ==========================================
    function showSuccessModal(data) {
        modalTitle.textContent = data.title;
        modalSubtitle.textContent = data.subtitle;
        modalMemberName.textContent = data.name;
        modalMemberId.textContent = `#${data.id}`;
        modalPlanName.textContent = data.plan;
        modalExpiry.textContent = data.expiry;
        modalEntryPin.textContent = data.entryPin;
        modalPaidAmount.textContent = data.paidAmount;

        modalPrintBtn.onclick = function () {
            window.open(data.receiptUrl + '&autoprint=1', '_blank', 'width=700,height=800');
        };

        modalNewBtn.onclick = function () {
            closeCelebrationModal();
            switchDeskTab('renewal');
        };

        celebrationModal.style.display = 'flex';
    }

    window.closeCelebrationModal = function () {
        if (celebrationModal) celebrationModal.style.display = 'none';
    };

    // Close modal on escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && celebrationModal && celebrationModal.style.display === 'flex') {
            closeCelebrationModal();
        }
    });

    // ==========================================
    // HELPER FUNCTIONS
    // ==========================================
    function calculateExpiryDate(startDateStr, validityStr) {
        validityStr = (validityStr || '1m').toLowerCase().trim();
        const parts = startDateStr.split('-');
        if (parts.length !== 3) return startDateStr;

        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);

        const expDate = new Date(year, month, day);

        if (validityStr.indexOf('d') !== -1) {
            const days = parseInt(validityStr.replace('d', ''), 10) || 30;
            expDate.setDate(expDate.getDate() + days);
        } else {
            const months = parseInt(validityStr.replace('m', ''), 10) || 1;
            expDate.setMonth(expDate.getMonth() + months);
        }

        const yyyy = expDate.getFullYear();
        const mm = String(expDate.getMonth() + 1).padStart(2, '0');
        const dd = String(expDate.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    function formatDateDisplay(dateStr) {
        if (!dateStr) return '—';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const mIdx = parseInt(parts[1], 10) - 1;
        return `${parts[2]}-${months[mIdx] || parts[1]}-${parts[0]}`;
    }

    function getTodayDateString() {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
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

    // Theme toggle
    window.toggleDeskTheme = function () {
        const isLight = document.body.classList.toggle('light-theme');
        localStorage.setItem('sf_desk_theme', isLight ? 'light' : 'dark');
        const icon = document.getElementById('themeToggleIcon');
        if (icon) icon.textContent = isLight ? '🌙' : '☀️';
    };

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', function () {
        initGymPlans();

        // Restore theme preference
        const savedTheme = localStorage.getItem('sf_desk_theme');
        if (savedTheme === 'light') {
            document.body.classList.add('light-theme');
            const icon = document.getElementById('themeToggleIcon');
            if (icon) icon.textContent = '🌙';
        }

        // Live clock updater
        const clockEl = document.getElementById('deskLiveClock');
        if (clockEl) {
            setInterval(() => {
                const now = new Date();
                clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            }, 1000);
        }

        // Auto-select member if ?renew_uid= is provided in query params
        const urlParams = new URLSearchParams(window.location.search);
        const autoRenewUid = urlParams.get('renew_uid') || urlParams.get('id');
        if (autoRenewUid) {
            selectMember(autoRenewUid);
        } else if (searchInput) {
            searchInput.focus();
        }
    });

})();
