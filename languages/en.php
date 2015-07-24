<?php
########################
# Note to translators: #
########################
# Only update the information inside the quotes on the 
# right side of the "=>" symbols and the comments after the "#"
# (including this notice at the top)


# This last two-letter code in brackets should be the ISO 639-1 language code (http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
# This code should also match the file name.
$languages["en"] = array(

  "isocode"      => "en",
  "languagename" => "English", # The name should be the native language name.
  
  # The first letter of each day of the week
  "Sunday-initial"    => "S",
  "Monday-initial"    => "M",
  "Tuesday-initial"   => "T",
  "Wednesday-initial" => "W",
  "Thursday-initial"  => "T",
  "Friday-initial"    => "F",
  "Saturday-initial"  => "S",
  
  # Month names
  # The date picker on search.php will use the first three letters of the month name as an abbreviation
  # if this is not acceptable for the language you're translating to, please file a bug report so we can
  # address this behavior.
  "January"   => "January",
  "February"  => "February",
  "March"     => "March",
  "April"     => "April",
  "May"       => "May",
  "June"      => "June",
  "July"      => "July",
  "August"    => "August",
  "September" => "September",
  "October"   => "October",
  "November"  => "November",
  "December"  => "December",

# common terms
  "Go"                     => "Go", # Used as the text for submit buttons
  "Note"                   => "Note",
  "altconfirm"             => "confirm",
  "altcancel"              => "cancel",
  "listlimitnote"          => "You can only ask for up to 250 results per page.",
  "showcount"              => "Showing %count% results per page", # %count% is used a variable here
  "Page"                   => "Page:",
  "Username"               => "Username",
  "Logs"                   => "Logs",
  "changeyourpassword"     => "Change Your Password",
  "IPAddress"              => "IP Address",
  "confirmdelete"          => "Are you sure you want to delete this object?",
  "deletesubnet"           => "delete subnet",
  "modifysubnet"           => "modify subnet",
  "blocknameconflict"      => "That name already exists in the database.", # also used as a notice
  "invalidip"              => "The IP you have entered is invalid.", # also used as a notice
  "invalidmask"            => "The subnet mask you have entered is not valid", # also used as a notice
  "invalidrequest"         => "That request is not valid",
  "None"                   => "None",
  "ReadOnly"               => "Read-Only",
  "ReserveIPs"             => "Reserve IPs",
  "AllocateSubnets"        => "Allocate Subnets",
  "AllocateBlocks"         => "Allocate Blocks",
  "Admin"                  => "Admin",
  "shortusername-notice"   => "The username must be four characters or longer.",
  "onecontact"             => "You must supply at least one form of contact for a user.", # also used as a notice
  "blocknamelength"        => "The block name must be between 3 and 25 characters long.",
  "subnetnamelength"       => "The subnet name must be between 3 and 25 characters long.",
  "aclnamelength"          => "The acl name mus be between 3 and 25 characters long.",
  "staticnamelength"       => "The static name must be between 3 and 25 characters long.",
  "notelengtherror"        => "The note must not be more than 80 characters long.",
  "SubnetMask"             => "Subnet Mask",
  "Gateway"                => "Gateway",
  "blankfield-notice"      => "You have left a required field blank.",
  "invalidrange"           => "The range provided does not contain at least two addresses in ascending order.",
  "aclsubnetmismatch"      => "The acl provided does not fall within exactly one subnet.",
  "blockoverlap-notice"    => "The IP range you entered overlaps with an existing block in the database.",  
  "acloverlap-notice"      => "The IP range you entered overlaps with an existing ACL in the database.",
  "contactlengtherror"     => "The contact you supplied is too long. Please limit it to 100 characters.",
  "StaleScan"              => "StaleScan: ",
  "StaleScandisabled"      => "StaleScan is disabled for this subnet",
  "enablestalescan"        => "click to enable stale scan",
  "disablestalescan"       => "click to disable stale scan",
  "staletoggleon-notice"   => "Stale Scan has been turned on",
  "staletoggleoff-notice"  => "Stale Scan has been turned off",

// header.php
  # note: many "notices" are generated by many different pages. They are interpreted at the top of header.php.
  "clicktoedit" => "click to edit...",
  "ClicktoEdit" => "Click to Edit",
  
// _blocks.php
  "selectblock"       => "Please select a block to do that.",
  "blockdeleted"      => "The %name% block has been deleted",
  "duplicatename"       => "An entry by that name already exists. Please choose a unique name.",
  
// blocks.php
  "blocknamehelp"       => "Enter the name of the IP block here. The name should be descriptive of what the subnets inside 
                            the block will be used for.",
  "blockiphelp"         => "Enter a block of IP addresses in CIDR notation such as \"10.10.0.0/23\" or using a subnet mask 
                            such as in \"10.10.0.0/255.255.254.0.\" You can also enter the start address of a range.",
  "blockendiphelp"      => "If you entered a start IP address for a range you can use this field for the end IP of the 
                            range. If you used a mask value in the IP field, this field will be ignored.",
  "blocknotehelp"       => "Enter a very brief description of what the subnets inside the block will be used for. An 
                            example would be \"Point to Point subnets.\"",
  "missingfield-notice" => "Please verify that required fields have been completed.",
  "blockbounds-notice"  => "You must supply the number of mask bits, a mask, or a valid end IP to add an IP block.",
  "blockadded-notice"   => "The block has been created.",
  "AllIPBlocks"         => "All IP Blocks",
  "SomeIPBlocks"        => "%block_name% IP Blocks",
  "AddaBlock"           => "Add a Block",
  "BlockName"           => "Block Name",
  "StartingIP"          => "Starting IP",
  "EndIP"               => "End IP",
  "modifyblock"         => "modify IP block",
  "ModifyBlock"         => "Modify the %block_name% IP block:",
  "blockupdated-notice" => "The block has been updated.",
  "iscontainerblock"    => "This block is meant to hold other blocks",
  "isipv4block"         => "This block is meant to hold IPv4 subnets",
  "ParentBlock"         => "Parent Block",
  "wouldorphansubnets"  => "You cannot change the block to the type specified because there are subnets in this block.",
  "wouldorphanblocks"   => "You cannot change the block to the type specified because there are child blocks that would be orphaned.",

  
// index.php
  "indextext" => "<h1>Welcome!</h1><br />
                    <h3>About Collate</h3>
                      <p> 
                      Collate is a collection of applications that will help people manage IT information. 
                      </p>  	            
                    <h3>About Collate:Network</h3>
                      <p> 
                      With this application, you can easily track your IP space allocations and static IP address assignments. 
                      </p>                    
                    <h3>Documentation</h3>
                      <p>
                      You can access this application's documenation online at <a href=\"http://collate.info/\">Collate.info</a>.
                      </p>",	

// install.php
  "upgradesuccess-notice" => "This application has been successfully upgraded!",
  "installsuccess-notice" => "This application has been successfully installed!",

// login.php
  "newpassword"            => "New Password:",
  "confirmpassword"        => "Confirm Password:",
  "ldapformatnote"         => "Your account may use LDAP authentication. In which case, your username must be formatted properly.",
  "domainnote"             => "If your username is in the domain ".$COLLATE['settings']['domain'].", then simply type your username. Otherwise,\n
                              please type your username in the format username@example.com unless you've been instructed by the \n
                              administrator of this application to do otherwise.",
  "nodomainnote"           => "Please type your username in the format username@example.com unless you've been instructed by the \n
                               administrator of this application to do otherwise.",
  "logout-notice"          => "You have successfully been logged out.",
  "alreadyloggedin-notice" => "You are already logged in.",
  "Login"                  => "Login",  
  "Password"               => "Password",
  "failedlogin-notice"     => "The username and/or password you have entered are invalid. Please note that failed login attempts are logged.",
  "lockedaccount-notice"   => "This account has been locked because there have been too many failed login attempts. You must contact your administrator 
                               to have your account unlocked.",
  "passwdexpired-notice"   => "Your password has expired. Please change your password before continuing.",
  "loginsuccess-notice"    => "You have successfully been logged in.",
  "passwdmatch-notice"     => "The new password and confirmation password you have entered do not match. Please try again.",
  "shortpasswd-notice"     => "The new password you have entered is less than the minimum password length required by your administrator. Please try again.",
  "ldappasswd-notice"      => "Your administrator has configured your account for LDAP authentication. You cannot change your password using this 
                               form. Please contact your administrator if you need assistance changing your password.",
  "oldpasswd-notice"       => "You have not supplied a new password. Please try again.",
  "passwdchange-notice"    => "You have successfully changed your password.",
  
// logs.php
  "confirmtruncate"        => "<h1>Truncate Logs?</h1><br />
                               <p><b>Are you sure you'd like to truncate the logs?</b> This will delete all log events in the database except the most
		                       recent 500 events. This action is not reversable!",
  "truncatesuccess-notice" => "The logs have been truncated",
  "TruncateLogs"           => "Truncate Logs",
  "Timestamp"              => "Timestamp",
  "Severity"               => "Severity",
  "Message"                => "Message",
  "nologs"                 => "No logs have been generated yet.",
  
// panel.php
  "ControlPanel"    => "Control Panel",
  "ManageUsers"     => "Manage Users",
  "UpdateProfile"   => "Update My Profile",
  "Documentation"   => "Documentation",
  "DiscoveredHosts" => "Discovered Hosts",
  "StaleHosts"      => "Stale Hosts",
  "Settings"        => "Settings",
  "BulkImport"      => "Bulk Import",
  
// search.php
  "shortsearch"           => "You must enter a search phrase of three characters or more in order to find results.",
  "numericfailedscans"    => "You can only search by Failed Scans count if you supply a number.",
  "SearchResults"         => "<h1>Search Results</h1>
                             <p><b>You searched for:</b><br />",
  "generalsearchterm"     => "All %first% where \"%second%\" is like \"%search%\" %searchdescription%",
  "failedscansearch"      => "All static IPs where the number of failed stale scans is greater than or equal to %search%",
  "nosearchresults"       => "No results were found that matched your search.",
  "SubnetName"            => "Subnet Name",
  "NetworkAddress"        => "Network Address",
  "SubnetMask"            => "Subnet Mask",
  "StaticsUsed"           => "Statics Used",
  "Contact"               => "Contact",
  "FailedScans"           => "FailedScans",
  "Timestamp"             => "Timestamp",
  "Severity"              => "Severity",
  "Message"               => "Message",
  "Block"                 => "Block",
  "Name"                  => "Name",
  "Path"                  => "Path",
  "deletestatic"          => "delete static IP",
  "IP"                    => "IP",
  "name"                  => "name",
  "note"                  => "note",
  "lastmodifiedby"        => "last modified by",
  "contact"               => "contact",
  "failedscanscount"      => "failed scans count",
  "username"              => "username",
  "level"                 => "level",
  "message"               => "message",
  "AdvancedSearch"        => "Advanced Search",
  "Lookfor"               => "Look for",
  "subnets"               => "subnets",
  "staticIPs"             => "static IPs",
  "logs"                  => "logs",
  "withaan"               => "with a/an",
  "Searchallrecords"      => "Search all records",
  "Specifydaterange"      => "Specify a date range",
  "dateFrom"              => "From:",
  "dateTo"                => "To:",
  "Exportresults"         => "Export Results as XML (Compatable with most spreadsheet applications.)",
  "searchdatedesc"        => "and the record was last modified between %fromdate% and %todate%",
  "IPblocks"              => "IP blocks",
  "nostaticsfound-notice" => "No static IP addresses matching your query were found. Below are the matching subnets.",
  
// common.php
  "login-notice" => "The administrator of this application requires you to log in to use this feature.",
  "perms-notice" => "You do not have sufficient access to use this resource. Please contact your administrator if you believe you have reached this page in error.",
  "outofpages"   => "out of %numofpages%",

// _settings.php
  "needadmin"        => "You must create at least one user with administrator rights before changing the permission requirements.",
  "ldapdeleted"      => "The ldap server entry \"%server%\" for the \"%domain%\" domain has been deleted.",
  "settingupdated"   => "The setting has been successfully updated.",
  "nosettingupdated" => "No settings have been updated.",
  "noldapsupport"    => "Your server does not currently support LDAP authentication. Database authentication will be used.",
  "oneldapserver"    => "You can only add one ldap server at a time",
  "invaliddomain"    => "Invalid domain",
  "defineldap"       => "Please define an ldap server for that domain first.",
  "APIkeydeleted"    => "The API key has been deleted.",
  "keyactivated"     => "The API key has been activated.",
  "keyrevoked"       => "The API key has been revoked.", 
  
  
// settings.php
  "Settings"          => "Settings",
  "General"           => "General",
  "DefaultLanguage"   => "Default Language:",
  "Authorization"     => "Authorization",
  "checkperms"        => "Check permissions for the following access",
  "readonly"          => "Read-Only (Must login to see/do anything)",
  "noauthentication"  => "None (Turn off authentication)",
  "Authentication"    => "Authentication",
  "DefaultAuthMethod" => "Default Authentication Method",
  "Database"          => "Database",
  "LDAP"              => "LDAP",
  "Domain"            => "Domain",
  "LDAPServer"        => "LDAP Server",
  "AddLDAPServer"     => "Add an LDAP Server",
  "DefaultDomainName" => "Default Domain Name",
  "defaultdomaintip"  => "The default domain name is appended to a username at login time if the username 
                         doesn't contain \"@\" and the user is using LDAP authentication.",
  "PasswordExpire"    => "Password Expiration:",
  "passwdexpiretip"   => "The number of days before a password epires. Use 0 for no password expiration. 
                          This setting is ignored for LDAP users.",
  "MinPasswdLength"   => "Minimum Password Length:",
  "passwdlengthtip"   => "Minimum password length does not apply to LDAP users.",
  "MaxLoginAttempts"  => "Maximum Failed Logins:",
  "maxlogintip"       => "Maximum failed login attempts before account is locked. This applies to database and 
                          ldap-authenticated users. Select 0 to prevent account lockouts.",
  "UserGuidance"      => "User Guidance",
  "DefaultIPGuidance" => "<b>Default IP Usage Guidance</b> (Optional)",
  "DNSGuidance"       => "<b>DNS Servers</b> (Optional)",
  "noserversdefined"  => "There are no servers defined yet.",
  "APIKeyDescript"    => "API Key Description",
  "Status"            => "Status",
  "APIKeys"           => "API Keys",
  "APIKey"            => "API Key",
  "AddAPIKey"         => "Generate an API Key",
  "nokeysdefined"     => "No API keys have been generated yet.",
  "Active"            => "Active",
  "Revoked"           => "Revoked",
  
  
// _users.php
  "userdeleted" => "%username% has been removed from the database.",
  "noperms"     => "You do not have permission to update this value.",
  "PasswordSet" => "Password Set",  

// users.php
  "Users"               => "Users",
  "AddaUser"            => "Add a User",
  "nousers"             => "There are no users yet.",
  "Telephone"           => "Telephone Number",
  "Email"               => "Email Address",
  "LastLogin"           => "Last Login",
  "Actions"             => "Actions",
  "DeleteUser"          => "Delete User",
  "EditUser"            => "Edit User",
  "PreferredLanguage"   => "Preferred Language",
  "UserAccessLevel"     => "User's Access Level",
  "SetTempPass"         => "Set Temporary Password",
  "Forcedbauth"         => "Force database authentication",
  "Lockaccount"         => "Lock account",
  "nameconflict-notice" => "That username is already in use. Please choose a unique name.",
  "useradded-notice"    => "The user has been added to the database",
  "userupdated-notice"  => "The user account has been updated",
  
// _statics.php
  "staticdeleted"         => "The static IP has been deleted.",
  "acldeleted"            => "The ACL statement has been deleted.",

// statics.php
  "ReserveaStaticIP"  => "Reserve a Static IP",
  "IPGuidance"        => "IP Guidance",
  "ContactPerson"     => "Contact Person",
  "Optional"          => "(Optional)",
  "nogateway"         => "This field relies on a single static IP having the note \"Default Gateway\" being reserved. This 
                          could not be found for this subnet. Please have your administrator correct this in order to see 
						  this information properly.",
  "IPReserved"        => "Your IP has been reserved!",
  "continuetostatics" => "Continue to Statics List",
  "ReserveIP"         => "Reserve an IP",
  "enablestalescan"   => "Click to enable stale scan",
  "disablestalescan"  => "Click to disable stale scan",
  "nostatics"         => "No static IPs have been reserved in this subnet yet.",
  "ACL"               => "ACL", # Stands for Access Control List.
  "AddACL"            => "Add an ACL statement",
  "StartingIPAddress" => "Starting IP Address",
  "EndingIPAddress"   => "Ending IP Address",
  "acladded-notice"   => "The ACL statement has been added.",
  "DNSServers"        => "DNS Servers",
  "Ping"              => "Ping",
  
// _subnets.php
  "subnetdeleted"   => "The subnet %name% (%cidr%) has been deleted",
  "showblockspace"  => "Show available IP space in this block instead",
  "SearchIPSpace"   => "Search for available IP Space",
  "Results"         => "Results",
  "IPSearchFormat"  => "The search term must be in the format of 192.168.0.0/16 or 192.168.0.0/255.255.0.0. 
                        The mask cannot be 0 or 32 bits long.",
  
// subnets.php
  "subnetname-tip"        => "Enter a unique name for the subnet here. The name should be descriptive of what the subnet 
                              will be used for. The name should be short and should not contain spaces.",
  "subnetaddress-tip"     => "Enter a subnet in CIDR notation such as \"192.168.1.0/24\" or using a subnet mask such as 
                              in \"192.168.1.0/255.255.255.0\".", # also used on a page as normal text
  "guidance-tip"          => "You may type a message that will be viewable to any user adding a static IP in this 
                              subnet. The message should help the user understand what IP to use for the type of 
						      device they wish to reserve an IP address for. Some formatting will be maintained, 
						      HTML is not allowed.",
  "AllocateaSubnet"       => "Allocate a Subnet",
  "Subnet"                => "Subnet",
  "ACLName"               => "ACL Name",
  "ACLRange"              => "ACL Range",
  "Showsearchinstead"     => "Show search instead",
  "AvailableIPinBlock"    => "Available IP Space in \"%block_name%\"",
  "invalidacl-notice"     => "The ACL you supplied is not valid.",
  "invalidgateway-notice" => "The gateway you supplied is not valid.",
  "subnetoverlap-notice"  => "The subnet you supplied overlaps with a previously allocated subnet in the database.",
  "subnetadded-notice"    => "The subnet you requested has been allocated.",
  "BlockSubnets"          => "\"%block_name%\" Subnets",
  "ModifySubnet"          => "Modify the %subnet_name% subnet: %start_ip%/%mask%",
  "Movesubnet"            => "Move %subnet_name% to a new block",
  "selectblock"           => "Select the block you'd like to move this subnet into",
  "Resizesubnet"          => "Resize %subnet_name%",
  "furtherpromptsahead"   => "You will be prompted on the next page with more information about how this will affect other
                              subnets and static IP reservations.",
  "subnetmoved-notice"    => "The subnet has been moved as requested.",
  "invalidshrink-notice"  => "The new subnet you specified is smaller than the old one, but not part of the old one. You should allocate a
                              new subnet instead.",
  "staticstodelete"       => "Static IPs in %original_subnet_name% that will be deleted",
  "nostaticsdeleted"      => "No static IP address reservations will be deleted by resizing this subnet.",
  "aclstobechanged"       => "ACLs in %original_subnet_name% will be modified as follows",
  "noaclschanged"         => "No ACL entries would be affected.",
  "Modification"          => "Modification",
  "StartingIPmodified"    => "Starting IP modified",
  "EndIPmodified"         => "End IP modified",
  "ToBeDeleted"           => "TO BE DELETED",
  "invalidgrow-notice"    => "The subnet you're expanding must be included in the new, larger subnet specified.",
  "subnetstomerge"        => "Subnets that must be merged to cleanly expand %original_subnet_name% based on your input",
  "nosubnetsoverlap"      => "No reserved subnets are overlapped by growing this subnet.",
  "confirmproceed"        => "Would you like to proceed?",
  "resized-notice"        => "The subnet has been resized.",
  "ipalreadyreserved"     => "The IP address you supplied is already reserved.",
  
// footer.php
#
# When translating this section, please link to the license.txt file as the "ruling license"
# and link to an external version of the license "for convenience." For Example:
#
# http://en.wikipedia.org/wiki/MIT_License
# http://es.wikipedia.org/wiki/MIT_License
# http://fr.wikipedia.org/wiki/Licence_MIT
# http://de.wikipedia.org/wiki/MIT-Lizenz
# http://nl.wikipedia.org/wiki/MIT-licentie
#
 "footertext" => "Collate:Network &copy; 2006 - 2015 by its <a href=\"http://collate.info/\">developers</a><br />
	              This software is licensed under the terms of  <a href=\"license.txt\">The MIT License</a><br />
	              Version: %version_number%",

// nav.php
  "Navigation"  => "Navigation",
  "Browse"      => "Browse",
  "LogIn"       => "Log In",
  "LogOut"      => "Log Out",
  "QuickSearch" => "Quick Search",
  "Hostname"    => "Hostname",
  
// command.php
  "uploadwarning"  => "Please consult the <a href=\"http://code.google.com/p/collate-network/w/list\">documentation</a> 
                       for guidance on the layout of the CSV files this form supports.",
  "SelectFile"     => "<b>Select a file</b> (up to %bytes% bytes long)",
  "filesizenote"   => "The file size limit is the smallest of the following values in your php.ini: post_max_size, upload_max_filesize, and memory_limit",
  "erroroccured"   => "An error has occured",
  "uploadtoobig"   => "The uploaded file was too large.",
  "partialupload"  => "The uploaded file was only partially uploaded.",
  "noupload"       => "No file was uploaded.",
  "notmpfolder"    => "Missing a temporary folder.",
  "diskwritefail"  => "Failed to write file to disk.",
  "extensionfail"  => "File upload stopped by extension.",
  "unknownerror"   => "Unknown upload error.",
  "tryagain"       => "Go back and try again",
  "erroronrow"     => "Error on row %rownum%: %error%",
  "invalidrecord"  => "Invalid record type.",
  "badfieldcount"  => "Invalid number of fields for this record type.",
  "manyimporterr"  => "<b>Note:</b> Only the first 50 validation errors are displayed.",
  "blocknotfound"  => "The block you specified does not exist.",
  "subnetnotfound" => "The IP address you supplied did not fall within a subnet in the database.",
  "aclmatch"       => "The IP address you supplied is not available due to an ACL.",
  "badencoding"    => "Sorry. Collate:Network only supports ASCII-encoded CSV files right now.",
  "importsuccess"  => "You have successfully imported %rows% records!",
  
// install.php
  "uptodate-notice" => "You're already running the current version of Collate:Network",
);
?>