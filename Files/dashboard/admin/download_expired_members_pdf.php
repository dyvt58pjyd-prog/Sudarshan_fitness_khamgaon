<?php
/**
 * Sudarshan Fitness Khamgaon - Expired Members List PDF Generator
 * 
 * Generates an executive-level, audit-ready PDF report of all members
 * whose membership has expired and has not been renewed.
 * 
 * Features:
 * - Direct PDF download or in-browser preview (?view=1)
 * - Complete member information (ID, Name, Mobile, Email, Plan, Start Date, Expiry Date, Exact Expired Period)
 * - KPI Summary Analytics Header (Total Expired, 0-30 Days, 31-90 Days, >90 Days, Potential Renewal Value)
 * - Landscape A4 format with repeated table headers across pages
 * - UTF-8 / Special character sanitization for clean FPDF rendering
 */

// Start output buffering to prevent any stray whitespace or warnings from corrupting the PDF stream
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

require_once __DIR__ . '/../../include/db_conn.php';
require_once __DIR__ . '/../../include/fpdf.php';

// Authentication & Role Protection
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception', 'auditor'])) {
    ob_end_clean();
    die("Access Denied: Only gym administrators can generate the Expired Members PDF report.");
}

// Set Indian Standard Time
date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');
$now_formatted = date('d-M-Y h:i A');

// Working session year
$working_year = isset($_SESSION['working_year']) ? intval($_SESSION['working_year']) : intval(date('Y'));

// Filter parameter: 'all', 'recent' (<=30d), 'month1_3' (31-90d), 'older' (>90d)
$filter = isset($_GET['filter']) ? strtolower(trim($_GET['filter'])) : 'all';
$preview_mode = (isset($_GET['view']) && $_GET['view'] == '1') || (isset($_GET['preview']) && $_GET['preview'] == '1');

// Fetch Gym Branding Details
$gym = get_gym_details($con);
$gym_name    = !empty($gym['gym_name']) ? $gym['gym_name'] : 'SUDARSHAN FITNESS';
$gym_address = !empty($gym['gym_address']) ? $gym['gym_address'] : 'Station Road, Khamgaon';
$gym_contact = !empty($gym['gym_contact']) ? $gym['gym_contact'] : '9325205075';
$gym_email   = !empty($gym['gym_email']) ? $gym['gym_email'] : 'sudarshan.fitness.khm@gmail.com';

// Logo Path Resolution
$raw_logo = isset($gym['gym_logo']) ? $gym['gym_logo'] : '../../images/logo.png';
$logo_path = '';
if (strpos($raw_logo, '/Sudarshan Data Folder/') === 0) {
    $logo_path = dirname(dirname(__DIR__)) . $raw_logo;
} else {
    $clean_rel = str_replace('../../', '', $raw_logo);
    $logo_path = dirname(dirname(__DIR__)) . '/' . $clean_rel;
}
if (!file_exists($logo_path)) {
    $alt_logo = dirname(dirname(__DIR__)) . '/images/logo.png';
    $alt_jpg  = dirname(dirname(__DIR__)) . '/images/logo.jpg';
    if (file_exists($alt_logo)) {
        $logo_path = $alt_logo;
    } elseif (file_exists($alt_jpg)) {
        $logo_path = $alt_jpg;
    } else {
        $logo_path = '';
    }
}

