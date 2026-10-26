<?php
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/fpdf.php';

class PDF_Alpha extends FPDF
{
    protected $extgstates = array();

    function SetAlpha($alpha, $bm='Normal')
    {
        $gs = $this->AddExtGState(array('ca'=>$alpha, 'CA'=>$alpha, 'BM'=>'/'.$bm));
        $this->SetExtGState($gs);
    }

    function AddExtGState($parms)
    {
        $n = count($this->extgstates)+1;
        $this->extgstates[$n]['parms'] = $parms;
        return $n;
    }

    function SetExtGState($gs)
    {
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    function _putextgstates()
    {
        for($i=1;$i<=count($this->extgstates);$i++)
        {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_put('<</Type /ExtGState');
            $parms = $this->extgstates[$i]['parms'];
            $this->_put(sprintf('/ca %.3F', $parms['ca']));
            $this->_put(sprintf('/CA %.3F', $parms['CA']));
            $this->_put('/BM '.$parms['BM']);
            $this-> _put('>>');
            $this->_put('endobj');
        }
    }

    function _putresourcedict()
    {
        parent::_putresourcedict();
        $this->_put('/ExtGState <<');
        foreach($this->extgstates as $k=>$v)
            $this->_put('/GS'.$k.' '.$v['n'].' 0 R');
        $this->_put('>>');
    }

    function _putresources()
    {
        $this->_putextgstates();
        parent::_putresources();
    }
}

if (!function_exists('generate_receipt_pdf_file')) {
    function generate_receipt_pdf_file($con, $uid, $etid = '', $pid = '') {
        $uid = mysqli_real_escape_string($con, $uid);
        
        if (empty($etid) || empty($pid)) {
            $q_et = mysqli_query($con, "SELECT et_id, pid FROM enrolls_to WHERE uid = '$uid' ORDER BY et_id DESC LIMIT 1");
            if ($q_et && mysqli_num_rows($q_et) > 0) {
                $et_row = mysqli_fetch_assoc($q_et);
                $etid = $et_row['et_id'];
                $pid = $et_row['pid'];
            }
        }
        
        if (empty($etid)) {
            return false;
        }
        
        $etid = mysqli_real_escape_string($con, $etid);
        
        $sql = "SELECT * FROM users u 
                INNER JOIN enrolls_to e ON u.userid = e.uid 
                INNER JOIN plan p ON p.pid = e.pid 
                WHERE u.userid = '$uid' AND e.et_id = '$etid'";
        $res = mysqli_query($con, $sql);
        if (!$res || mysqli_num_rows($res) === 0) {
            return false;
        }
        $row = mysqli_fetch_assoc($res);
        
        $gym = get_gym_details($con);
        
        $discount = isset($row['discount_amount']) ? intval($row['discount_amount']) : 0;
        $paid_amount = (isset($row['paid_amount']) && $row['paid_amount'] !== null) ? intval($row['paid_amount']) : intval($row['amount']);
        
        $has_pt = false;
        $pt_row = null;
        $pt_amount = 0;
        
        $total_paid = $paid_amount;
        
        // FPDF initialization
        $pdf = new PDF_Alpha();
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        
        // Add watermark at 0.05 opacity (very subtle background style)
        $watermark_path = dirname(__DIR__) . '/images/watermark.jpg';
        if (file_exists($watermark_path)) {
            $pdf->SetAlpha(0.05);
            $pdf->Image($watermark_path, 35, 102.5, 140);
            $pdf->SetAlpha(1.0);
        }
        
        // Top border line - Clean thin premium accent line
        $pdf->SetDrawColor(255, 95, 0); // Premium Gym Orange
        $pdf->SetLineWidth(1.5);
        $pdf->Line(15, 15, 195, 15);
        $pdf->SetLineWidth(0.2); // reset
        
        // Header Layout: Left (Logo + Brand), Right (INVOICE / RECEIPT + metadata)
        $raw_logo = $gym['gym_logo'];
        if (strpos($raw_logo, '/Sudarshan Data Folder/') === 0) {
            $logo_path = dirname(dirname(__DIR__)) . $raw_logo; // Go up to public_html and append absolute path
        } else {
            $logo_path = dirname(__DIR__) . '/' . str_replace('../../', '', $raw_logo);
        }

        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, 15, 23, 22);
        }
        
        // Gym Details
        $pdf->SetTextColor(30, 41, 59); // #1E293B (Dark Slate)
        $pdf->SetFont('Helvetica', 'B', 17);
        $pdf->SetXY(42, 23);
        $pdf->Cell(0, 6, $gym['gym_name'], 0, 1);
        
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(255, 95, 0); // Premium Gym Orange
        $pdf->SetX(42);
        
        $plan_start_date = isset($row['paid_date']) ? $row['paid_date'] : date('Y-m-d');
        if ($plan_start_date > date('Y-m-d')) {
            $formatted_start = date('F jS, Y', strtotime($plan_start_date));
            $pdf->Cell(0, 4.5, 'YOUR TRANSFORMATION BEGINS ON ' . strtoupper($formatted_start), 0, 1);
        } else {
            $pdf->Cell(0, 4.5, 'YOUR TRANSFORMATION BEGINS FROM TODAY', 0, 1);
        }
        
