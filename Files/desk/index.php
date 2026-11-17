<?php
require_once __DIR__ . '/auth_check.php';

$gym = get_gym_details($con);
$logo_path = $gym['gym_logo'] ?? '../images/logo.png';
if (substr($logo_path, 0, 6) === '../../') {
    $logo_path = '../' . substr($logo_path, 6);
}

// Fetch all active plans from database
$plans = [];
$q_plans = mysqli_query($con, "SELECT * FROM plan WHERE active = 'yes' ORDER BY amount ASC");
if ($q_plans && mysqli_num_rows($q_plans) > 0) {
    while ($p = mysqli_fetch_assoc($q_plans)) {
        $plans[] = [
            'pid'           => $p['pid'],
            'planName'      => $p['planName'],
            'amount'        => intval($p['amount']),
            'validity'      => $p['validity'],
            'discount_lock' => intval($p['discount_lock'] ?? 0)
        ];
    }
}

// Calculate Next Safe Membership ID
$next_id = 101;
$res_max = mysqli_query($con, "SELECT userid FROM users WHERE userid REGEXP '^[0-9]+$' AND CAST(userid AS UNSIGNED) < 100000000");
if ($res_max && mysqli_num_rows($res_max) > 0) {
    $max_val = 100;
    while ($row_max = mysqli_fetch_assoc($res_max)) {
        $val = intval($row_max['userid']);
        if ($val > $max_val) $max_val = $val;
    }
    $next_id = $max_val + 1;
}

$today_str = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Desk Portal: Registration & Renewal | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>" type="image/jpeg">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="desk.css?v=<?php echo time(); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
        window.DESK_GYM_PLANS = <?php echo json_encode($plans); ?>;
    </script>