// -------------------------------------------------------------
// Database Query: Retrieve Expired & Unrenewed Members
// -------------------------------------------------------------
// Logic:
// 1. Identify each member's latest enrollment record (MAX(et_id))
// 2. Filter for those where latest expire date < today
// 3. Ensure NO ongoing active enrollment exists (expire >= today)
// -------------------------------------------------------------
$query = "SELECT 
            u.userid,
            u.username,
            u.mobile,
            u.email,
            u.gender,
            u.joining_date,
            latest_e.et_id,
            latest_e.pid,
            latest_e.paid_date,
            latest_e.start_date,
            latest_e.expire,
            latest_e.paid_amount,
            latest_e.renewal,
            latest_e.payment_mode,
            p.planName,
            p.validity,
            p.amount AS plan_price
          FROM users u
          INNER JOIN (
              SELECT e1.*
              FROM enrolls_to e1
              INNER JOIN (
                  SELECT uid, MAX(et_id) AS max_et_id
                  FROM enrolls_to
                  GROUP BY uid
              ) e2 ON e1.et_id = e2.max_et_id
          ) latest_e ON u.userid = latest_e.uid
          LEFT JOIN plan p ON latest_e.pid = p.pid
          WHERE u.userid != 'PENDING'
            AND LOWER(u.userid) != 'membership id'
            AND LOWER(u.username) != 'full name'
            AND latest_e.expire < '$today'
            AND NOT EXISTS (
                SELECT 1 FROM enrolls_to e_active 
                WHERE e_active.uid = u.userid AND e_active.expire >= '$today'
            )
          ORDER BY latest_e.expire DESC, CAST(u.userid AS UNSIGNED) DESC";

$result = mysqli_query($con, $query);

$all_expired_members = [];
$total_revenue_opportunity = 0;
$count_0_30 = 0;
$count_31_90 = 0;
$count_over_90 = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $expire_date = $row['expire'];
        $days_lapsed = (int)floor((strtotime($today) - strtotime($expire_date)) / 86400);
        if ($days_lapsed < 0) $days_lapsed = 0;

        $plan_value = intval($row['plan_price'] ?? 0);
        if ($plan_value <= 0) {
            $plan_value = intval($row['paid_amount'] ?? 0);
        }
        $total_revenue_opportunity += $plan_value;

        // Categorize by age of expiration
        if ($days_lapsed <= 30) {
            $count_0_30++;
        } elseif ($days_lapsed <= 90) {
            $count_31_90++;
        } else {
            $count_over_90++;
        }

        // Apply URL filter if selected
        if ($filter === 'recent' && $days_lapsed > 30) {
            continue;
        } elseif ($filter === 'month1_3' && ($days_lapsed <= 30 || $days_lapsed > 90)) {
            continue;
        } elseif ($filter === 'older' && $days_lapsed <= 90) {
            continue;
        }

        // Calculate friendly expiry period string
        if ($days_lapsed === 0) {
            $period_str = "Expired Today";
        } elseif ($days_lapsed === 1) {
            $period_str = "1 Day Ago";
        } elseif ($days_lapsed < 30) {
            $period_str = $days_lapsed . " Days Ago";
        } elseif ($days_lapsed < 365) {
            $m = (int)floor($days_lapsed / 30);
            $d = $days_lapsed % 30;
            $period_str = $m . " Mo " . ($d > 0 ? $d . "d" : "") . " Ago";
        } else {
            $y = (int)floor($days_lapsed / 365);
            $rem_m = (int)floor(($days_lapsed % 365) / 30);
            $period_str = $y . " Yr " . ($rem_m > 0 ? $rem_m . "mo" : "") . " Ago";
        }

        $row['days_lapsed'] = $days_lapsed;
        $row['period_str'] = $period_str;
        $row['plan_value'] = $plan_value;

        $all_expired_members[] = $row;
    }
}

$total_expired_count = count($all_expired_members);

// Helper for FPDF string encoding & truncation
function clean_pdf_text($str, $max_len = 0) {
    if ($str === null || $str === '') return '';
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    // Convert UTF-8 to Windows-1252/ISO-8859-1 for core FPDF fonts
    if (function_exists('iconv')) {
        $clean = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $str);
        if ($clean !== false) $str = $clean;
    } else {
        $str = utf8_decode($str);
    }
    $str = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $str); // Remove non-printable chars
    if ($max_len > 0 && strlen($str) > $max_len) {
        $str = substr($str, 0, $max_len - 3) . '...';
    }
    return $str;
}

