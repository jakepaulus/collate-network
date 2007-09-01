<?php
/**
 * Please see /include/common.php for documentation on common.php, the $COLLATE global array used by this program, and the AccessControl function used widely.
 */
require_once('./include/common.php');
require_once('./include/header.php');

$op = (empty($_GET['op'])) ? 'default' : $_GET['op'];

switch($op){

	case "add";
	AccessControl("5", "New User form accessed");
	add_user();
	break;
		
	case "submit"; 
	submit_user();
	break;
		
	case "edit"; 
	add_user();
	break;
		
	case "delete";
	delete_user();
	break;
		
	default:
	AccessControl("1", "User list viewed");
	list_users();
	break;
	
}

require_once('./include/footer.php');

function list_users(){
  global $COLLATE;
  
  $sql = "SELECT username, phone, email FROM users ORDER BY username"; 
  $result = mysql_query($sql);
    
  echo "<h1>All Users</h1>\n".
       "<p style=\"text-align: right;\"><a href=\"users.php?op=add\"><img src=\"images/add.gif\" alt=\"Add\" /> Add a User </a></p>".
       "<table width=\"100%\"><tr><th>Username</th><th>Telephone Number</th><th>Email Address</th><th>Actions</th></tr>".
	   "<tr><td colspan=\"4\"><hr class=\"head\" /></td></tr>";
	   
  while(list($username,$phone,$email) = mysql_fetch_row($result)){
    echo "<tr><td>$username</td><td>$phone</td><td>$email</td>
	     <td><a href=\"users.php?op=delete&amp;username=$username\"><img src=\"./images/remove.gif\" alt=\"X\" /></a> &nbsp;
		 &nbsp;<a href=\"users.php?op=edit&amp;username=$username\"><img src=\"./images/edit.gif\" alt=\"edit\" /></td></tr>".
	     "<tr><td colspan=\"4\"><hr class=\"division\" /></td></tr>";
  }
  
  echo "</table>";

} // Ends list_users function

function delete_user() {
  global $COLLATE;
  $username = clean($_GET['username']);
  $accesslevel = "5";
  $message = "user delete attempt: $username";
  
  AccessControl($accesslevel, $message);
  
  if($_GET['confirm'] != "yes") { // draw the confirmation page or error
  
    $sql = "SELECT username FROM users WHERE username='$username'";
    $row = mysql_query($sql);
    if(mysql_num_rows($row) == '1') {
      echo "Are you sure you'd like to delete the user \"$username\"?<br /><br />\n".
		   "<a href=\"users.php?op=delete&amp;username=$username&amp;confirm=yes\">".
		   "<img src=\"./images/apply.gif\" alt=\"confirm\" /></a> &nbsp; <a href=\"users.php\"><img src=\"./images/cancel.gif\" alt=\"cancel\" /></a>";
      require_once('./include/footer.php');
	  exit();
    }
	else{
    $notice = "There is no user in the database called $username";
	}
  }
  else { // delete the row, they are sure
    $sql = "DELETE FROM users WHERE username='$username'";
    $result = mysql_query($sql);
	$notice = "$username has been removed from the database.";
  }
  header("Location: users.php?notice=$notice");
  exit();
} // Ends delete_site function

