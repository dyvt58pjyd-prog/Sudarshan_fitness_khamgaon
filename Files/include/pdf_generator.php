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
        $pdf->Cell(0, 4.5, 'YOUR TRANSFORMATION BEGINS FROM TODAY', 0, 1);
        
        $pdf->SetTextColor(100, 116, 139); // #64748B (Muted Slate)
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetX(42);
        $pdf->Cell(0, 4, $gym['gym_address'], 0, 1);
        $pdf->SetX(42);
        $pdf->Cell(0, 4, 'Phone: ' . $gym['gym_contact'] . ' | Email: ' . $gym['gym_email'], 0, 1);
        
        // Invoice / Receipt Title and Metadata on Right
        $pdf->SetXY(140, 23);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(55, 6, 'INVOICE / RECEIPT', 0, 1, 'R');
        
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetX(140);
        $pdf->Cell(55, 4.5, 'Receipt ID: #' . $row['et_id'], 0, 1, 'R');
        $pdf->SetX(140);
        $pdf->Cell(55, 4.5, 'Date: ' . $row['paid_date'], 0, 1, 'R');
        
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
        
        // Table Header
        $pdf->SetXY(15, 84);
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
        $pdf->SetXY(15, 94);
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
        if ($discount > 0) {
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
        $pdf->Cell(30, 6, 'Rs. ' . $total_paid . '  ', 0, 1, 'R');
        
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
        $pdf->Cell(110, 3.2, 'System Engineered by DRDO, Ministry of Defence (Anurag Bawaskar)', 0, 1);
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
        $pdf->Cell(0, 4, 'Phone: ' . $gym['gym_contact'] . ' | Email: ' . $gym['gym_email'], 0, 1);
        
        // Invoice / Receipt Title and Metadata on Right
        $pdf->SetXY(140, 23);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(55, 6, 'INVOICE / RECEIPT', 0, 1, 'R');
        
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetX(140);
        $pdf->Cell(55, 4.5, 'Receipt ID: #PT-' . $row['pt_id'], 0, 1, 'R');
        $pdf->SetX(140);
        $pdf->Cell(55, 4.5, 'Date: ' . $row['enroll_date'], 0, 1, 'R');
        
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
        $pdf->Cell(110, 3.2, 'System Engineered by DRDO, Ministry of Defence (Anurag Bawaskar)', 0, 1);
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