// -------------------------------------------------------------
// Custom FPDF Class for Landscape Report
// -------------------------------------------------------------
class ExpiredMembersPDF extends FPDF
{
    public $gym_name;
    public $gym_address;
    public $gym_contact;
    public $gym_email;
    public $logo_path;
    public $generated_at;
    public $filter_label;
    public $total_records;

    function Header()
    {
        // 1. Top Accent Stripe (Alert Red)
        $this->SetFillColor(239, 68, 68); // #EF4444 (Alert Red)
        $this->Rect(12, 10, 273, 2.5, 'F');

        // 2. Logo & Gym Branding
        $brand_x = 12;
        if (!empty($this->logo_path) && file_exists($this->logo_path)) {
            $this->Image($this->logo_path, 12, 14, 20);
            $brand_x = 35;
        }

        // Gym Name & Report Heading
        $this->SetXY($brand_x, 15);
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(30, 41, 59); // Slate Dark #1E293B
        $this->Cell(150, 6, clean_pdf_text($this->gym_name), 0, 1, 'L');

        // Subtitle with alert tag
        $this->SetX($brand_x);
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetTextColor(220, 38, 38); // Red Alert #DC2626
        $this->Cell(150, 4.5, clean_pdf_text('AUDIT REPORT: EXPIRED & UNRENEWED MEMBERS DIRECTORY (' . strtoupper($this->filter_label) . ')'), 0, 1, 'L');

        // Gym Contact Details Line
        $this->SetX($brand_x);
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(100, 116, 139); // Muted Slate #64748B
        $contact_line = clean_pdf_text($this->gym_address . ' | Contact: ' . $this->gym_contact . ' | Email: ' . $this->gym_email);
        $this->Cell(150, 4, $contact_line, 0, 1, 'L');

        // 3. Right Header Metadata Block
        $this->SetXY(200, 14);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(220, 38, 38);
        $this->Cell(85, 5, 'EXPIRED & UNRENEWED', 0, 1, 'R');

        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(100, 116, 139);
        $this->SetX(200);
        $this->Cell(85, 3.8, 'Generated: ' . clean_pdf_text($this->generated_at), 0, 1, 'R');
        $this->SetX(200);
        $this->Cell(85, 3.8, 'Report ID: EXP-REP-' . date('Ymd-His'), 0, 1, 'R');
        $this->SetX(200);
        $this->Cell(85, 3.8, 'Total Expired Listed: ' . intval($this->total_records) . ' Members', 0, 1, 'R');

        // 4. Subtle Separator Line
        $this->SetDrawColor(226, 232, 240); // #E2E8F0
        $this->SetLineWidth(0.4);
        $this->Line(12, 33, 285, 33);
        $this->SetLineWidth(0.2); // reset

        // 5. Repeated Table Column Headers (Rendered on Page 2 and beyond automatically)
        if ($this->PageNo() > 1) {
            $this->SetY(36);
            $this->RenderTableHeader();
        }
    }

    function RenderTableHeader()
    {
        $this->SetFillColor(30, 41, 59); // Dark Slate #1E293B
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->SetDrawColor(51, 65, 85);
        $this->SetLineWidth(0.2);

        // Column widths totaling exactly 273mm (Landscape A4 usable width: 297mm - 24mm margins = 273mm)
        $this->Cell(10, 7.5, '#', 1, 0, 'C', true);
        $this->Cell(22, 7.5, 'Member ID', 1, 0, 'C', true);
        $this->Cell(46, 7.5, '  Member Name', 1, 0, 'L', true);
        $this->Cell(28, 7.5, 'Mobile No.', 1, 0, 'C', true);
        $this->Cell(44, 7.5, '  Email Address', 1, 0, 'L', true);
        $this->Cell(38, 7.5, '  Last Plan Subscribed', 1, 0, 'L', true);
        $this->Cell(22, 7.5, 'Start Date', 1, 0, 'C', true);
        $this->Cell(24, 7.5, 'Expiry Date', 1, 0, 'C', true);
        $this->Cell(26, 7.5, 'Expired Period', 1, 0, 'C', true);
        $this->Cell(13, 7.5, 'Status', 1, 1, 'C', true);
    }