function add_user(){
  global $COLLATE;
  global $op;
  
  $username = (empty($_GET['username'])) ? '' : $_GET['username'];
  
  if($op == 'edit'){
  
  $accesslevel = "5";
  $message = "User Edit form accessed: $username";
  AccessControl($accesslevel, $message); // This is placed here to prevent false messages saying the system account was deleted.
  
    $post_to = "users.php?op=submit&amp;action=edit";
	    if(empty($username)){
	  $notice = "Please select a user to edit.";
	  header("Location: users.php?notice=$notice");
	  exit();
	}
		$sql = "SELECT passwd, tmppasswd, accesslevel, phone, email FROM users WHERE username='$username'";
    $result = mysql_query($sql);
		if(mysql_num_rows($result) != '1'){
	  $notice = "Please select a user to edit.";
	  header("Location: users.php?notice=$notice");
	  exit();
	}
		list($passwd,$tmppasswd,$accesslevel,$phone,$email) = mysql_fetch_row($result);
  }
  else{
  $accesslevel = "5";
  $message = "Add User form accessed";
  
  AccessControl($accesslevel, $message); // This is placed here to prevent false messages saying the system account was deleted.
    $post_to = "users.php?op=submit&amp;action=add";
	$phone = (empty($_GET['phone'])) ? '' : $_GET['phone'];
	$email = (empty($_GET['email'])) ? '' : $_GET['email'];
	$accesslevel = '0';
  }
  
  echo "<div id=\"passwordtip\" style=\"display: none;\" class=\"tip\">A temporary password must be set for this 
       user to login for the first time. The user will \n".
       "be prompted to change their password the first time they login.<br /></div>\n";
	   
  if($op == "edit"){
    echo "<h1>Update User: $username</h1>\n";
  }
  else{
    echo  "<h1>Add a user:</h1>\n";
  }
  echo "<br />\n".
       "<form action=\"$post_to\" method=\"post\">\n";
	  
  if($op == "edit"){
    echo "<p style=\"display: none;\"><input type=\"hidden\" name=\"username\" value=\"$username\" /></p>";
  }
  else{
    echo "    <p>Username:<br />\n".
         "    <input name=\"username\" type=\"text\" value=\"$username\" /></p>\n";
  }
  echo "    <p>Telephone Number:<br />\n".
       "    <input name=\"phone\" type=\"text\" value=\"$phone\" /></p> \n".
       "    <p>Email Address: (optional)<br />\n".
       "    <input name=\"email\" type=\"text\" value=\"$email\" /></p>\n";
      
  echo "<p>User's Permissions:<br />\n";

  $hide = "onclick=\"new Effect.Fade('extraforms', {duration: 0.2})\"";
  $show = "onclick=\"new Effect.Appear('extraforms', {duration: 0.2})\"";
  $show1 = "onclick=\"new Effect.Fade('extraforms', {duration: 0.2})\"";
  $show2 = "onclick=\"new Effect.Fade('extraforms', {duration: 0.2})\"";
  $show3 = "onclick=\"new Effect.Fade('extraforms', {duration: 0.2})\"";
  $show4 = "onclick=\"new Effect.Fade('extraforms', {duration: 0.2})\"";
  $show5 = "onclick=\"new Effect.Fade('extraforms', {duration: 0.2})\"";

  if($COLLATE['settings']['checklevel1perms'] === "1"){ $show1 = $show; }
  if($COLLATE['settings']['checklevel2perms'] === "1"){ $show2 = $show; }
  if($COLLATE['settings']['checklevel3perms'] === "1"){ $show3 = $show; }
  if($COLLATE['settings']['checklevel4perms'] === "1"){ $show4 = $show; }
  if($COLLATE['settings']['checklevel5perms'] === "1"){ $show5 = $show; }
  
  if($accesslevel == '1'){ 
    $checked1 = "checked=\"checked\""; 
  }
  elseif($accesslevel == '2'){ 
    $checked2 = "checked=\"checked\""; 
  }
  elseif($accesslevel == '3'){ 
    $checked3 = "checked=\"checked\""; 
  }
  elseif($accesslevel == '4'){ 
    $checked4 = "checked=\"checked\""; 
  }
  elseif($accesslevel == '5'){ 
    $checked5 = "checked=\"checked\""; 
  }
  else{
    $checked0 = "checked=\"checked\"";
  }
  
  if(empty($passwd) && empty($tmppasswd)){
    $hidden = "style=\"display: none;\"";
  }
  
  echo "<input type=\"radio\" name=\"perms\" $hide $checked0 value=\"0\" /> None<br />\n".
       "<input type=\"radio\" name=\"perms\" $show1 $checked1 value=\"1\" />Read-Only<br />\n".
	   "<input type=\"radio\" name=\"perms\" $show2 $checked2 value=\"2\" />Reserve IPs<br />\n".
       "<input type=\"radio\" name=\"perms\" $show3 $checked3 value=\"3\" />Allocate Subnets<br />\n".
	   "<input type=\"radio\" name=\"perms\" $show4 $checked4 value=\"4\" />Add IP Blocks<br />\n".
	   "<input type=\"radio\" name=\"perms\" $show $checked5 value=\"5\" />Admin<br />\n".
       "</p>\n".
	   "<div id=\"extraforms\" $hidden>\n".
	   "<p>Temporary Password:<br />\n".
	   "<input id=\"tmppassword\" name=\"tmppassword\" type=\"text\" size=\"30\" />\n".
	   "<a href=\"#\" onclick=\"new Effect.toggle($('passwordtip'),'appear')\"><img src=\"images/help.gif\" alt=\"[?]\" /></a></p>\n".
	   "</div>\n".
       "<p style=\"clear: left.\"><input type=\"submit\" value=\" Go \" /></p>\n".
       "</form>\n";

} // Ends add_user function

