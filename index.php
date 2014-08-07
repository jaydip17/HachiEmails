<?php

$google_client_id 	= '269953751280-c28kad58nlpqqvsfjah24pcq9eonfr2a.apps.googleusercontent.com';
$google_client_secret 	= 'BXxxBrEz-JuiXKEIZ9Xp4Wyr';
$google_redirect_url 	= 'http://citysolution.co.in/index.php'; 
$google_developer_key 	= 'AIzaSyBbOIk4b4trL4HjpF18mk-UWgRgRu3B1c0';

$db_username = "yourvb1y_xxxx"; 
$db_password = "newxxxxxx"; 
$hostname = "localhost"; 
$db_name = 'yourvb1y_hachi'; 

require_once 'src/Google_Client.php';
require_once 'src/contrib/Google_Oauth2Service.php';

session_start();

$gClient = new Google_Client();
$gClient->setApplicationName('Hachi Email Fetcher');
$gClient->setClientId($google_client_id);
$gClient->setClientSecret($google_client_secret);
$gClient->setRedirectUri($google_redirect_url);
$gClient->setDeveloperKey($google_developer_key);
$gClient->setScopes(array('https://www.googleapis.com/auth/userinfo.email',
'https://www.googleapis.com/auth/userinfo.profile','https://www.google.com/m8/feeds/','https://www.googleapis.com/auth/calendar','https://www.googleapis.com/auth/gmail.readonly'
));

$google_oauthV2 = new Google_Oauth2Service($gClient);


if (isset($_REQUEST['reset'])) 
{
  unset($_SESSION['token']);
  $gClient->revokeToken();
  header('Location: ' . filter_var($google_redirect_url, FILTER_SANITIZE_URL)); 
}


if (isset($_GET['code'])) 
{ 
	$gClient->authenticate($_GET['code']);
	$_SESSION['token'] = $gClient->getAccessToken();
	header('Location: ' . filter_var($google_redirect_url, FILTER_SANITIZE_URL));
	return;
}


if (isset($_SESSION['token'])) 
{ 
	$gClient->setAccessToken($_SESSION['token']);
}


if ($gClient->getAccessToken()) 
{
	  $user 			= $google_oauthV2->userinfo->get();
	
	  $user_id 			= $user['id'];
	  $user_name 			= filter_var($user['name'], FILTER_SANITIZE_SPECIAL_CHARS);
	  $email 			= filter_var($user['email'], FILTER_SANITIZE_EMAIL);
	  $profile_url 			= filter_var($user['link'], FILTER_VALIDATE_URL);
	  $profile_image_url 	        = filter_var($user['picture'], FILTER_VALIDATE_URL);
	  $personMarkup 		= "$email<div><img src='$profile_image_url?sz=50'></div>";
	  $_SESSION['token'] 	        = $gClient->getAccessToken();
	  
	  $req = new Google_HttpRequest("https://www.google.com/m8/feeds/contacts/default/full?max-results=9999&alt=json"); //for contacts
	  $mailurl = new Google_HttpRequest("https://www.googleapis.com/gmail/v1/users/me/threads?maxResults=20&alt=json"); //for recent mails
	  $calurl = new Google_HttpRequest("https://www.googleapis.com/calendar/v3/users/me/calendarList?minAccessRole=writer&fields=items%2Fid&alt=json"); //for calender events
	  
	   $val = $gClient->getIo()->authenticatedRequest($req);
  	   $mails = $gClient->getIo()->authenticatedRequest($mailurl);
           $cals = $gClient->getIo()->authenticatedRequest($calurl);

	   $temp = json_decode($val->getResponseBody(),true);
           $mailids = json_decode($mails->getResponseBody(),true);
           $calids = json_decode($cals->getResponseBody(),true);


	   
}
else 
{
	//For Guest user, get google login url
	$authUrl = $gClient->createAuthUrl();
}

function search($array, $key, $value)   /* Use of this function is to help while parsing multidimensional array */
{
    $results = array();

    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }

        foreach ($array as $subarray) {
            $results = array_merge($results, search($subarray, $key, $value));
        }
    }

    return $results;
}



//HTML page start
echo '<!DOCTYPE HTML><html>';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
echo '<title>Login with Google</title>';
echo '</head>';
echo '<body>';