    function Footer()
    {
        $this->SetY(-13);
        $this->SetDrawColor(226, 232, 240);
        $this->Line(12, $this->GetY(), 285, $this->GetY());

        $this->SetFont('Arial', '', 7.2);
        $this->SetTextColor(148, 163, 184); // #94A3B8

        // Left note
        $this->Cell(120, 8, clean_pdf_text('Sudarshan Fitness Management System | Confidential Official Audit Report'), 0, 0, 'L');
        
        // Center note
        $this->Cell(80, 8, clean_pdf_text('https://sudarshanfitness.de'), 0, 0, 'C');

        // Right page number
        $this->Cell(73, 8, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R');
    }
}

// -------------------------------------------------------------
// Initialize PDF Document
// -------------------------------------------------------------
$pdf = new ExpiredMembersPDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 15);

// Pass header metadata
$pdf->gym_name     = $gym_name;
$pdf->gym_address  = $gym_address;
$pdf->gym_contact  = $gym_contact;
$pdf->gym_email    = $gym_email;
$pdf->logo_path    = $logo_path;
$pdf->generated_at = $now_formatted;
$pdf->filter_label = ($filter === 'all') ? 'All-Time Records' : (($filter === 'recent') ? 'Expired <= 30 Days' : (($filter === 'month1_3') ? 'Expired 31-90 Days' : 'Expired > 90 Days'));
$pdf->total_records = $total_expired_count;

$pdf->AddPage();

// -------------------------------------------------------------
// Page 1 KPI Analytics Summary Block
// -------------------------------------------------------------
$pdf->SetY(36);

