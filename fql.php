<?php

require 'secrets.php'; // facebook secrets
require 'facebook-php-sdk/src/facebook.php';

$facebook = new Facebook(array(
  'appId'  => FACEBOOK_APP_ID,
  'secret' => FACEBOOK_SECRET,
));

// Get User ID
$user = $facebook->getUser();


print_r($user);

// Use fql to get the ids of the users friends
function get_friends(){
	global $facebook;
	$fql = "select uid, name from user where uid in (SELECT uid2 FROM friend WHERE uid1 = me()) ";

	$response = $facebook->api(array(
		'method' => 'fql.query',
		'query' =>$fql,
	));		
	
	return $response;	
}

// Create an array of batches queries for the mutual friends. Each query gets the user
// ids of the mutual friends between the user and one of his friends
function get_queries($response){
	$queries = array(); 
	$format = 'select uid1 from friend where uid2 = %s and uid1 in (select uid2 from friend where uid1 = me())';

	foreach($response as $idx => $dict){
		$fid = $dict['uid'];
		$q = sprintf($format, $fid, $fid);
		$q = str_replace(' ', '+', $q);
		$cur = array('method'=>'GET', 'relative_url'=>'method/fql.query?query='.$q);	
		$queries []= $cur;
	}
	
	return $queries;	
}

// Comparison function based on mutual friend count
function cmp($a, $b){
	if($a['count'] == $b['count']) return 0;
	return ($a['count'] < $b['count']) ? 1 : -1;
}



if ($user) {
  try {
	// Proceed knowing you have a logged in user who's authenticated.
	$user_profile = $facebook->api('/me');
	$mutual_friends = $facebook->api('/me/mutualfriends/678561285');
	$next = $mutual_friends['pagin']['next'];

	$num = count($mutual_friends['data']);
	echo $num;

//	$xx = $facebook->api('/1141800361/mutualfriends?user=1207059&limit=5000&offset=5000&__after_id=214707');
//	print_r($xx);

//	print_r($mutual_friends);

	// echo "<pre>";
	// $response = get_friends();

	// //array_splice($response, 50);

	// $queries = get_queries($response);

	// $BATCH_SIZE = 50;

	// $batches = array_chunk($queries, $BATCH_SIZE);

	// $i = 0;
	// foreach($batches as $batch){
	// 	try{
	// 		$mutual = $facebook->api('/?batch='.json_encode($batch), 'POST');
	// 		foreach($mutual as $idx => $dict){
	// 			$body = $dict['body'];
	// 			$arr = json_decode($body);
	// 			$response[$i*$BATCH_SIZE + $idx]['count'] = count($arr);
	// 		}

	// 	}catch(FacebookApiException $e){
	// 		print_r($e);
	// 	}

	// 	$i++;

	// }


	// uasort($response, 'cmp');


	echo "</pre>";

  } catch (FacebookApiException $e) {
    error_log($e);
    $user = null;
  }
}

// Login or logout url will be needed depending on current user state.
if ($user) {
  $logoutUrl = $facebook->getLogoutUrl();
} else {
  $loginUrl = $facebook->getLoginUrl();
}





?>

<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
  <head>
    <title>most mutual friends</title>
    <style>
      body {
        font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
      }
      h1 a {
        text-decoration: none;
        color: #3b5998;
      }
      h1 a:hover {
        text-decoration: underline;
      }
    </style>
  </head>
  <body>
    <h1>Most Mutual Friends</h1>

    <?php if ($user): ?>
      <a href="<?php echo $logoutUrl; ?>">Logout</a>
    <?php else: ?>
      <div>
        <a href="<?php echo $loginUrl; ?>">Login with Facebook</a>
      </div>
    <?php endif ?>

    <?php if ($user): ?>
      <h3>You</h3>
      <img src="https://graph.facebook.com/<?php echo $user; ?>/picture">
    <?php else: ?>
      <strong><em>You are not Connected.</em></strong>
    <?php endif ?>

<pre>

<?php 

// foreach($response as $item){
// echo $item['name'] . " " . $item['count'] . "\n";
// }

?>

</pre>
		
  </body>
</html>
