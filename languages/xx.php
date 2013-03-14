<?php
########################
# Note to translators: #
########################
# This file's purpose is only to help find parts of the application that
# still require work to add multiple-language support.
#


# This last two-letter code in bracks should be the ISO 639-1 language code (http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)
# This code should also match the file name.
$languages["xx"] = array(

  "isocode"      => "xx",
  "languagename" => "#######", # The name should be the native language name.
  
  # The first letter of each day of the week
  "Sunday-initial"    => "#",
  "Monday-initial"    => "#",
  "Tuesday-initial"   => "#",
  "Wednesday-initial" => "#",
  "Thursday-initial"  => "#",
  "Friday-initial"    => "#",
  "Saturday-initial"  => "#",
  
  # Month names
  # The date picker on search.php will use the first three letters of the month name as an abbreviation
  # if this is not acceptable for the language you're translating to, please file a bug report so we can
  # address this behavior.
  "January"   => "#######",
  "February"  => "########",
  "March"     => "#####",
  "April"     => "#####",
  "May"       => "###",
  "June"      => "####",
  "July"      => "####",
  "August"    => "######",
  "September" => "#########",
  "October"   => "#######",
  "November"  => "########",
  "December"  => "########",

# common terms
  "Go"                     => "##", # Used as the text for submit buttons
  "Note"                   => "####",
  "altconfirm"             => "#######",
  "altcancel"              => "######",
  "listlimitnote"          => "### ### #### ### ### ## ## ### ####### ### ####.",
  "showcount"              => "####### %count% ####### ### ####", # %count% is used a variable here
  "Page"                   => "####:",
  "Username"               => "########",
  "Logs"                   => "####",
  "changeyourpassword"     => "###### #### ########",
  "IPAddress"              => "## #######",
  "confirmdelete"          => "### ### #### ### #### ## ###### #### ######?",
  "deletesubnet"           => "###### ######",
  "modifysubnet"           => "###### ######",
  "blocknameconflict"      => "#### #### ####### ###### ## ### ########.", # also used as a notice
  "invalidip"              => "### ## ### #### ####### ## #######.", # also used as a notice
  "invalidmask"            => "### ###### #### ### #### ####### ## ### #####", # also used as a notice
  "invalidrequest"         => "#### ####### ## ### #####",
  "None"                   => "####",
  "ReadOnly"               => "#########",
  "ReserveIPs"             => "####### ###",
  "AllocateSubnets"        => "######## #######",
  "AllocateBlocks"         => "######## ######",
  "Admin"                  => "#####",
  "shortusername-notice"   => "### ######## #### ## #### ########## ## ######.",
  "onecontact"             => "### #### ###### ## ##### ### #### ## ####### ### # ####.", # also used as a notice
  "blocknamelength"        => "### ##### #### #### ## ####### # ### ## ########## ####.",
  "subnetnamelength"       => "### ###### #### #### ## ####### # ### ## ########## ####.",
  "aclnamelength"          => "### ### #### ### ## ####### # ### ## ########## ####.",
  "staticnamelength"       => "### ###### #### #### ## ####### # ### ## ########## ####.",
  "notelengtherror"        => "### #### #### ### ## #### #### ## ########## ####.",
  "SubnetMask"             => "###### ####",
  "Gateway"                => "#######",
  "blankfield-notice"      => "### #### #### # ######## ##### #####.",
  "invalidrange"           => "### ## ##### ### ######### ## ### #####.",  
  "blockoverlap-notice"    => "### ## ##### ### ####### ######## #### ## ######## ##### ## ### ########.",  
  "acloverlap-notice"      => "### ## ##### ### ####### ######## #### ## ######## ### ## ### ########.",
  "contactlengtherror"     => "### ####### ### ######## ## ### ####. ###### ##### ## ## ### ##########.",
  
// header.php
  # note: many "notices" are generated by many different pages. They are interpreted at the top of header.php.
  "clicktoedit" => "##### ## ####...",
  "ClicktoEdit" => "##### ## ####",
  
// _blocks.php
  "selectblock"       => "###### ###### # ##### ## ## ####.",
  "blockdeleted"      => "### %name% ##### ### #### #######",
  "duplicatename"     => "## ##### ## #### #### ####### ######. ###### ###### # ###### ####.",
  
// blocks.php
  "blocknamehelp"       => "##### ### #### ## ### ## ##### ####. ### #### ###### ## ########### ## #### ### ####### ###### 
                            ### ##### #### ## #### ###. ### #### ###### ## ##### ### ###### ### ####### ######.",
  "blockiphelp"         => "##### # ##### ## ## ######### ## #### ######## #### ## \"10.10.0.0/23\" ## ##### # ###### #### 
                            #### ## ## \"10.10.0.0/255.255.254.0.\" ### ### #### ##### ### ##### ####### ## # #####.",
  "blockendiphelp"      => "## ### ####### # ##### ## ####### ### # ##### ### ### ### #### ##### ### ### ### ## ## ### 
                            #####. ## ### #### # #### ##### ## ### ## #####, #### ##### #### ## #######.",
  "blocknotehelp"       => "##### # #### ##### ########### ## #### ### ####### ###### ### ##### #### ## #### ###. ## 
                            ####### ##### ## \"##### ## ##### #######.\"",
  "missingfield-notice" => "###### ###### #### ######## ###### #### #### #########.",
  "blockbounds-notice"  => "### #### ###### ### ###### ## #### ####, # ####, ## # ##### ### ## ## ### ## ## #####.",
  "blockadded-notice"   => "### ##### #### #### #######.",
  "AllIPBlocks"         => "### ## ######",
  "AddaBlock"           => "### # #####",
  "BlockName"           => "##### ####",
  "StartingIP"          => "######## ##",
  "EndIP"               => "### ##",
  

  
// index.php
  "indextext" => "<h1>#######!</h1><br />
                    <##>##### #######</##>
                      <#> 
                      ####### ## # ########## ## ############ #### #### #### ###### ###### ## ###########. 
                      </#>  	            
                    <##>##### #######:#######</##>
                      <#> 
                      #### #### ###########, ### ### ###### ##### #### ## ##### ########### ### ###### ## ####### ###########. 
                      </#>                    
                    <##>#############</##>
                      <#>
                      ### ### ###### #### ###########'# ############ ###### ## <# ####=\"####://#######.####/\">#######.####</#>.
                      </#>",		

// install.php
  "upgradesuccess-notice" => "#### ########### ### #### ############ ########!",
  "installsuccess-notice" => "#### ########### ### #### ############ #########!",

// login.php
  "newpassword"            => "### ########:",
  "confirmpassword"        => "####### ########:",
  "ldapformatnote"         => "#### ####### ### ### #### ##############. ## ##### ####, #### ######## #### ## ######### ########.",
  "domainnote"             => "## #### ######## ## ## ### ###### ".$COLLATE['settings']['domain'].", #### ###### #### #### ########. #########,\n
                              ###### #### #### ######## ## ### ###### ########@#######.### ###### ###'## #### ########## ## ### \n
                              ############# ## #### ########### ## ## #########.",
  "nodomainnote"           => "###### #### #### ######## ## ### ###### ########@#######.### ###### ###'## #### ########## ## ### \n
                               ############# ## #### ########### ## ## #########.",
  "logout-notice"          => "### #### ############ #### ###### ###.",
  "alreadyloggedin-notice" => "### ### ####### ###### ##.",
  "Login"                  => "#####",  
  "Password"               => "########",
  "failedlogin-notice"     => "### ######## ###/## ######## ### #### ####### ### #######. ###### #### #### ###### ##### ######## ### ######.",
  "lockedaccount-notice"   => "#### ####### ### #### ###### ####### ##### #### #### ### #### ###### ##### ########. ### #### ####### #### ############# 
                               ## #### #### ####### ########.",
  "passwdexpired-notice"   => "#### ######## ### #######. ###### ###### #### ######## ###### ##########.",
  "loginsuccess-notice"    => "### #### ############ #### ###### ##.",
  "passwdmatch-notice"     => "### ### ######## ### ############ ######## ### #### ####### ## ### #####. ###### ### #####.",
  "shortpasswd-notice"     => "### ### ######## ### #### ####### ## #### #### ### ####### ######## ###### ######## ## #### #############. ###### ### #####.",
  "ldappasswd-notice"      => "#### ############# ### ########## #### ####### ### #### ##############. ### ###### ###### #### ######## ##### #### 
                               ####. ###### ####### #### ############# ## ### #### ########## ######## #### ########.",
  "oldpasswd-notice"       => "### #### ### ######## # ### ########. ###### ### #####.",
  "passwdchange-notice"    => "### #### ############ ####### #### ########.",
  
// logs.php
  "confirmtruncate"        => "<h1>######## ####?</h1><br />
                               <p><b>### ### #### ###'# #### ## ######## ### ####?</b> #### #### ###### ### ### ###### ## ### ######## ###### ### ####
		                       ###### ### ######. #### ###### ## ### ##########!",
  "truncatesuccess-notice" => "### #### #### #### #########",
  "TruncateLogs"           => "######## ####",
  "Timestamp"              => "#########",
  "Severity"               => "########",
  "Message"                => "#######",
  "nologs"                 => "## #### #### #### ######### ###.",
  
// panel.php
  "ControlPanel"    => "####### #####",
  "ManageUsers"     => "###### #####",
  "UpdateProfile"   => "###### ## #######",
  "Documentation"   => "#############",
  "DiscoveredHosts" => "########## #####",
  "StaleHosts"      => "##### #####",
  "Settings"        => "########",
  "BulkImport"      => "#### ######",
  
// search.php
  "shortsearch"        => "### #### ##### # ###### ###### ## ##### ########## ## #### ## ##### ## #### #######.",
  "numericfailedscans" => "### ### #### ###### ## ###### ##### ##### ## ### ###### # ######.",
  "SearchResults"      => "<h1>###### #######</h1>
                          <p><b>### ######## ###:</b><br />",
  "generalsearchterm"  => "### %first% ##### \"%second%\" ## #### \"%search%\" %searchdescription%</p>",
  "failedscansearch"   => "### ###### ### ##### ### ###### ## ###### ##### ##### ## ####### #### ## ##### ## %search%",
  "nosearchresults"    => "## ####### #### ##### #### ####### #### ######.",
  "SubnetName"         => "###### ####",
  "NetworkAddress"     => "####### #######",
  "SubnetMask"         => "###### ####",
  "StaticsUsed"        => "####### ####",
  "Contact"            => "#######",
  "FailedScans"        => "###########",
  "Timestamp"          => "#########",
  "Severity"           => "########",
  "Message"            => "#######",
  "Block"              => "#####",
  "Name"               => "####",
  "Path"               => "####",
  "deletestatic"       => "###### ###### ##",
  "enablestalescan"    => "##### ## ###### ##### ####",
  "disablestalescan"   => "##### ## ####### ##### ####",
  "IP"                 => "##",
  "name"               => "####",
  "note"               => "####",
  "lastmodifiedby"     => "#### ######## ##",
  "contact"            => "#######",
  "failedscanscount"   => "###### ##### #####",
  "username"           => "########",
  "level"              => "#####",
  "message"            => "#######",
  "AdvancedSearch"     => "######## ######",
  "Lookfor"            => "#### ###",
  "subnets"            => "#######",
  "staticIPs"          => "###### ###",
  "logs"               => "####",
  "withaan"            => "#### #/##",
  "Searchallrecords"   => "###### ### #######",
  "Specifydaterange"   => "####### # #### #####",
  "dateFrom"           => "####:",
  "dateTo"             => "##:",
  "Exportresults"      => "###### ####### ## ### (########## #### #### ########### ############.)",
  "searchdatedesc"     => "### ### ###### ### #### ######## ####### %fromdate% ### %todate%",
  
// common.php
  "login-notice" => "### ############# ## #### ########### ######## ### ## ### ## ## ### #### #######.",
  "perms-notice" => "### ## ### #### ########## ###### ## ### #### ########. ###### ####### #### ############# ## ### ####### ### #### ####### #### #### ## #####.",
  "outofpages"   => "### ## %numofpages%",

// _settings.php
  "needadmin"        => "### #### ###### ## ##### ### #### #### ############# ###### ###### ######## ### ########## ############.",
  "ldapdeleted"      => "### #### ###### ##### \"%server%\" ### ### \"%domain%\" ###### ### #### #######.",
  "settingupdated"   => "### ####### ### #### ############ #######.",
  "nosettingupdated" => "## ######## #### #### #######.",
  "noldapsupport"    => "#### ###### #### ### ######### ####### #### ##############. ######## ############## #### ## ####.",
  "oneldapserver"    => "### ### #### ### ### #### ###### ## # ####",
  "invaliddomain"    => "####### ######",
  "defineldap"       => "###### ###### ## #### ###### ### #### ###### #####.",
  "APIkeydeleted"    => "### ### ### ### #### #######.",
  "keyactivated"     => "### ### ### ### #### #########.",
  "keyrevoked"       => "### ### ### ### #### #######.", 
  
  
// settings.php
  "Settings"          => "########",
  "General"           => "#######",
  "DefaultLanguage"   => "####### ########:",
  "Authorization"     => "#############",
  "checkperms"        => "##### ########### ### ### ######### ######",
  "readonly"          => "####-#### (#### ##### ## ###/## ########)",
  "noauthentication"  => "#### (#### ### ##############)",
  "Authentication"    => "##############",
  "DefaultAuthMethod" => "####### ############## ######",
  "Database"          => "########",
  "LDAP"              => "####",
  "Domain"            => "######",
  "LDAPServer"        => "#### ######",
  "AddLDAPServer"     => "### ## #### ######",
  "DefaultDomainName" => "####### ###### ####",
  "defaultdomaintip"  => "### ####### ###### #### ## ######## ## # ######## ## ##### #### ## ### ######## 
                         #####'# ####### \"@\" ### ### #### ## ##### #### ##############.",
  "PasswordExpire"    => "######## ##########:",
  "passwdexpiretip"   => "### ###### ## #### ###### # ######## ######. ### # ### ## ######## ##########. 
                          #### ####### ## ####### ### #### #####.",
  "MinPasswdLength"   => "####### ######## ######:",
  "passwdlengthtip"   => "####### ######## ###### #### ### ##### ## #### #####.",
  "MaxLoginAttempts"  => "####### ###### ######:",
  "maxlogintip"       => "####### ###### ##### ######## ###### ####### ## ######. #### ####### ## ######## ### 
                          ####-############# #####. ###### # ## ####### ####### ########.",
  "UserGuidance"      => "#### ########",
  "DefaultIPGuidance" => "<b>####### ## ##### ########</b> (########)",
  "DNSGuidance"       => "<b>### #######</b> (########)",
  "noserversdefined"  => "##### ### ## ####### ####### ###.",
  "APIKeyDescript"    => "### ### ###########",
  "Status"            => "######",
  "APIKeys"           => "### ####",
  "APIKey"            => "### ###",
  "AddAPIKey"         => "######## ## ### ###",
  "nokeysdefined"     => "## ### #### #### #### ######### ###.",
  "Active"            => "######",
  "Revoked"           => "#######",
  
  
// _users.php
  "userdeleted" => "%username% ### #### ####### #### ### ########.",
  "noperms"     => "### ## ### #### ########## ## ###### #### #####.",
  "PasswordSet" => "######## ###",  

// users.php
  "Users"               => "#####",
  "AddaUser"            => "### # ####",
  "nousers"             => "##### ### ## ##### ###.",
  "Telephone"           => "######### ######",
  "Email"               => "##### #######",
  "LastLogin"           => "#### #####",
  "Actions"             => "#######",
  "DeleteUser"          => "###### ####",
  "EditUser"            => "#### ####",
  "PreferredLanguage"   => "######### ########",
  "UserAccessLevel"     => "####'# ###### #####",
  "SetTempPass"         => "### ######### ########",
  "Forcedbauth"         => "##### ######## ##############",
  "Lockaccount"         => "#### #######",
  "nameconflict-notice" => "#### ######## ## ####### ## ###. ###### ###### # ###### ####.",
  "useradded-notice"    => "### #### ### #### ##### ## ### ########",

// _statics.php
  "staticdeleted"         => "### ###### ## ### #### #######.",
  "acldeleted"            => "### ### ######### ### #### #######.",
  "staletoggleon-notice"  => "##### #### ### #### ###### ##",
  "staletoggleoff-notice" => "##### #### ### #### ###### ###",

// statics.php
  "ReserveaStaticIP"  => "####### # ###### ##",
  "IPGuidance"        => "## ########",
  "ContactPerson"     => "####### ######",
  "Optional"          => "(########)",
  "nogateway"         => "#### ##### ###### ## # ###### ###### ## ###### ### #### \"####### #######\" ##### ########. #### 
                          ##### ### ## ##### ### #### ######. ###### #### #### ############# ####### #### ## ##### ## ### 
						  #### ########### ########.",
  "IPReserved"        => "#### ## ### #### ########!",
  "continuetostatics" => "######## ## ####### ####",
  "ReserveIP"         => "####### ## ##",
  "enablestalescan"   => "##### ## ###### ##### ####",
  "disablestalescan"  => "##### ## ####### ##### ####",
  "nostatics"         => "## ###### ### #### #### ######## ## #### ###### ###.",
  "ACL"               => "###", # Stands for Access Control List.
  "AddACL"            => "### ## ### #########",
  "StartingIPAddress" => "######## ## #######",
  "EndingIPAddress"   => "###### ## #######",
  "acladded-notice"   => "### ### ######### ### #### #####.",
  "DNSServers"        => "### #######",
  "Ping"              => "####",
  
// _subnets.php
  "subnetdeleted"   => "### ###### %name% (%cidr%) ### #### #######",
  "showblockspace"  => "#### ######### ## ##### ## #### ##### #######",
  "SearchIPSpace"   => "###### ### ######### ## #####",
  "Results"         => "#######",
  "IPSearchFormat"  => "### ###### #### #### ## ## ### ###### ## ###.###.#.#/## ## ###.###.#.#/###.###.#.#. 
                        ### #### ###### ## # ## ## #### ####.",
  
// subnets.php
  "subnetname-tip"        => "##### # ###### #### ### ### ###### ####. ### #### ###### ## ########### ## #### ### ###### 
                              #### ## #### ###. ### #### ###### ## ##### ### ###### ### ####### ######.",
  "subnetaddress-tip"     => "##### # ###### ## #### ######## #### ## \"###.###.#.#/##\" ## ##### # ###### #### #### ## 
                              ## \"###.###.#.#/###.###.###.#\".", # also used on a page as normal text
  "guidance-tip"          => "### ### #### # ####### #### #### ## ######## ## ### #### ###### # ###### ## ## #### 
                              ######. ### ####### ###### #### ### #### ########## #### ## ## ### ### ### #### ## 
						      ###### #### #### ## ####### ## ## ####### ###. #### ########## #### ## ##########, 
						      #### ## ### #######.",
  "AllocateaSubnet"       => "######## # ######",
  "Subnet"                => "######",
  "ACLName"               => "### ####",
  "ACLRange"              => "### #####",
  "Showsearchinstead"     => "#### ###### #######",
  "AvailableIPinBlock"    => "######### ## ##### ## \"%block_name%\"",
  "invalidacl-notice"     => "### ### ### ######## ## ### #####.",
  "invalidgateway-notice" => "### ####### ### ######## ## ### #####.",
  "subnetoverlap-notice"  => "### ###### ### ######## ######## #### # ########## ######### ###### ## ### ########.",
  "subnetadded-notice"    => "### ###### ### ######### ### #### #########.",
  "BlockSubnets"          => "\"%block_name%\" #######",
  "ModifySubnet"          => "###### ### %subnet_name% ######: %start_ip%/%mask%",
  "Movesubnet"            => "#### %subnet_name% ## # ### #####",
  "selectblock"           => "###### ### ##### ###'# #### ## #### #### ###### ####",
  "Resizesubnet"          => "###### %subnet_name%",
  "furtherpromptsahead"   => "### #### ## ######## ## ### #### #### #### #### ########### ##### ### #### #### ###### #####
                              ####### ### ###### ## ############.",
  "subnetmoved-notice"    => "### ###### ### #### ##### ## #########.",
  "invalidshrink-notice"  => "### ### ###### ### ######### ## ####### #### ### ### ###, ### ### #### ## ### ### ###. ### ### #### ## ######## #
                              ### ###### #######.",
  "staticstodelete"       => "###### ### ## %original_subnet_name% #### #### ## #######",
  "nostaticsdeleted"      => "## ###### ## ####### ############ #### ## ###### ## #### ###### ######.",
  "aclstobechanged"       => "#### ## %original_subnet_name% #### ## ######## ## #######",
  "noaclschanged"         => "## ### ####### ##### ## ########.",
  "Modification"          => "############",
  "StartingIPmodified"    => "######## ## ########",
  "EndIPmodified"         => "### ## ########",
  "ToBeDeleted"           => "## ## #######",
  "invalidgrow-notice"    => "### ###### ###'## ######### #### ## ######## ## ### ###, ###### ###### #########.",
  "subnetstomerge"        => "####### #### #### ## ###### ## ####### ###### %original_subnet_name% ##### ## #### #####",
  "nosubnetsoverlap"      => "## ######## ####### ### ########## ## ####### #### ######.",
  "confirmproceed"        => "##### ### #### ## #######?",
  "resized-notice"        => "### ###### ### #### #######.",
  "ipalreadyreserved"     => "### ## ####### ### ######## ## ####### ########.",
  
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
 "footertext" => "#######:####### &copy; 2007 - 2012 ## ### <a href=\"http://collate.info/\">##########</a><br />
	              #### ######## ## ######## ##### ### ##### ##  <a href=\"license.txt\">### ### #######</a><br />
	              #######: %version_number%",

// nav.php
  "Navigation"  => "##########",
  "Browse"      => "######",
  "LogIn"       => "### ##",
  "LogOut"      => "### ###",
  "QuickSearch" => "##### ######",
  "Hostname"    => "########",
  
// command.php
  "uploadwarning"  => "###### ####### ### <a href=\"http://code.google.com/p/collate-network/w/list\">#############</a> 
                       ### ######## ## ### ###### ## ### ### ##### #### #### ########.",
  "SelectFile"     => "<b>###### # ####</b> (## ## %bytes% ##### ####)",
  "filesizenote"   => "### #### #### ##### ## ### ######## ## ### ######### ###### ## #### ###.###: ####_###_####, ######_###_########, ### ######_#####",
  "erroroccured"   => "## ##### ### #######",
  "uploadtoobig"   => "### ######## #### ### ### #####.",
  "partialupload"  => "### ######## #### ### #### ######### ########.",
  "noupload"       => "## #### ### ########.",
  "notmpfolder"    => "####### # ######### ######.",
  "diskwritefail"  => "###### ## ##### #### ## ####.",
  "extensionfail"  => "#### ###### ####### ## #########.",
  "unknownerror"   => "####### ###### #####.",
  "tryagain"       => "## #### ### ### #####",
  "erroronrow"     => "##### ## ### %rownum%: %error%",
  "invalidrecord"  => "####### ###### ####.",
  "badfieldcount"  => "####### ###### ## ###### ### #### ###### ####.",
  "manyimporterr"  => "<b>####:</b> #### ### ##### ## ########## ###### ### #########.",
  "blocknotfound"  => "### ##### ### ######### #### ### #####.",
  "subnetnotfound" => "### ###### ## ### ######### ### ### #### ###### # ###### ## ### ########.",
  "aclmatch"       => "### ## ####### ### ######## ## ### ######### ### ## ## ###.",
  "badencoding"    => "#####. #######:####### #### ######## #####-####### ### ##### ##### ###.",
  "importsuccess"  => "### #### ############ ######## %rows% #######!",
);
?>