// Card 1: Total Expired
$pdf->SetFillColor(254, 242, 242); // Soft Red #FEF2F2
$pdf->SetDrawColor(252, 165, 165); // Red border
$pdf->Rect(12, 36, 50, 17, 'DF');
$pdf->SetXY(12, 37.5);
$pdf->SetFont('Arial', 'B', 6.8);
$pdf->SetTextColor(220, 38, 38);
$pdf->Cell(50, 3.5, 'TOTAL EXPIRED MEMBERS', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(185, 28, 28);
$pdf->Cell(50, 7.5, strval($total_expired_count), 0, 1, 'C');
$pdf->SetFont('Arial', '', 6.5);
$pdf->SetTextColor(153, 27, 27);
$pdf->Cell(50, 3, 'Awaiting renewal update', 0, 0, 'C');

// Card 2: 0-30 Days (Hot Leads)
$pdf->SetFillColor(255, 247, 237); // Soft Orange #FFF7ED
$pdf->SetDrawColor(253, 186, 116);
$pdf->Rect(66, 36, 50, 17, 'DF');
$pdf->SetXY(66, 37.5);
$pdf->SetFont('Arial', 'B', 6.8);
$pdf->SetTextColor(194, 65, 12);
$pdf->Cell(50, 3.5, 'EXPIRED <= 30 DAYS', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(194, 65, 12);
$pdf->Cell(50, 7.5, strval($count_0_30), 0, 1, 'C');
$pdf->SetFont('Arial', '', 6.5);
$pdf->SetTextColor(154, 52, 18);
$pdf->Cell(50, 3, 'High recovery potential', 0, 0, 'C');

// Card 3: 31-90 Days (Medium Risk)
$pdf->SetFillColor(254, 252, 232); // Soft Yellow #FEFCE8
$pdf->SetDrawColor(253, 224, 71);
$pdf->Rect(120, 36, 50, 17, 'DF');
$pdf->SetXY(120, 37.5);
$pdf->SetFont('Arial', 'B', 6.8);
$pdf->SetTextColor(161, 98, 7);
$pdf->Cell(50, 3.5, 'EXPIRED 31 - 90 DAYS', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(161, 98, 7);
$pdf->Cell(50, 7.5, strval($count_31_90), 0, 1, 'C');
$pdf->SetFont('Arial', '', 6.5);
$pdf->SetTextColor(133, 77, 14);
$pdf->Cell(50, 3, 'Follow-up campaign target', 0, 0, 'C');

// Card 4: > 90 Days (Cold Churn)
$pdf->SetFillColor(241, 245, 249); // Soft Slate #F1F5F9
$pdf->SetDrawColor(203, 213, 225);
$pdf->Rect(174, 36, 50, 17, 'DF');
$pdf->SetXY(174, 37.5);
$pdf->SetFont('Arial', 'B', 6.8);
$pdf->SetTextColor(71, 85, 105);
$pdf->Cell(50, 3.5, 'EXPIRED > 90 DAYS', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(51, 65, 85);
$pdf->Cell(50, 7.5, strval($count_over_90), 0, 1, 'C');
$pdf->SetFont('Arial', '', 6.5);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(50, 3, 'Inactive / win-back offer', 0, 0, 'C');

// Card 5: Potential Renewal Revenue Value
$pdf->SetFillColor(240, 253, 244); // Soft Green #F0FDF4
$pdf->SetDrawColor(134, 239, 172);
$pdf->Rect(228, 36, 57, 17, 'DF');
$pdf->SetXY(228, 37.5);
$pdf->SetFont('Arial', 'B', 6.8);
$pdf->SetTextColor(21, 128, 61);
$pdf->Cell(57, 3.5, 'POTENTIAL RENEWAL VALUE', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(22, 101, 52);
$pdf->Cell(57, 7.5, 'Rs. ' . number_format($total_revenue_opportunity), 0, 1, 'C');
$pdf->SetFont('Arial', '', 6.5);
$pdf->SetTextColor(22, 101, 52);
$pdf->Cell(57, 3, 'Estimated pipeline recovery', 0, 0, 'C');

// Table starts below KPI cards
$pdf->SetY(57);
$pdf->RenderTableHeader();

// -------------------------------------------------------------
// Render Table Rows
// -------------------------------------------------------------
if ($total_expired_count === 0) {
    // Empty state callout
    $pdf->SetY(75);
    $pdf->SetFillColor(240, 253, 244);
    $pdf->SetDrawColor(187, 247, 208);
    $pdf->Rect(12, 75, 273, 25, 'DF');
    $pdf->SetXY(12, 82);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(22, 101, 52);
    $pdf->Cell(273, 6, clean_pdf_text('GREAT NEWS: ALL REGISTERED MEMBERS ARE CURRENTLY ACTIVE!'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(71, 85, 105);
    $pdf->Cell(273, 5, clean_pdf_text('No expired and unrenewed members were found matching your current filter criteria.'), 0, 1, 'C');
} else {
    $row_idx = 0;
    foreach ($all_expired_members as $m) {
        $row_idx++;
        
        // Check if page break is needed (leave 20mm margin at bottom)
        if ($pdf->GetY() > 185) {
            $pdf->AddPage();
            $pdf->SetY(36);
            $pdf->RenderTableHeader();
        }

        // Alternating row background
        $is_even = ($row_idx % 2 === 0);
        if ($is_even) {
            $pdf->SetFillColor(248, 250, 252); // #F8FAFC
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

        $pdf->SetDrawColor(241, 245, 249);
        $pdf->SetLineWidth(0.2);

        $row_h = 7.0;

        // 1. Sr No (#)
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(10, $row_h, strval($row_idx), 1, 0, 'C', true);

        // 2. Member ID
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(22, $row_h, clean_pdf_text($m['userid']), 1, 0, 'C', true);

        // 3. Member Name
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(46, $row_h, '  ' . clean_pdf_text($m['username'], 26), 1, 0, 'L', true);

        // 4. Mobile No.
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(51, 65, 85);
        $mobile_display = !empty($m['mobile']) ? clean_pdf_text($m['mobile']) : '-';
        $pdf->Cell(28, $row_h, $mobile_display, 1, 0, 'C', true);

        // 5. Email Address
        $pdf->SetFont('Arial', '', 7.2);
        $pdf->SetTextColor(100, 116, 139);
        $email_display = !empty($m['email']) ? clean_pdf_text($m['email'], 28) : '-';
        $pdf->Cell(44, $row_h, '  ' . $email_display, 1, 0, 'L', true);

        // 6. Last Plan Subscribed
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(30, 41, 59);
        $plan_display = !empty($m['planName']) ? clean_pdf_text($m['planName'], 24) : 'Standard Plan';
        $pdf->Cell(38, $row_h, '  ' . $plan_display, 1, 0, 'L', true);

        // 7. Start Date
        $start_dt = !empty($m['start_date']) ? $m['start_date'] : (!empty($m['paid_date']) ? $m['paid_date'] : (!empty($m['joining_date']) ? $m['joining_date'] : '-'));
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(22, $row_h, clean_pdf_text($start_dt), 1, 0, 'C', true);

        // 8. Expiry Date (Highlighted in Bold Red)
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(24, $row_h, clean_pdf_text($m['expire']), 1, 0, 'C', true);

        // 9. Expired Period / Lapsed Time (e.g. "45 Days Ago")
        $pdf->SetFont('Arial', 'B', 7.5);
        if ($m['days_lapsed'] <= 30) {
            $pdf->SetTextColor(194, 65, 12); // Orange for recent
        } elseif ($m['days_lapsed'] <= 90) {
            $pdf->SetTextColor(161, 98, 7); // Amber for medium
        } else {
            $pdf->SetTextColor(100, 116, 139); // Gray for old
        }
        $pdf->Cell(26, $row_h, clean_pdf_text($m['period_str']), 1, 0, 'C', true);

        // 10. Status Pill ("Unrenewed")
        $pdf->SetFont('Arial', 'B', 6.5);
        $pdf->SetTextColor(185, 28, 28);
        $pdf->Cell(13, $row_h, 'EXPIRED', 1, 1, 'C', true);
    }
}

// -------------------------------------------------------------
// Executive Sign-off Block on Final Page
// -------------------------------------------------------------
if ($pdf->GetY() > 165) {
    $pdf->AddPage();
    $pdf->SetY(36);
}

$sign_y = $pdf->GetY() + 8;
$pdf->SetY($sign_y);
$pdf->SetDrawColor(226, 232, 240);
$pdf->Line(12, $sign_y, 285, $sign_y);

$pdf->SetY($sign_y + 3);
$pdf->SetFont('Arial', '', 7.5);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(160, 4, clean_pdf_text('* This report contains confidential business intelligence regarding lapsed memberships for Sudarshan Fitness Khamgaon.'), 0, 0, 'L');

$pdf->SetFont('Arial', 'I', 7.5);
$pdf->Cell(113, 4, clean_pdf_text('Verified by Super Admin / Gym Management: ______________________'), 0, 1, 'R');

$pdf->SetFont('Arial', '', 7);
$current_staff = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Authorized Administrator';
$pdf->Cell(160, 4, clean_pdf_text('Report generated by: ' . $current_staff . ' on ' . $now_formatted), 0, 0, 'L');
$pdf->Cell(113, 4, clean_pdf_text('Authorized Official Seal & Signature'), 0, 1, 'R');

// -------------------------------------------------------------
// Stream PDF to Browser
// -------------------------------------------------------------
$filename = 'Sudarshan_Fitness_Expired_Members_' . date('Y-m-d') . '.pdf';

// Clean all previous output buffers to guarantee pure binary PDF stream
while (ob_get_level()) {
    ob_end_clean();
}

if ($preview_mode) {
    // Open inline in browser tab
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    $pdf->Output('I', $filename);
} else {
    // Direct download prompt
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $pdf->Output('D', $filename);
}
exit();