</head>
<body>

    <div class="desk-app-wrapper">

        <!-- Header -->
        <header class="desk-header">
            <div class="brand-section">
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="brand-logo">
                <div class="brand-text">
                    <h1>
                        <?php echo htmlspecialchars($gym['gym_name']); ?>
                        <span class="desk-portal-tag">Registration &amp; Renewal Only</span>
                    </h1>
                    <p>⚡ Express Counter Console &bull; <span id="deskLiveClock"><?php echo date('h:i:s A'); ?></span> &bull; <?php echo date('d M Y'); ?></p>
                </div>
            </div>

            <div class="header-actions">
                <div class="staff-pill">
                    <span>👤 <?php echo htmlspecialchars($current_staff_name); ?></span>
                    <span class="staff-role-badge"><?php echo htmlspecialchars($current_staff_role); ?></span>
                </div>

                <button type="button" class="btn-icon" onclick="toggleDeskTheme()" title="Toggle Dark/Light Mode">
                    <span id="themeToggleIcon">☀️</span>
                </button>

                <?php if ($current_staff_role === 'super_admin' || $current_staff_role === 'owner'): ?>
                    <a href="../dashboard/admin/index.php" class="btn-icon" title="Return to Full Admin Suite">
                        <span>⚙️ Admin Hub</span>
                    </a>
                <?php endif; ?>

                <a href="logout.php" class="btn-icon" style="color: #ef4444;" title="Sign Out of Desk">
                    <span>🚪 Logout</span>
                </a>
            </div>
        </header>

        <!-- Top Navigation Tabs -->
        <nav class="desk-tabs-bar">
            <button type="button" class="desk-tab-btn active" id="tab-btn-renewal" onclick="switchDeskTab('renewal')">
                <span style="font-size: 18px;">🔄</span>
                <span>Quick Renewal</span>
            </button>
            <button type="button" class="desk-tab-btn" id="tab-btn-register" onclick="switchDeskTab('register')">
                <span style="font-size: 18px;">➕</span>
                <span>New Registration</span>
            </button>
            <button type="button" class="desk-tab-btn" id="tab-btn-history" onclick="switchDeskTab('history')">
                <span style="font-size: 18px;">📋</span>
                <span>Desk Activity Log</span>
            </button>
        </nav>

        <!-- ======================================================= -->
        <!-- TAB 1: QUICK RENEWAL (Instant Live Search & Direct Renew) -->
        <!-- ======================================================= -->
        <section id="tab-renewal" class="tab-content active">

            <!-- Search Bar Wrapper -->
            <div class="search-wrapper">
                <div class="search-input-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="memberSearch" class="search-input" placeholder="Type member name, phone number, or ID..." autocomplete="off" autofocus>
                    <button type="button" id="searchClearBtn" class="search-clear-btn" title="Clear Search">✕</button>
                </div>
                <!-- Dropdown for autocomplete suggestions -->
                <div id="searchDropdown" class="search-dropdown"></div>
            </div>

            <!-- Welcome / Empty State Placeholder (when no member selected) -->
            <div id="renewalWelcome" class="desk-card" style="text-align: center; padding: 48px 24px;">
                <div style="font-size: 50px; margin-bottom: 14px;">⚡👤</div>
                <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 8px;">Direct Renewal by Name</h2>
                <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 20px auto; font-size: 14px;">
                    Start typing any member's name in the box above. As soon as you type, matching members will appear automatically. Select the person to renew them directly with one click!
                </p>
                <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <span style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; font-size: 12px; color: var(--text-muted);">
                        💡 Instant name search
                    </span>
                    <span style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; font-size: 12px; color: var(--text-muted);">
                        💡 Auto-extends from current expiry
                    </span>
                    <span style="background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; font-size: 12px; color: var(--text-muted);">
                        💡 1-Click WhatsApp &amp; Print Receipt
                    </span>
                </div>
            </div>

            <!-- Selected Member Renewal Console -->
            <div id="renewalConsole" class="renewal-console">
                <div class="renewal-grid">

                    <!-- Left: Member Summary Card -->
                    <div class="member-summary-box">
                        <div class="summary-photo-wrap">
                            <img id="summaryPhoto" src="../images/default_avatar.png" alt="Photo" class="summary-photo">
                        </div>
                        <h2 id="summaryName" class="summary-name">—</h2>
                        <span id="summaryId" class="summary-id">#—</span>

                        <div style="margin-bottom: 12px;">
                            <span id="summaryStatusBadge" class="status-badge status-none">Checking Status</span>
                        </div>

                        <div class="summary-details-list">
                            <div class="summary-detail-row">
                                <span class="label">Mobile Number:</span>
                                <span id="summaryMobile" class="val">—</span>
                            </div>
                            <div class="summary-detail-row">
                                <span class="label">Email Address:</span>
                                <span id="summaryEmail" class="val" style="word-break: break-all;">—</span>
                            </div>
                            <div class="summary-detail-row">
                                <span class="label">Gender:</span>
                                <span id="summaryGender" class="val">—</span>
                            </div>
                            <div class="summary-detail-row">
                                <span class="label">Current Plan:</span>
                                <span id="summaryCurrentPlan" class="val" style="color: var(--primary);">—</span>
                            </div>
                            <div class="summary-detail-row">
                                <span class="label">Membership Expiry:</span>
                                <span id="summaryExpiryDate" class="val" style="font-weight: 700;">—</span>
                            </div>
                        </div>

                        <button type="button" class="summary-change-btn" onclick="resetRenewalSelection()">
                            🔍 Search Another Member (Esc)
                        </button>
                    </div>

                    <!-- Right: Direct Renewal Form -->
                    <div class="renewal-form-box">
                        <h3 class="desk-card-title">
                            <span>⚡</span> Direct Membership Renewal
                        </h3>
                        <p class="desk-card-subtitle">Choose the plan &amp; start date. New expiry date and charges calculate in real-time.</p>

                        <form id="renewalForm">
                            <input type="hidden" name="m_id" id="renewUid" value="">

                            <!-- Select Plan -->
                            <div class="form-group">
                                <label for="renewPlanSelect">Select Renewal Package *</label>
                                <select name="plan" id="renewPlanSelect" class="form-control" required>
                                    <option value="">-- Choose Membership Plan --</option>
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?php echo htmlspecialchars($p['pid']); ?>" 
                                                data-price="<?php echo $p['amount']; ?>" 
                                                data-validity="<?php echo htmlspecialchars($p['validity']); ?>" 
                                                data-name="<?php echo htmlspecialchars($p['planName']); ?>"
                                                data-discount-lock="<?php echo $p['discount_lock']; ?>">
                                            <?php echo htmlspecialchars($p['planName']); ?> — ₹<?php echo number_format($p['amount']); ?> (<?php echo htmlspecialchars($p['validity']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-grid-2">
                                <!-- Effective Start Date -->
                                <div class="form-group">
                                    <label for="renewStartDate">Effective Start Date *</label>
                                    <input type="date" name="start_date" id="renewStartDate" class="form-control" value="<?php echo $today_str; ?>" required>
                                    <div class="date-pill-group">
                                        <button type="button" class="date-pill" onclick="setRenewalStartDate('expiry')">Extend from Expiry</button>
                                        <button type="button" class="date-pill" onclick="setRenewalStartDate('today')">From Today</button>
                                    </div>
                                </div>

                                <!-- Transaction Date -->
                                <div class="form-group">
                                    <label for="renewTxDate">Transaction Date *</label>
                                    <input type="date" name="transaction_date" id="renewTxDate" class="form-control" value="<?php echo $today_str; ?>" required>
                                </div>
                            </div>

                            <!-- Live Expiry Banner -->
                            <div class="live-expiry-banner">
                                <div>
                                    <div class="title">📅 New Membership Expiry</div>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">Calculated automatically based on Start Date + Validity</div>
                                </div>
                                <div id="renewNewExpiryText" class="date-val">—</div>
                            </div>

                            <div class="form-grid-2">
                                <!-- Discount -->
                                <div class="form-group">
                                    <label for="renewDiscount">Desk Discount (₹)</label>
                                    <input type="number" name="discount" id="renewDiscount" class="form-control" value="0" min="0" placeholder="0">
                                    <span id="renewDiscountWarning" style="display: none; color: #ef4444; font-size: 11.5px; margin-top: 4px; font-weight: 600;"></span>
                                </div>

                                <!-- Payment Mode -->
                                <div class="form-group">
                                    <label for="renewPaymentMode">Payment Method *</label>
                                    <select name="payment_mode" id="renewPaymentMode" class="form-control" required>
                                        <option value="Cash" selected>💵 Cash</option>
                                        <option value="UPI">📱 UPI / QR Code</option>
                                        <option value="Card">💳 Card / POS Machine</option>
                                        <option value="Net Banking">🏦 Net Banking / Cheque</option>
                                        <?php if ($current_staff_role === 'super_admin' || $current_staff_role === 'owner'): ?>
                                            <option value="Complimentary">🌟 VIP / Complimentary (₹0)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Amount Paid Now -->
                            <div class="form-group">
                                <label for="renewPaidAmount">Amount Paid Now (₹) *</label>
                                <input type="number" name="paid_amount" id="renewPaidAmount" class="form-control" placeholder="Full amount paid" min="0" step="any" required>
                            </div>

                            <!-- Balance Due Row (shows only if partial payment) -->
                            <div id="balanceDueRow" class="form-group" style="display: none;">
                                <label for="balanceDueDate" style="color: #ef4444;">Balance Due Promise Date *</label>
                                <input type="date" name="balance_due_date" id="balanceDueDate" class="form-control">
                            </div>

                            <!-- Pricing Breakdown Box -->
                            <div class="pricing-breakdown">
                                <div class="price-row">
                                    <span>Plan Catalog Rate:</span>
                                    <span id="renewPlanPriceText">₹0.00</span>
                                </div>
                                <div id="renewDiscountRow" class="price-row" style="display: none; color: #10b981;">
                                    <span>Discount Applied:</span>
                                    <span id="renewDiscountValText">- ₹0.00</span>
                                </div>
                                <div class="price-row total-payable">
                                    <span>Total Payable:</span>
                                    <span id="renewTotalPayableText">₹0.00</span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" id="renewSubmitBtn" class="btn-action-primary">
                                ⚡ Renew Membership Now
                            </button>
                        </form>
                    </div>

                </div>
            </div>

        </section>

        <!-- ======================================================= -->
        <!-- TAB 2: NEW REGISTRATION (Clean 2-Column Admission Form) -->
        <!-- ======================================================= -->
        <section id="tab-register" class="tab-content">
            <div class="desk-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px;">
                    <div>
                        <h2 class="desk-card-title"><span>👤➕</span> New Member Admission</h2>
                        <p class="desk-card-subtitle" style="margin-bottom: 0;">Register new gym member with auto-allocated ID and instant admission receipt.</p>
                    </div>
                    <div>
                        <span style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: var(--success); padding: 6px 14px; border-radius: 10px; font-size: 13px; font-weight: 800; font-family: var(--font-mono);">
                            Next Available ID: #<?php echo $next_id; ?>
                        </span>
                    </div>
                </div>

                <form id="regForm">
                    <div class="reg-container-grid">

                        <!-- Column 1: Personal Details -->
                        <div class="reg-column">
                            <h3>1. Personal Details</h3>

                            <div class="form-group">
                                <label for="reg_name">Full Name *</label>
                                <input type="text" name="u_name" id="reg_name" class="form-control" placeholder="e.g. Rahul Anil Sharma" required>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="reg_mobile">Mobile Number *</label>
                                    <input type="tel" name="mobile" id="reg_mobile" class="form-control" placeholder="10-digit mobile" maxlength="10" required>
                                </div>
                                <div class="form-group">
                                    <label for="reg_gender">Gender *</label>
                                    <select name="gender" id="reg_gender" class="form-control" required>
                                        <option value="Male" selected>Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Transgender">Transgender</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="reg_dob">Date of Birth *</label>
                                    <input type="date" name="dob" id="reg_dob" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="reg_email">Email Address</label>
                                    <input type="email" name="email" id="reg_email" class="form-control" placeholder="Optional (auto-generated if empty)">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reg_street">Street / Locality</label>
                                <input type="text" name="street_name" id="reg_street" class="form-control" placeholder="e.g. Shivaji Nagar, Near Temple" value="Main Road">
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="reg_city">City</label>
                                    <input type="text" name="city" id="reg_city" class="form-control" value="Khamgaon" required>
                                </div>
                                <div class="form-group">
                                    <label for="reg_zipcode">Pincode</label>
                                    <input type="number" name="zipcode" id="reg_zipcode" class="form-control" value="444303" required>
                                </div>
                            </div>

                            <!-- Live Photo Capture / Upload -->
                            <div class="form-group" style="margin-top: 14px;">
                                <label>Member Photo</label>
                                <div class="webcam-container">
                                    <img id="webcam-preview" src="../images/default_avatar.png" alt="Preview" class="webcam-preview">
                                    <video id="webcam-video" autoplay playsinline></video>
                                    <canvas id="webcamCanvas" style="display: none;"></canvas>
                                    <input type="hidden" name="member_photo_base64" id="member_photo_base64" value="">

                                    <div class="webcam-actions">
                                        <button type="button" id="startWebcamBtn" class="btn-sm">📸 Open Camera</button>
                                        <button type="button" id="capturePhotoBtn" class="btn-sm" style="display: none; background: #ff6b00; color: #fff; border: none;">⚡ Capture Snap</button>
                                        <button type="button" id="retakePhotoBtn" class="btn-sm" style="display: none;">🔄 Retake</button>
                                        <label for="filePhotoInput" class="btn-sm" style="cursor: pointer; margin: 0;">📁 Choose File</label>
                                        <input type="file" id="filePhotoInput" accept="image/*" style="display: none;">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Column 2: Membership & Payment Details -->
                        <div class="reg-column">
                            <h3>2. Membership &amp; Payment</h3>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="reg_height">Height (cm)</label>
                                    <input type="number" name="height" id="reg_height" class="form-control" placeholder="e.g. 175" min="50" max="250">
                                </div>
                                <div class="form-group">
                                    <label for="reg_weight">Weight (kg)</label>
                                    <input type="number" name="weight" id="reg_weight" class="form-control" placeholder="e.g. 70" min="20" max="300">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reg_batch">Biometric Shift / Batch *</label>
                                <select name="biometric_batch" id="reg_batch" class="form-control" required>
                                    <option value="1" selected>Batch 1 (General: 6 AM - 11 AM)</option>
                                    <option value="2">Batch 2 (Women Only: 4 PM - 5 PM)</option>
                                    <option value="3">Batch 3 (Evening General: 5 PM - 10 PM)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="reg_plan">Select Membership Package *</label>
                                <select name="plan" id="reg_plan" class="form-control" required>
                                    <option value="">-- Choose Membership Plan --</option>
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?php echo htmlspecialchars($p['pid']); ?>" data-price="<?php echo $p['amount']; ?>">
                                            <?php echo htmlspecialchars($p['planName']); ?> — ₹<?php echo number_format($p['amount']); ?> (<?php echo htmlspecialchars($p['validity']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="reg_jdate">Joining Date *</label>
                                    <input type="date" name="jdate" id="reg_jdate" class="form-control" value="<?php echo $today_str; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="reg_txdate">Transaction Date *</label>
                                    <input type="date" name="transaction_date" id="reg_txdate" class="form-control" value="<?php echo $today_str; ?>" required>
                                </div>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label for="reg_discount">Discount (₹)</label>
                                    <input type="number" name="discount" id="reg_discount" class="form-control" value="0" min="0">
                                </div>
                                <div class="form-group">
                                    <label for="reg_paymode">Payment Method *</label>
                                    <select name="payment_mode" id="reg_paymode" class="form-control" required>
                                        <option value="Cash" selected>💵 Cash</option>
                                        <option value="UPI">📱 UPI / QR Code</option>
                                        <option value="Card">💳 Card / POS Machine</option>
                                        <option value="Net Banking">🏦 Net Banking</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reg_paid">Amount Paid Now (₹) *</label>
                                <input type="number" name="paid_amount" id="reg_paid" class="form-control" placeholder="Leave empty for full payment" min="0" step="any">
                            </div>

                            <!-- Submit Button -->
                            <div style="margin-top: 24px;">
                                <button type="submit" id="regSubmitBtn" class="btn-action-primary">
                                    ➕ Complete Registration &amp; Issue Membership
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- TAB 3: RECENT DESK RECORDS -->
        <!-- ======================================================= -->
        <section id="tab-history" class="tab-content">
            <div class="desk-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px; gap: 12px;">
                    <div>
                        <h2 class="desk-card-title"><span>📋</span> Desk Activity Log</h2>
                        <p class="desk-card-subtitle" style="margin-bottom: 0;">Recent registrations &amp; renewals processed at the front desk.</p>
                    </div>
                    <div>
                        <button type="button" class="btn-sm" onclick="loadRecentDeskHistory()">
                            🔄 Refresh Log
                        </button>
                    </div>
                </div>

                <div class="desk-table-wrapper">
                    <table class="desk-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Member Name</th>
                                <th>ID</th>
                                <th>Plan</th>
                                <th>Paid Amount</th>
                                <th>Expiry Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                    Click "Desk Activity Log" tab to load records.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div>

    <!-- ======================================================= -->
    <!-- SUCCESS CELEBRATION MODAL -->
    <!-- ======================================================= -->
    <div id="celebrationModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-icon">🎉</div>
            <h2 id="modalTitle" class="modal-title">Membership Renewed!</h2>
            <p id="modalSubtitle" class="modal-sub">Payment recorded and membership validity updated successfully.</p>

            <div class="modal-details-box">
                <div class="row-item">
                    <span class="label">Member Name:</span>
                    <span id="modalMemberName" class="val">—</span>
                </div>
                <div class="row-item">
                    <span class="label">Membership ID:</span>
                    <span id="modalMemberId" class="val" style="color: var(--primary);">#—</span>
                </div>
                <div class="row-item">
                    <span class="label">Enrolled Plan:</span>
                    <span id="modalPlanName" class="val">—</span>
                </div>
                <div class="row-item">
                    <span class="label">New Expiration:</span>
                    <span id="modalExpiry" class="val" style="color: #10b981;">—</span>
                </div>
                <div class="row-item">
                    <span class="label">Gate Entry PIN:</span>
                    <span id="modalEntryPin" class="val" style="font-family: var(--font-mono); letter-spacing: 2px;">—</span>
                </div>
                <div class="row-item">
                    <span class="label">Amount Paid:</span>
                    <span id="modalPaidAmount" class="val" style="font-weight: 800;">₹—</span>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" id="modalPrintBtn" class="btn-action-primary" style="height: 48px; font-size: 14px;">
                    🖨️ Print Receipt
                </button>
                <button type="button" id="modalNewBtn" class="btn-sm" style="height: 48px; font-size: 14px; background: rgba(255,255,255,0.08);">
                    🔄 Continue Desk
                </button>
            </div>
        </div>
    </div>

    <!-- PWA Manifest & Service Worker -->
    <script src="desk.js?v=<?php echo time(); ?>"></script>
</body>
</html>