function submit_user(){
  global $COLLATE;
  $username = clean($_POST['username']);
  $tmppassword = clean($_POST['tmppassword']);
  $phone = clean($_POST['phone']);
  $email = clean($_POST['email']);
  $perms = clean($_POST['perms']);
  
  $action = clean($_GET['action']);
  
  $accesslevel = "3";
  $message = "new user add attempted: $username";
  AccessControl($accesslevel, $message); 
  
  if (strlen($username) < "4" ){ 
    $notice = "The username must be four characters or longer."; 
    header("Location: users.php?op=$action&username=$username&phone=$phone&email=$email&notice=$notice");
	exit();
  } 
  
  if(strlen($phone) < "2") {
    $notice = "You must include a contact number for the user.";
    header("Location: users.php?op=$action&username=$username&phone=$phone&email=$email&notice=$notice");
	exit();
  }
  
  if(!empty($tmppassword) && strlen($tmppassword) < $COLLATE['settings']['passwdlength']){
    $notice = "The password you have set for the user is too short. Please try again";
	header("Location: users.php?op=$action&username=$username&phone=$phone&email=$email&notice=$notice");
	exit();
  }
  else{
   $tmppasswd = sha1($tmppassword);
  }
   if($action == "add"){
    $test = mysql_query("SELECT id FROM users WHERE username='$username'");
    if(mysql_num_rows($test) > "0") {
      $notice = "This user already exists in the database. Please use a unique username.";
      header("Location: users.php?op=add&username=$username&phone=$phone&email=$email&notice=$notice");
	  exit();
    }
  }

  if($action == "add"){
    $sql = "INSERT INTO users (username, tmppasswd, accesslevel, phone, email) 
           VALUES('$username', '$tmppasswd', '$perms', '$phone', '$email')";
  }
  elseif($action == "edit" && empty($tmppassword)){
    $sql = "UPDATE users SET accesslevel='$perms', phone='$phone', email='$email' WHERE username='$username'";
  }
  elseif($action == "edit"){
    $sql = "UPDATE users SET tmppasswd='$tmppasswd', accesslevel='$perms', phone='$phone', email='$email', loginattempts='0' WHERE username='$username'";
  }
  else{
    $notice = "An error has occured. Please try again.";
	header("Location: users.php?notice=$notice");
	exit();
  }

  mysql_query($sql);
  
  if($action == "edit"){
    $notice = "The user $username has been updated.";
  }
  else{
    $notice = "The user has been added to the database.";
  }
  header("Location: users.php?notice=$notice");
  exit();

} // Ends process_new_user function

?>