<?php
// Calculate top 5 members for the current month
$q_leaderboard = "SELECT a.uid, u.username, u.photo, COUNT(a.id) as visits 
                  FROM attendance a 
                  JOIN users u ON a.uid = u.userid 
                  WHERE MONTH(a.date) = MONTH(CURRENT_DATE()) 
                    AND YEAR(a.date) = YEAR(CURRENT_DATE()) 
                  GROUP BY a.uid 
                  ORDER BY visits DESC 
                  LIMIT 5";
$res_leaderboard = mysqli_query($con, $q_leaderboard);
?>

<div class="col-md-12" style="margin-top: 30px;">
    <div style="background: linear-gradient(135deg, rgba(255,215,0,0.1) 0%, rgba(0,0,0,0.8) 100%); border: 1px solid rgba(255,215,0,0.3); border-radius: 12px; padding: 25px; box-shadow: 0 10px 30px rgba(255,215,0,0.1);">
        <h3 style="margin-top: 0; color: #ffd700; font-weight: 800; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 2px;">
            <i class="entypo-trophy" style="font-size: 24px;"></i> Monthly Attendance Kings & Queens
        </h3>
        <p style="color: #a3a3a3; font-size: 14px; margin-bottom: 25px;">The top 5 members with the highest gym attendance this month. Reward them to boost engagement!</p>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php
            if ($res_leaderboard && mysqli_num_rows($res_leaderboard) > 0) {
                $rank = 1;
                while ($row = mysqli_fetch_assoc($res_leaderboard)) {
                    $photo_raw = $row['photo'];
                    if (strpos($photo_raw, '../../Sudarshan Data Folder/') === 0) {
                        $photo_raw = '/Sudarshan Data Folder/' . substr($photo_raw, 26);
                    } elseif (strpos($photo_raw, '../../') === 0) {
                        $photo_raw = '/' . substr($photo_raw, 6);
                    }
                    $photo = htmlspecialchars($photo_raw);
                    
                    // Style differently based on rank
                    $badge_color = '#4b5563'; // Default Gray
                    $badge_icon = '';
                    if ($rank == 1) { $badge_color = '#ffd700'; $badge_icon = '<i class="entypo-star"></i> '; } // Gold
                    elseif ($rank == 2) { $badge_color = '#9ca3af'; } // Silver
                    elseif ($rank == 3) { $badge_color = '#b45309'; } // Bronze
                    
                    echo '
                    <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #222; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; color: '.$badge_color.';">
                                #'.$rank.'
                            </div>
                            '.(!empty($row['photo']) ? '<img src="'.$photo.'" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid '.$badge_color.';">' : '<div style="width: 40px; height: 40px; border-radius: 50%; background: #333; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff;"><i class="entypo-user"></i></div>').'
                            <div>
                                <div style="color: #fff; font-weight: 700; font-size: 16px;">'.htmlspecialchars($row['username']).'</div>
                                <div style="color: #a3a3a3; font-size: 12px;">ID: '.htmlspecialchars($row['uid']).'</div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: '.$badge_color.'; font-size: 24px; font-weight: 800;">'.$badge_icon.intval($row['visits']).'</div>
                            <div style="color: #a3a3a3; font-size: 10px; text-transform: uppercase;">Visits</div>
                        </div>
                    </div>';
                    $rank++;
                }
            } else {
                echo '<div style="color: #a3a3a3; padding: 20px; text-align: center; border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px;">No attendance logged this month yet.</div>';
            }
            ?>
        </div>
    </div>
</div>
