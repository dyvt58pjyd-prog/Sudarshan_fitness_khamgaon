
<?php
require '../../include/db_conn.php';
page_protect();
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <title>SUDARSHAN FITNESS| Routine</title>
    <link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="../../css/premium.css?v=<?php echo time(); ?>">
    <style>
      .page-container .sidebar-menu #main-menu li#routinehassubopen > a {
      background-color: rgba(0, 122, 255, 0.1);
      color: #007aff;
    }
    .a1-container table td {
      padding: 9px 0 !important;
      vertical-align: middle !important;
      color: #1d1d1f !important;
      font-weight: 600;
      font-size: 13px;
    }
    </style>
</head>
     <body class="page-body  page-fade" onload="collapseSidebar()">

    	<div class="page-container sidebar-collapsed" id="navbarcollapse">	
	
		<div class="sidebar-menu">
	
			<header class="logo-env">
			
			<!-- logo -->
			<div class="logo">
				<a href="main.php">
					<?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
				</a>
			</div>
			
					<!-- logo collapse icon -->
					<div class="sidebar-collapse" onclick="collapseSidebar()">
				<a href="#" class="sidebar-collapse-icon with-animation"><!-- add class "with-animation" if you want sidebar to have animation during expanding/collapsing transition -->
					<i class="entypo-menu"></i>
				</a>
			</div>
							
			
		
			</header>
    		<?php include('nav.php'); ?>
    	</div>

    		<div class="main-content">
		
				<div class="row">
					
					<!-- Profile Info and Notifications -->
					<div class="col-md-6 col-sm-8 clearfix">	
							
					</div>
					
					
					<!-- Raw Links -->
					<div class="col-md-6 col-sm-4 clearfix hidden-xs">
						
						<ul class="list-inline links-list pull-right">

							<li>Welcome <?php echo $_SESSION['full_name']; ?> 
							</li>						
						
							<li>
								<a href="logout.php">
									Log Out <i class="entypo-logout right"></i>
								</a>
							</li>
						</ul>
						
					</div>
					
				</div>

		<h3>Create Routine</h3>

		<hr />

		
		
		 <div class="a1-container a1-small a1-padding-32" style="margin-top:2px; margin-bottom:2px;">
        <div class="a1-card-8" style="width:500px; margin:0 auto; border-radius: 16px; overflow: hidden; background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.03);">
		<div class="a1-container a1-center" style="background: #f8fafc; border-bottom: 1px solid rgba(0, 0, 0, 0.06); padding: 14px 20px;">
        	<h6 style="margin: 0; font-weight: 700; color: #1d1d1f; font-size: 14px; letter-spacing: 0.5px;">NEW ROUTINE</h6>
        </div>
       <form id="form1" name="form1" method="post" class="a1-container" action="saveroutine.php">
         <table width="100%" border="0" align="center">
         <tr>
           <td height="35"><table width="100%" border="0" align="center">
           	 <tr>
           	   <td height="35">ROUTINE NAME:</td>
           	   <td height="35"><input name="rname"  size="30" required/></td>
         	   </tr>
			   
			   <tr>
               <td height="35">DAY 1:</td>
               <td height="35"><label for="textarea"></label>
                 <textarea name="day1" id="textarea" style="margin: 0px; width: 236px; height: 42px; resize:none;"></textarea></td>
             </tr>
             <tr>
               <td height="35">DAY 2:</td>
               <td height="35"><label for="textarea"></label>
                 <textarea name="day2" id="textarea" style="margin: 0px; width: 236px; height: 42px;resize:none;"></textarea></td></td>
             </tr>
             <tr>
               <td height="35">DAY 3:</td>
               <td height="35"><label for="textarea"></label>
                 <textarea name="day3" id="textarea" style="margin: 0px; width: 236px; height: 42px;resize:none;"></textarea></td></td>
             </tr>
             <tr>
               <td height="35">DAY 4:</td>
               <td height="35"><label for="textarea"></label>
                 <textarea name="day4" id="textarea" style="margin: 0px; width: 236px; height: 42px;resize:none;"></textarea></td></td>
             </tr>
            <tr>
               <td height="35">DAY 5:</td>
               <td height="35"><label for="textarea"></label>
                 <textarea name="day5" id="textarea" style="margin: 0px; width: 236px; height: 42px;resize:none;"></textarea><td></td>
             </tr>
             <tr>
               <td height="35">DAY 6:</td>
               <td height="35"><label for="textarea"></label>
                 <textarea name="day6" id="textarea" style="margin: 0px; width: 236px; height: 42px;resize:none;"></textarea></td></td>
             </tr>
			 
            
             <tr>
             <tr>
               <td height="35">&nbsp;</td>
               <td height="35"><input class="a1-btn a1-blue" type="submit" name="submit" id="submit" value="Add Routine" >
                 <input class="a1-btn a1-blue" type="reset" name="reset" id="reset" value="Reset"></td>
             </tr>
           </table></td>
         </tr>
         </table>
       </form>
    </div>
    </div>   
		
		
		
		
		


<?php include('footer.php'); ?>
    	</div>

    </body>
</html>


				