        $pdf->SetTextColor(100, 116, 139); // #64748B (Muted Slate)
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetX(42);
        $pdf->Cell(0, 4, $gym['gym_address'], 0, 1);
        $pdf->SetX(42);
        $res_smtp = mysqli_query($con, "SELECT smtp_username FROM smtp_settings WHERE id = 1");
        $admin_email = ($res_smtp && mysqli_num_rows($res_smtp) > 0) ? mysqli_fetch_assoc($res_smtp)['smtp_username'] : 'admin@sudarshanfitness.de';
        $pdf->Cell(0, 4, 'Phone: ' . $gym['gym_contact'] . ' | Email: ' . $gym['gym_email'] . ' | Admin: ' . $admin_email, 0, 1);
        
        // Invoice / Receipt Title and Metadata on Right
        $pdf->SetXY(130, 23);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(65, 6, 'INVOICE / RECEIPT', 0, 1, 'R');
        
        $invoice_no = '#SF-' . date('Y', strtotime($row['paid_date'])) . '-' . str_pad($row['et_id'], 4, '0', STR_PAD_LEFT);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetX(130);
        $pdf->Cell(65, 4.5, 'Tax Invoice: ' . $invoice_no, 0, 1, 'R');
        $pdf->SetX(130);
        $pdf->Cell(65, 4.5, 'Date: ' . date('d M, Y', strtotime($row['paid_date'])), 0, 1, 'R');
        
        // Elegant Pill Badge for "PAID"
        $pdf->SetFillColor(220, 252, 231); // Soft Green
        $pdf->SetDrawColor(134, 239, 172);
        $pdf->SetLineWidth(0.4);
        $pdf->Rect(169, 39, 26, 6.5, 'DF');
        $pdf->SetLineWidth(0.2); // reset
        
        $pdf->SetXY(169, 39.5);
        $pdf->SetTextColor(21, 128, 61); // Deep Green
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->Cell(26, 6, 'PAID & VERIFIED', 0, 1, 'C');
        
