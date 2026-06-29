<?php
// Calculate live gym capacity (Check-ins in the last 2 hours)
$two_hours_ago = date('H:i:s', strtotime('-2 hours'));
$q_capacity = "SELECT COUNT(DISTINCT uid) as live_count FROM attendance WHERE date = CURRENT_DATE() AND entry_time >= '$two_hours_ago'";
$res_capacity = mysqli_query($con, $q_capacity);
$live_count = 0;
if ($res_capacity) {
    $row_cap = mysqli_fetch_assoc($res_capacity);
    $live_count = intval($row_cap['live_count']);
}

// Calculate needle rotation (-90 to +90 degrees)
// Assuming max capacity of 50 for the speedometer scale
$max_capacity = 50;
$percentage = min(100, ($live_count / $max_capacity) * 100);
$rotation = -90 + (180 * ($percentage / 100));

// Determine Status Color
$status_color = '#10b981'; // Green
$status_text = 'Quiet';
if ($percentage >= 50 && $percentage < 80) {
    $status_color = '#f59e0b'; // Yellow
    $status_text = 'Busy';
} elseif ($percentage >= 80) {
    $status_color = '#ef4444'; // Red
    $status_text = 'Packed';
}
?>

<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-12">
        <div style="background: var(--glass-bg); backdrop-filter: blur(16px); border: 1px solid var(--glass-border); border-radius: 12px; padding: 25px; box-shadow: var(--glass-shadow); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 250px;">
                <h3 style="margin-top: 0; color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 10px;">
                    <i class="entypo-gauge" style="color: <?php echo $status_color; ?>;"></i> Live Gym Capacity
                </h3>
                <p style="color: #a3a3a3; font-size: 14px; margin-bottom: 0;">Real-time biometric attendance tracker. Analyzes active check-ins within the last 2 hours.</p>
            </div>
            
            <!-- SVG Speedometer -->
            <div style="position: relative; width: 200px; height: 100px; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: flex-end;">
                <!-- Dial Arc -->
                <svg viewBox="0 0 100 50" style="width: 100%; height: 100%;">
                    <path d="M 10,50 A 40,40 0 0,1 90,50" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8" stroke-linecap="round"/>
                    <path d="M 10,50 A 40,40 0 0,1 90,50" fill="none" stroke="<?php echo $status_color; ?>" stroke-width="8" stroke-linecap="round" stroke-dasharray="125.6" stroke-dashoffset="<?php echo 125.6 - (125.6 * ($percentage / 100)); ?>" style="transition: stroke-dashoffset 1s ease-out;"/>
                </svg>
                
                <!-- Needle -->
                <div style="position: absolute; bottom: -5px; left: calc(50% - 2px); width: 4px; height: 45px; background: #fff; border-radius: 4px; transform-origin: bottom center; transform: rotate(<?php echo $rotation; ?>deg); transition: transform 1s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 5;"></div>
                
                <!-- Center Pin -->
                <div style="position: absolute; bottom: -8px; left: calc(50% - 8px); width: 16px; height: 16px; background: <?php echo $status_color; ?>; border-radius: 50%; border: 3px solid #222; z-index: 10;"></div>
            </div>
            
            <!-- Stats -->
            <div style="text-align: right; min-width: 150px;">
                <div style="font-size: 36px; font-weight: 800; color: #fff; line-height: 1;"><?php echo $live_count; ?></div>
                <div style="font-size: 14px; font-weight: 700; color: <?php echo $status_color; ?>; text-transform: uppercase; letter-spacing: 1px; margin-top: 5px;"><?php echo $status_text; ?></div>
            </div>
            
        </div>
    </div>
</div>
