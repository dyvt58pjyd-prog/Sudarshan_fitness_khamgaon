<?php
/**
 * Developer Credit Footer
 * Designed & Developed by Anurag Bawaskar
 * Include this at the bottom of every page before </body>
 */
?>
<!-- ===== DEVELOPER CREDIT FOOTER ===== -->
<div id="dev-credit-footer" style="
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 9990;
    background: linear-gradient(90deg, rgba(10,10,20,0.97) 0%, rgba(15,7,18,0.97) 50%, rgba(10,10,20,0.97) 100%);
    border-top: 1px solid rgba(255,107,0,0.3);
    padding: 7px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow: 0 -4px 24px rgba(0,0,0,0.5);
">
    <!-- Left: Developer Branding -->
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:6px;white-space:nowrap;">
            <span style="font-size:14px;">⚡</span>
            <span style="font-size:12px;font-weight:400;color:rgba(255,255,255,0.55);font-family:'Segoe UI',Arial,sans-serif;">Designed &amp; Developed by</span>
            <span style="font-size:13px;font-weight:800;color:#ff6b00;font-family:'Segoe UI',Arial,sans-serif;text-shadow:0 0 10px rgba(255,107,0,0.5);">Anurag Bawaskar</span>
        </div>
        <div style="background:rgba(255,107,0,0.12);border:1px solid rgba(255,107,0,0.3);color:rgba(255,160,60,0.9);font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap;font-family:'Segoe UI',Arial,sans-serif;">
            Additional Section Officer
        </div>
    </div>

    <!-- Right: Contact + Copyright -->
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="tel:+918459962390" style="display:flex;align-items:center;gap:5px;text-decoration:none;color:rgba(255,255,255,0.7);font-size:12px;font-weight:600;font-family:'Segoe UI',Arial,sans-serif;transition:color 0.2s;white-space:nowrap;" onmouseover="this.style.color='#ff6b00'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">
            <span style="font-size:13px;">📞</span>
            <span>+91 84599 62390</span>
        </a>
        <div style="width:1px;height:14px;background:rgba(255,255,255,0.1);flex-shrink:0;"></div>
        <div style="font-size:10px;color:rgba(255,255,255,0.28);white-space:nowrap;font-family:'Segoe UI',Arial,sans-serif;">
            &copy; <?php echo date('Y'); ?> Sudarshan Fitness
        </div>
    </div>
</div>
<!-- Spacer so footer doesn't cover content -->
<div style="height:38px;"></div>