        // Horizontal Accent Line Separator
        $pdf->SetDrawColor(226, 232, 240); // #E2E8F0
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, 48, 195, 48);
        $pdf->SetLineWidth(0.2); // reset
        
        // Billing Info Block - Beautiful Two-Column Grid (No ugly border boxes)
        $pdf->SetXY(15, 52);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(15, 52, 180, 32, 'F');
        
        // Accent Left Line for the Info Block
        $pdf->SetDrawColor(255, 95, 0);
        $pdf->SetLineWidth(1.5);
        $pdf->Line(15, 52, 15, 84);
        $pdf->SetLineWidth(0.2); // reset
        
        // Information Grid Headers
        $pdf->SetXY(18, 54);
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(85, 4, 'BILLED TO', 0, 0);
        $pdf->Cell(0, 4, 'PAYMENT & MEMBERSHIP DETAILS', 0, 1);
        
        // Primary values
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetX(18);
        $pdf->Cell(85, 5.5, $row['username'], 0, 0);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(0, 5.5, 'Member ID: ' . $row['userid'], 0, 1);
        
        // Secondary details
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetX(18);
        $pdf->Cell(85, 4.5, 'Phone: ' . $row['mobile'], 0, 0);
        $pdf->Cell(0, 4.5, 'Plan Period: ' . $row['paid_date'] . ' to ' . $row['expire'], 0, 1);
        
        $pdf->SetX(18);
        $pdf->Cell(85, 4.5, 'Email: ' . $row['email'], 0, 0);
        $pdf->Cell(0, 4.5, 'Payment Method: ' . strtoupper($row['payment_mode']), 0, 1);
        
        $pdf->SetX(18);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->SetTextColor(255, 107, 0);
        $pdf->Cell(85, 4.5, 'Biometric PIN: ' . $row['entry_code'], 0, 0);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(0, 4.5, 'Assigned Biometric ID: ' . $row['biometric_id'], 0, 1);
        
        // Table Header
        $pdf->SetXY(15, 89);
        $pdf->SetFillColor(30, 41, 59); // Dark Slate background
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(85, 8, '  Plan Description', 0, 0, 'L', true);
        $pdf->Cell(35, 8, 'Validity', 0, 0, 'L', true);
        $pdf->Cell(30, 8, 'Payment Mode', 0, 0, 'L', true);
        $pdf->Cell(30, 8, 'Amount  ', 0, 1, 'R', true);
        
        // Row 1 Data
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY(15, 99);
        $pdf->Cell(85, 5.5, '  ' . $row['planName'], 0, 0, 'L');
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(35, 5.5, $row['validity'] . ' Month(s)', 0, 0, 'L');
        $pdf->Cell(30, 5.5, strtoupper($row['payment_mode']), 0, 0, 'L');
        $pdf->Cell(30, 5.5, 'Rs. ' . $row['amount'] . '  ', 0, 1, 'R');
        
        // Row 1 Description
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetX(15);
        $desc = !empty($row['description']) ? substr($row['description'], 0, 55) : 'Standard Membership Plan';
        $pdf->Cell(85, 4.5, '  ' . $desc, 0, 0, 'L');
        $pdf->Cell(35, 4.5, 'Expires: ' . $row['expire'], 0, 1, 'L');
        
        // Draw Horizontal Line Separator
        $curr_y = $pdf->GetY() + 3;
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(15, $curr_y, 195, $curr_y);
        $pdf->SetLineWidth(0.2); // reset
        
        // Row 2 Data (PT) if exists (safely check, though false by default)
        if ($has_pt) {
            $curr_y += 2;
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetXY(15, $curr_y);
            $pdf->Cell(85, 5.5, '  Personal Training (PT)', 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 8.5);
            $pdf->Cell(35, 5.5, 'Expires: ' . $pt_row['expire_date'], 0, 0, 'L');
            $pdf->Cell(30, 5.5, strtoupper($pt_row['payment_mode']), 0, 0, 'L');
            $pdf->Cell(30, 5.5, 'Rs. ' . $pt_row['amount'] . '  ', 0, 1, 'R');
            
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('Helvetica', '', 7.5);
            $pdf->SetX(15);
            $pdf->Cell(85, 4.5, '  Trainer: ' . $pt_row['trainer_name'], 0, 1, 'L');
            
            $curr_y = $pdf->GetY() + 2;
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->SetLineWidth(0.4);
            $pdf->Line(15, $curr_y, 195, $curr_y);
            $pdf->SetLineWidth(0.2); // reset
        }
        
        // Total block
        $curr_y += 3;
        $pdf->SetXY(120, $curr_y);
        $plan_amount = intval($row['amount']);
        $is_welcome_bonus = ($discount > 0 && ($plan_amount == 12000 || $plan_amount == 6000));
        
        if ($is_welcome_bonus) {
            $pdf->SetFont('Helvetica', 'B', 8.5);
            $pdf->SetTextColor(255, 95, 0);
            $pdf->Cell(45, 4.5, 'Welcome Bonus Applied:', 0, 0, 'R');
            $pdf->SetTextColor(239, 68, 68);
            $pdf->Cell(30, 4.5, '- Rs. ' . $discount . '  ', 0, 1, 'R');
            $pdf->SetX(120);
        } elseif ($discount > 0) {
            $pdf->SetFont('Helvetica', 'B', 8.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(45, 4.5, 'Discount:', 0, 0, 'R');
            $pdf->SetTextColor(239, 68, 68);
            $pdf->Cell(30, 4.5, '- Rs. ' . $discount . '  ', 0, 1, 'R');
            $pdf->SetX(120);
        }
        
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(45, 6, 'Total Paid:', 0, 0, 'R');
        $pdf->SetTextColor(255, 95, 0); // Premium Orange Accent
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(30, 6, 'Rs. ' . number_format($total_paid) . '  ', 0, 1, 'R');
        
        if (isset($row['balance']) && intval($row['balance']) > 0) {
            $pdf->SetX(90);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetTextColor(220, 38, 38);
            $due_date_str = !empty($row['balance_due_date']) ? date('d M Y', strtotime($row['balance_due_date'])) : 'Pending';
            $pdf->Cell(75, 4.5, 'Balance Due (By ' . $due_date_str . '):', 0, 0, 'R');
            $pdf->Cell(30, 4.5, 'Rs. ' . number_format($row['balance']) . '  ', 0, 1, 'R');
        }
        
        // Quote / Motivation Divider
        $curr_y = $pdf->GetY() + 4;
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(15, $curr_y, 195, $curr_y);
        
        $curr_y += 3;
        $pdf->SetXY(15, $curr_y);
        $pdf->SetFont('Helvetica', 'BI', 9.5);
        $pdf->SetTextColor(255, 95, 0);
        $pdf->Cell(0, 4.5, 'Be strong : be fit : be you', 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 4, 'Lets build a strong tomorrow, together', 0, 1, 'C');
        
        // Received by banner
        $curr_y = $pdf->GetY() + 4;
        $pdf->SetXY(15, $curr_y);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(15, $curr_y, 180, 7, 'F');
        
        // Draw a small left border
        $pdf->SetDrawColor(255, 95, 0);
        $pdf->SetLineWidth(1.2);
        $pdf->Line(15, $curr_y, 15, $curr_y + 7);
        $pdf->SetLineWidth(0.2); // reset
        
        $pdf->SetXY(18, $curr_y + 1);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Write(5, 'Payment Received By: ');
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->SetTextColor(255, 95, 0);
        $received_name = ($row['received_by'] ? $row['received_by'] : 'Super Admin');
        $pdf->Write(5, $received_name);
        
        // Footer Details
        $curr_y = $pdf->GetY() + 10;
        $pdf->SetXY(15, $curr_y);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(110, 3.2, '* Thank you for being a valued member of the Sudarshan Fitness Family!', 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(110, 3.2, 'Access your member portal anytime at: https://sudarshanfitness.de', 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(110, 3.2, 'System Engineered by DRDO, Ministry of Defence (Anurag Bawaskar) | Ph: 8459962390', 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(110, 3.2, 'Please keep this receipt for future reference or plan renewal requests.', 0, 1);
        
        // Signature Block on Right
        $pdf->SetXY(140, $curr_y);
        $pdf->SetFont('Helvetica', 'I', 7);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->Cell(55, 3, '[ SUDARSHAN FITNESS ]', 0, 1, 'C');
        $pdf->SetX(140);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(55, 3, '----------------------------------------', 0, 1, 'C');
        $pdf->SetX(140);
        $pdf->Cell(55, 3, 'Authorized Sign', 0, 1, 'C');
        
        // Terms & Conditions Block
        $curr_y = $pdf->GetY() + 6;
        $pdf->Line(15, $curr_y, 195, $curr_y);
        $pdf->SetXY(15, $curr_y + 2);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 3, 'Terms & Conditions:', 0, 1);
        
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetTextColor(148, 163, 184);
        $terms_text = "1. Equipment Damage Recovery: If any member causes damage to the gym machinery, weights, or equipment, 100% of the replacement or repair cost will be recovered directly from the member.\n2. Health & Liability Waiver: In the event of any physical injury, health issues, or medical emergency during training within the gym premises, the gym management will not be held responsible.\n3. Membership Fees: Gym registration, membership, and personal training fees are strictly non-refundable and non-transferable under any circumstances.\n4. Safety & Decorum: Members must wear clean athletic attire, indoor-only training shoes, and wipe down machines after use. Always return weights to their proper racks.";
        $pdf->MultiCell(180, 2.5, $terms_text);
        
        $uploads_dir = dirname(__DIR__) . '/uploads';
        if (!file_exists($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }
        
        $temp_filename = $uploads_dir . '/Sudarshan_Fitness_Receipt_' . $row['et_id'] . '.pdf';
        $pdf->Output('F', $temp_filename);
        
        return $temp_filename;
    }
}

if (!function_exists('generate_pt_receipt_pdf_file')) {
    function generate_pt_receipt_pdf_file($con, $uid, $pt_id = '') {
        $uid = mysqli_real_escape_string($con, $uid);
        
        if (empty($pt_id)) {
            $q_pt = mysqli_query($con, "SELECT pt_id FROM pt_enrollments WHERE uid = '$uid' ORDER BY pt_id DESC LIMIT 1");
            if ($q_pt && mysqli_num_rows($q_pt) > 0) {
                $pt_row = mysqli_fetch_assoc($q_pt);
                $pt_id = $pt_row['pt_id'];
            }
        }
        
        if (empty($pt_id)) {
            return false;
        }
        
        $pt_id = mysqli_real_escape_string($con, $pt_id);
        
        $sql = "SELECT p.*, u.username, u.mobile, u.email, t.Full_name AS trainer_name 
                FROM pt_enrollments p
                INNER JOIN users u ON p.uid = u.userid
                INNER JOIN admin t ON p.trainer_id = t.username
                WHERE p.uid = '$uid' AND p.pt_id = '$pt_id'";
        $res = mysqli_query($con, $sql);
        if (!$res || mysqli_num_rows($res) === 0) {
            return false;
        }
        $row = mysqli_fetch_assoc($res);
        
        $gym = get_gym_details($con);
        
        // FPDF initialization
        $pdf = new PDF_Alpha();
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        
        // Add watermark at 0.05 opacity (very subtle background style)
        $watermark_path = dirname(__DIR__) . '/images/watermark.jpg';
        if (file_exists($watermark_path)) {
            $pdf->SetAlpha(0.05);
            $pdf->Image($watermark_path, 35, 102.5, 140);
            $pdf->SetAlpha(1.0);
        }
        
        // Top border line - Clean thin premium accent line
        $pdf->SetDrawColor(255, 95, 0); // Premium Gym Orange
        $pdf->SetLineWidth(1.5);
        $pdf->Line(15, 15, 195, 15);
        $pdf->SetLineWidth(0.2); // reset
        
        // Header Layout: Left (Logo + Brand), Right (PT INVOICE + metadata)
        $raw_logo = $gym['gym_logo'];
        if (strpos($raw_logo, '/Sudarshan Data Folder/') === 0) {
            $logo_path = dirname(dirname(__DIR__)) . $raw_logo; // Go up to public_html and append absolute path
        } else {
            $logo_path = dirname(__DIR__) . '/' . str_replace('../../', '', $raw_logo);
        }

        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, 15, 23, 22);
        }
        
        // Gym Details
        $pdf->SetTextColor(30, 41, 59); // #1E293B (Dark Slate)
        $pdf->SetFont('Helvetica', 'B', 17);
        $pdf->SetXY(42, 23);
        $pdf->Cell(0, 6, $gym['gym_name'], 0, 1);
        
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(255, 95, 0); // Premium Gym Orange
        $pdf->SetX(42);
        $pdf->Cell(0, 4.5, 'YOUR TRANSFORMATION BEGINS FROM TODAY', 0, 1);
        
        $pdf->SetTextColor(100, 116, 139); // #64748B (Muted Slate)
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetX(42);
        $pdf->Cell(0, 4, $gym['gym_address'], 0, 1);
        $pdf->SetX(42);
        $res_smtp = mysqli_query($con, "SELECT smtp_username FROM smtp_settings WHERE id = 1");
        $admin_email = ($res_smtp && mysqli_num_rows($res_smtp) > 0) ? mysqli_fetch_assoc($res_smtp)['smtp_username'] : 'admin@sudarshanfitness.de';
        $pdf->Cell(0, 4, 'Phone: ' . $gym['gym_contact'] . ' | Email: ' . $gym['gym_email'] . ' | Admin: ' . $admin_email, 0, 1);
        
        // Invoice / Receipt Title and Metadata on Right
        $pdf->SetXY(130, 23);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(65, 6, 'INVOICE / RECEIPT', 0, 1, 'R');
        
        $pt_receipt_no = '#PT-' . date('Y', strtotime($row['enroll_date'])) . '-' . str_pad($row['pt_id'], 4, '0', STR_PAD_LEFT);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetX(130);
        $pdf->Cell(65, 4.5, 'PT Receipt: ' . $pt_receipt_no, 0, 1, 'R');
        $pdf->SetX(130);
        $pdf->Cell(65, 4.5, 'Date: ' . date('d M, Y', strtotime($row['enroll_date'])), 0, 1, 'R');
        
        // Elegant Pill Badge for "PAID"
        $pdf->SetFillColor(220, 252, 231); // Soft Green
        $pdf->SetDrawColor(134, 239, 172);
        $pdf->SetLineWidth(0.4);
        $pdf->Rect(169, 39, 26, 6.5, 'DF');
        $pdf->SetLineWidth(0.2); // reset
        
        $pdf->SetXY(169, 39.5);
        $pdf->SetTextColor(21, 128, 61); // Deep Green
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->Cell(26, 6, 'PAID & VERIFIED', 0, 1, 'C');
        
        // Horizontal Accent Line Separator
        $pdf->SetDrawColor(226, 232, 240); // #E2E8F0
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, 48, 195, 48);
        $pdf->SetLineWidth(0.2); // reset
        
        // Billing Info Block - Beautiful Two-Column Grid (No ugly border boxes)
        $pdf->SetXY(15, 52);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(15, 52, 180, 27, 'F');
        
        // Accent Left Line for the Info Block
        $pdf->SetDrawColor(255, 95, 0);
        $pdf->SetLineWidth(1.5);
        $pdf->Line(15, 52, 15, 79);
        $pdf->SetLineWidth(0.2); // reset
        
        // Information Grid Headers
        $pdf->SetXY(18, 54);
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(85, 4, 'BILLED TO', 0, 0);
        $pdf->Cell(0, 4, 'PAYMENT & TRAINING DETAILS', 0, 1);
        
        // Primary values
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetX(18);
        $pdf->Cell(85, 5.5, $row['username'], 0, 0);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(0, 5.5, 'Member ID: ' . $row['uid'], 0, 1);
        
        // Secondary details
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetX(18);
        $pdf->Cell(85, 4.5, 'Phone: ' . $row['mobile'], 0, 0);
        $pdf->Cell(0, 4.5, 'PT Period: ' . $row['enroll_date'] . ' to ' . $row['expire_date'], 0, 1);
        
        $pdf->SetX(18);
        $pdf->Cell(85, 4.5, 'Email: ' . $row['email'], 0, 0);
        $pdf->Cell(0, 4.5, 'Payment Method: ' . strtoupper($row['payment_mode']), 0, 1);
        
        // Table Header
        $pdf->SetXY(15, 84);
        $pdf->SetFillColor(30, 41, 59); // Dark Slate background
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(85, 8, '  Training Service Description', 0, 0, 'L', true);
        $pdf->Cell(35, 8, 'Validity', 0, 0, 'L', true);
        $pdf->Cell(30, 8, 'Payment Mode', 0, 0, 'L', true);
        $pdf->Cell(30, 8, 'Amount  ', 0, 1, 'R', true);
        
        // Row 1 Data (PT)
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY(15, 94);
        $pdf->Cell(85, 5.5, '  Personal Training (PT)', 0, 0, 'L');
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(35, 5.5, 'Expires: ' . $row['expire_date'], 0, 0, 'L');
        $pdf->Cell(30, 5.5, strtoupper($row['payment_mode']), 0, 0, 'L');
        $pdf->Cell(30, 5.5, 'Rs. ' . $row['amount'] . '  ', 0, 1, 'R');
        
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetX(15);
        $pdf->Cell(85, 4.5, '  Trainer: ' . $row['trainer_name'], 0, 1, 'L');
        
        $curr_y = $pdf->GetY() + 3;
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(15, $curr_y, 195, $curr_y);
        $pdf->SetLineWidth(0.2); // reset
        
        // Total block
        $curr_y += 3;
        $pdf->SetXY(120, $curr_y);
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(45, 6, 'Total Paid:', 0, 0, 'R');
        $pdf->SetTextColor(255, 95, 0); // Premium Orange Accent
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(30, 6, 'Rs. ' . $row['amount'] . '  ', 0, 1, 'R');
        
        // Quote / Motivation Divider
        $curr_y = $pdf->GetY() + 4;
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(15, $curr_y, 195, $curr_y);
        
        $curr_y += 3;
        $pdf->SetXY(15, $curr_y);
        $pdf->SetFont('Helvetica', 'BI', 9.5);
        $pdf->SetTextColor(255, 95, 0);
        $pdf->Cell(0, 4.5, 'Be strong : be fit : be you', 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 4, 'Lets build a strong tomorrow, together', 0, 1, 'C');
        
        // Received by banner
        $curr_y = $pdf->GetY() + 4;
        $pdf->SetXY(15, $curr_y);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(15, $curr_y, 180, 7, 'F');
        
        // Draw a small left border
        $pdf->SetDrawColor(255, 95, 0);
        $pdf->SetLineWidth(1.2);
        $pdf->Line(15, $curr_y, 15, $curr_y + 7);
        $pdf->SetLineWidth(0.2); // reset
        
        $pdf->SetXY(18, $curr_y + 1);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Write(5, 'Payment Received By: ');
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->SetTextColor(255, 95, 0);
        $received_name = ($row['received_by'] ? $row['received_by'] : 'Super Admin');
        $pdf->Write(5, $received_name);
        
        // Footer Details
        $curr_y = $pdf->GetY() + 10;
        $pdf->SetXY(15, $curr_y);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(110, 3.2, '* Thank you for being a valued member of the Sudarshan Fitness Family!', 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(110, 3.2, 'Access your member portal anytime at: https://sudarshanfitness.de', 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(110, 3.2, 'System Engineered by DRDO, Ministry of Defence (Anurag Bawaskar) | Ph: 8459962390', 0, 1);
        $pdf->SetX(15);
        $pdf->Cell(110, 3.2, 'Please keep this receipt for future reference or plan renewal requests.', 0, 1);
        
        // Signature Block on Right
        $pdf->SetXY(140, $curr_y);
        $pdf->SetFont('Helvetica', 'I', 7);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->Cell(55, 3, '[ SUDARSHAN FITNESS ]', 0, 1, 'C');
        $pdf->SetX(140);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(55, 3, '----------------------------------------', 0, 1, 'C');
        $pdf->SetX(140);
        $pdf->Cell(55, 3, 'Authorized Sign', 0, 1, 'C');
        
        // Terms & Conditions Block
        $curr_y = $pdf->GetY() + 6;
        $pdf->Line(15, $curr_y, 195, $curr_y);
        $pdf->SetXY(15, $curr_y + 2);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 3, 'Terms & Conditions:', 0, 1);
        
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetTextColor(148, 163, 184);
        $terms_text = "1. Equipment Damage Recovery: If any member causes damage to the gym machinery, weights, or equipment, 100% of the replacement or repair cost will be recovered directly from the member.\n2. Health & Liability Waiver: In the event of any physical injury, health issues, or medical emergency during training within the gym premises, the gym management will not be held responsible.\n3. Membership Fees: Gym registration, membership, and personal training fees are strictly non-refundable and non-transferable under any circumstances.\n4. Safety & Decorum: Members must wear clean athletic attire, indoor-only training shoes, and wipe down machines after use. Always return weights to their proper racks.";
        $pdf->MultiCell(180, 2.5, $terms_text);
        
        $uploads_dir = dirname(__DIR__) . '/uploads';
        if (!file_exists($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }
        
        $temp_filename = $uploads_dir . '/Sudarshan_Fitness_Receipt_PT_' . $row['pt_id'] . '.pdf';
        $pdf->Output('F', $temp_filename);
        
        return $temp_filename;
    }
}

if (!function_exists('generate_expired_members_pdf_file')) {
    function generate_expired_members_pdf_file($con, $filter = 'all', $output_path = '') {
        $today = date('Y-m-d');
        $now_formatted = date('d-M-Y h:i A');
        $gym = get_gym_details($con);
        
        $gym_name    = !empty($gym['gym_name']) ? $gym['gym_name'] : 'SUDARSHAN FITNESS';
        $gym_address = !empty($gym['gym_address']) ? $gym['gym_address'] : 'Station Road, Khamgaon';
        $gym_contact = !empty($gym['gym_contact']) ? $gym['gym_contact'] : '9325205075';
        $gym_email   = !empty($gym['gym_email']) ? $gym['gym_email'] : 'sudarshan.fitness.khm@gmail.com';
        
        $raw_logo = isset($gym['gym_logo']) ? $gym['gym_logo'] : '../../images/logo.png';
        $logo_path = '';
        if (strpos($raw_logo, '/Sudarshan Data Folder/') === 0) {
            $logo_path = dirname(dirname(__DIR__)) . $raw_logo;
        } else {
            $clean_rel = str_replace('../../', '', $raw_logo);
            $logo_path = dirname(__DIR__) . '/' . $clean_rel;
        }
        if (!file_exists($logo_path)) {
            $alt_logo = dirname(__DIR__) . '/images/logo.png';
            $alt_jpg  = dirname(__DIR__) . '/images/logo.jpg';
            if (file_exists($alt_logo)) $logo_path = $alt_logo;
            elseif (file_exists($alt_jpg)) $logo_path = $alt_jpg;
        }
        
        $clean_fn = function($str, $max_len = 0) {
            if ($str === null || $str === '') return '';
            $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
            if (function_exists('iconv')) {
                $clean = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $str);
                if ($clean !== false) $str = $clean;
            } else {
                $str = utf8_decode($str);
            }
            $str = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $str);
            if ($max_len > 0 && strlen($str) > $max_len) {
                $str = substr($str, 0, $max_len - 3) . '...';
            }
            return $str;
        };

        $query = "SELECT 
                    u.userid, u.username, u.mobile, u.email, u.gender, u.joining_date,
                    latest_e.expire, latest_e.paid_date, latest_e.start_date, latest_e.paid_amount,
                    p.planName, p.validity, p.amount AS plan_price
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
        $members = [];
        $total_value = 0;
        $cnt_30 = 0; $cnt_90 = 0; $cnt_over90 = 0;

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $days = (int)floor((strtotime($today) - strtotime($row['expire'])) / 86400);
                if ($days < 0) $days = 0;

                $pval = intval($row['plan_price'] ?? ($row['paid_amount'] ?? 0));
                $total_value += $pval;

                if ($days <= 30) $cnt_30++;
                elseif ($days <= 90) $cnt_90++;
                else $cnt_over90++;

                if ($filter === 'recent' && $days > 30) continue;
                if ($filter === 'month1_3' && ($days <= 30 || $days > 90)) continue;
                if ($filter === 'older' && $days <= 90) continue;

                if ($days === 0) $period = "Today";
                elseif ($days === 1) $period = "1 Day Ago";
                elseif ($days < 30) $period = $days . " Days Ago";
                elseif ($days < 365) $period = floor($days/30) . " Mo " . ($days%30) . "d Ago";
                else $period = floor($days/365) . " Yr Ago";

                $row['days_lapsed'] = $days;
                $row['period_str'] = $period;
                $members[] = $row;
            }
        }

        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Header
        $pdf->SetFillColor(239, 68, 68);
        $pdf->Rect(12, 10, 273, 2.5, 'F');

        $bx = 12;
        if (!empty($logo_path) && file_exists($logo_path)) {
            $pdf->Image($logo_path, 12, 14, 20);
            $bx = 35;
        }

        $pdf->SetXY($bx, 15);
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(150, 6, $clean_fn($gym_name), 0, 1, 'L');

        $pdf->SetX($bx);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(150, 4.5, 'AUDIT REPORT: EXPIRED & UNRENEWED MEMBERS DIRECTORY', 0, 1, 'L');

        $pdf->SetX($bx);
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(150, 4, $clean_fn($gym_address . ' | Contact: ' . $gym_contact . ' | Email: ' . $gym_email), 0, 1, 'L');

        $pdf->SetXY(200, 14);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(85, 5, 'EXPIRED & UNRENEWED', 0, 1, 'R');
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetX(200);
        $pdf->Cell(85, 3.8, 'Generated: ' . $clean_fn($now_formatted), 0, 1, 'R');
        $pdf->SetX(200);
        $pdf->Cell(85, 3.8, 'Total Expired: ' . count($members) . ' Members', 0, 1, 'R');

        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(12, 33, 285, 33);

        // KPI Box
        $pdf->SetY(36);
        $pdf->SetFillColor(254, 242, 242); $pdf->SetDrawColor(252, 165, 165);
        $pdf->Rect(12, 36, 60, 15, 'DF');
        $pdf->SetXY(12, 37.5); $pdf->SetFont('Arial', 'B', 7); $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(60, 3.5, 'TOTAL EXPIRED MEMBERS', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 13); $pdf->Cell(60, 6, strval(count($members)), 0, 1, 'C');

        $pdf->SetFillColor(240, 253, 244); $pdf->SetDrawColor(134, 239, 172);
        $pdf->Rect(76, 36, 65, 15, 'DF');
        $pdf->SetXY(76, 37.5); $pdf->SetFont('Arial', 'B', 7); $pdf->SetTextColor(22, 101, 52);
        $pdf->Cell(65, 3.5, 'POTENTIAL RENEWAL VALUE', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 13); $pdf->Cell(65, 6, 'Rs. ' . number_format($total_value), 0, 1, 'C');

        $pdf->SetFillColor(255, 247, 237); $pdf->SetDrawColor(253, 186, 116);
        $pdf->Rect(145, 36, 45, 15, 'DF');
        $pdf->SetXY(145, 37.5); $pdf->SetFont('Arial', 'B', 7); $pdf->SetTextColor(194, 65, 12);
        $pdf->Cell(45, 3.5, '<= 30 DAYS AGO', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 13); $pdf->Cell(45, 6, strval($cnt_30), 0, 1, 'C');

        $pdf->SetFillColor(254, 252, 232); $pdf->SetDrawColor(253, 224, 71);
        $pdf->Rect(194, 36, 45, 15, 'DF');
        $pdf->SetXY(194, 37.5); $pdf->SetFont('Arial', 'B', 7); $pdf->SetTextColor(161, 98, 7);
        $pdf->Cell(45, 3.5, '31-90 DAYS AGO', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 13); $pdf->Cell(45, 6, strval($cnt_90), 0, 1, 'C');

        $pdf->SetFillColor(241, 245, 249); $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect(243, 36, 42, 15, 'DF');
        $pdf->SetXY(243, 37.5); $pdf->SetFont('Arial', 'B', 7); $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(42, 3.5, '> 90 DAYS AGO', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 13); $pdf->Cell(42, 6, strval($cnt_over90), 0, 1, 'C');

        // Table Header function
        $draw_th = function() use ($pdf) {
            $pdf->SetFillColor(30, 41, 59); $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 8); $pdf->SetDrawColor(51, 65, 85);
            $pdf->Cell(10, 7.5, '#', 1, 0, 'C', true);
            $pdf->Cell(22, 7.5, 'Member ID', 1, 0, 'C', true);
            $pdf->Cell(46, 7.5, '  Member Name', 1, 0, 'L', true);
            $pdf->Cell(28, 7.5, 'Mobile No.', 1, 0, 'C', true);
            $pdf->Cell(44, 7.5, '  Email Address', 1, 0, 'L', true);
            $pdf->Cell(38, 7.5, '  Last Plan', 1, 0, 'L', true);
            $pdf->Cell(22, 7.5, 'Start Date', 1, 0, 'C', true);
            $pdf->Cell(24, 7.5, 'Expiry Date', 1, 0, 'C', true);
            $pdf->Cell(26, 7.5, 'Expired Period', 1, 0, 'C', true);
            $pdf->Cell(13, 7.5, 'Status', 1, 1, 'C', true);
        };

        $pdf->SetY(55);
        $draw_th();

        $idx = 0;
        foreach ($members as $m) {
            $idx++;
            if ($pdf->GetY() > 185) {
                $pdf->AddPage();
                $pdf->SetY(15);
                $draw_th();
            }

            $pdf->SetFillColor(($idx % 2 === 0) ? 248 : 255, ($idx % 2 === 0) ? 250 : 255, ($idx % 2 === 0) ? 252 : 255);
            $pdf->SetDrawColor(241, 245, 249);
            $h = 7.0;

            $pdf->SetFont('Arial', '', 7.5); $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(10, $h, strval($idx), 1, 0, 'C', true);

            $pdf->SetFont('Arial', 'B', 8); $pdf->SetTextColor(30, 41, 59);
            $pdf->Cell(22, $h, $clean_fn($m['userid']), 1, 0, 'C', true);

            $pdf->SetFont('Arial', 'B', 8); $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(46, $h, '  ' . $clean_fn($m['username'], 26), 1, 0, 'L', true);

            $pdf->SetFont('Arial', '', 7.5); $pdf->SetTextColor(51, 65, 85);
            $pdf->Cell(28, $h, $clean_fn(!empty($m['mobile']) ? $m['mobile'] : '-'), 1, 0, 'C', true);

            $pdf->SetFont('Arial', '', 7.2); $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(44, $h, '  ' . $clean_fn(!empty($m['email']) ? $m['email'] : '-', 28), 1, 0, 'L', true);

            $pdf->SetFont('Arial', '', 7.5); $pdf->SetTextColor(30, 41, 59);
            $pdf->Cell(38, $h, '  ' . $clean_fn(!empty($m['planName']) ? $m['planName'] : 'Standard Plan', 24), 1, 0, 'L', true);

            $sdt = !empty($m['start_date']) ? $m['start_date'] : (!empty($m['paid_date']) ? $m['paid_date'] : (!empty($m['joining_date']) ? $m['joining_date'] : '-'));
            $pdf->SetFont('Arial', '', 7.5); $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(22, $h, $clean_fn($sdt), 1, 0, 'C', true);

            $pdf->SetFont('Arial', 'B', 8); $pdf->SetTextColor(220, 38, 38);
            $pdf->Cell(24, $h, $clean_fn($m['expire']), 1, 0, 'C', true);

            $pdf->SetFont('Arial', 'B', 7.5);
            if ($m['days_lapsed'] <= 30) $pdf->SetTextColor(194, 65, 12);
            elseif ($m['days_lapsed'] <= 90) $pdf->SetTextColor(161, 98, 7);
            else $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(26, $h, $clean_fn($m['period_str']), 1, 0, 'C', true);

            $pdf->SetFont('Arial', 'B', 6.5); $pdf->SetTextColor(185, 28, 28);
            $pdf->Cell(13, $h, 'EXPIRED', 1, 1, 'C', true);
        }

        if (empty($output_path)) {
            $uploads_dir = dirname(__DIR__) . '/uploads';
            if (!file_exists($uploads_dir)) @mkdir($uploads_dir, 0777, true);
            $output_path = $uploads_dir . '/Sudarshan_Fitness_Expired_Members_' . date('Y-m-d') . '.pdf';
        }

        $pdf->Output('F', $output_path);
        return $output_path;
    }
}