if(isset($authUrl)) //user is not logged in, show login button
{
	echo '<h2>A Simple ContactGrabber for GoHachi ! </h2>';
	echo '<a class="login" href="'.$authUrl.'"><img src="images/google-login-button.png" /></a>';
} 
else // user logged in 
{
   /* connect to database using mysqli */
	$mysqli = new mysqli($hostname, $db_username, $db_password, $db_name);
	
	if ($mysqli->connect_error) {
		die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
	}
	
	//compare user id in our database
	$user_exist = $mysqli->query("SELECT COUNT(google_id) as usercount FROM google_users WHERE google_id=$user_id")->fetch_object()->usercount; 
	if($user_exist)
	{
		echo '<h2>Welcome back '.$user_name.'! You are currently Logged in with Google.</h2>';
	}else{ 
		//user is new
		echo 'Hi '.$user_name.', Thanks for Registering for our App! You are currently Logged in with Google.</h2>';
		$mysqli->query("INSERT INTO google_users (google_id, google_name, google_email, google_link, google_picture_link) 
		VALUES ($user_id, '$user_name','$email','$profile_url','$profile_image_url')");
	}

	
	echo '<br /><a href="'.$profile_url.'" target="_blank"><img src="'.$profile_image_url.'?sz=100" /></a>';
	echo '<br /><a class="logout" href="?reset=1">Logout</a>';
	
	
	
	
	/*****************************************************************************************************************************/
echo "<h3> All Contacts </h3>";
echo "<hr>";	
echo "<table>";
foreach($temp['feed']['entry'] as $cnt) {
	echo "<tr>";
	echo "<td>" . $cnt['title']['$t'] . " </td><td> " . $cnt['gd$email']['0']['address'] . "</td>";
	if(isset($cnt['gd$phoneNumber'])) echo " <td>" . $cnt['gd$phoneNumber'][0]['$t'] . "</td>";
	if(isset($cnt['gd$structuredPostalAddress'][0]['gd$street'])) echo " --- " . $cnt['gd$structuredPostalAddress'][0]['gd$street']['$t'];
	if(isset($cnt['gd$structuredPostalAddress'][0]['gd$neighborhood'])) echo " --- " . $cnt['gd$structuredPostalAddress'][0]['gd$neighborhood']['$t'];
	if(isset($cnt['gd$structuredPostalAddress'][0]['gd$pobox'])) echo " --- " . $cnt['gd$structuredPostalAddress'][0]['gd$pobox']['$t'];
	if(isset($cnt['gd$structuredPostalAddress'][0]['gd$postcode'])) echo " --- " . $cnt['gd$structuredPostalAddress'][0]['gd$postcode']['$t'];
	if(isset($cnt['gd$structuredPostalAddress'][0]['gd$city'])) echo " --- " . $cnt['gd$structuredPostalAddress'][0]['gd$city']['$t'];
	if(isset($cnt['gd$structuredPostalAddress'][0]['gd$region'])) echo " --- " . $cnt['gd$structuredPostalAddress'][0]['gd$region']['$t'];
	if(isset($cnt['gd$structuredPostalAddress'][0]['gd$country'])) echo " --- " . $cnt['gd$structuredPostalAddress'][0]['gd$country']['$t'];
	echo "</tr>";
}
echo "</table></br></br></br>";


           /*****************************************************************************************************************************/

echo "<h3> Recent 20 Contacts </h3>";
echo "<hr>";
echo "<table>";
foreach($mailids['threads'] as $mail){

$messageurl = new Google_HttpRequest("https://www.googleapis.com/gmail/v1/users/me/messages/".$mail[id]."?alt=json");
$mailpayload = $gClient->getIo()->authenticatedRequest($messageurl);
$payload = json_decode($mailpayload->getResponseBody(),true);

$data = search($payload, 'name', 'From');  // Search using above prepared function

echo "<tr>";
echo "<td>" . $data[0]['value'] . "</td>";
echo "</tr>";

}
echo "</table></br></br></br>";

      /*****************************************************************************************************************************/

echo "<h3> Recently met </h3>";
echo "<hr>";
echo "<table>";

foreach($calids as $cals){
	
	if(is_array($cals)){
	
		foreach($cals as $cal){
		
		$eventurl = new Google_HttpRequest("https://www.googleapis.com/calendar/v3/calendars/".$cal['id']."/events?alwaysIncludeEmail=true&fields=items(attendees(displayName%2Cemail))&alt=json");
		$eventpayload = $gClient->getIo()->authenticatedRequest($eventurl);
		$event = json_decode($eventpayload->getResponseBody(),true);
		
		
			foreach($event['items'] as $meetings ){
			
				foreach ($meetings as $person){
				
					foreach($person as $rawcontact) {
					
						echo "<tr>";
						echo "<td>" . $rawcontact['displayName'] ."</td><td>" .$rawcontact['email']."</td>" ;
						echo "</tr>";
						
					}
				//echo $cal['id']; 
				}
			
			}
		}
	
	
	}



}	

echo "</table>";



	
		
}
 
echo '</body></html>';
?>
