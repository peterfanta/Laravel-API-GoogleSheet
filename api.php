<?php

use Illuminate\Http\Request;
use App\userList;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/getmessage', function(Request $request) {
    // $param = $request->input('data');
    // return $param;
    $message = $request->input('message');
    $title = $request->input('title');
    $id = $request->input('unique');
    $user = userList::first();
    if($user)
    {
    	  
         $effectiveDate = date('Y-m-d', strtotime("+".$user->term." Months", strtotime($user->updated_at)));

         $time = date("Y-m-d");
      // return $effectiveDate;
         if($user->id ==0)
         {
         	$trial = date('Y-m-d', strtotime("+7 days", strtotime($user->updated_at)));
	        if($trial < $time)
    	     	return;	
         }
         else
         {
         	if($effectiveDate < $time)
         	{
	         	return;	
         	}
         }  
         
    }
    else
    {
    	userList::create(['unique'=>$id]);
    }
	$message_url = 'https://m.facebook.com'.$request->input('message_url');
   	$tab_title = $request->input('tab_title');
   	$window = $request->input('window');
   	$client = new \Google_Client();
	$client->setApplicationName("aaaa");
	$client->setDeveloperKey("Your api key");
	$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
	$client->setAccessType('offline');
	  
	//$client->setHttpClient($httpClient);
	$service = new Google_Service_Sheets($client);
 	 
	// The ID of the spreadsheet to retrieve data from.
	$spreadsheetId = $request->input('sheetid'); // TODO: 



	// The A1 notation of the values to retrieve.
	$range = 'answer';  // TODO: Update placeholder value.

	$response = $service->spreadsheets_values->get($spreadsheetId, $range);

	if(!$response) 
 		return 'databaseerror';

	$messages = ($response->values);
	$answer='no';
	
 	//check if the message exist
  if (is_array($messages) || is_object($messages))
	foreach ($messages as $key) {
	 if(sizeof($key)>0)
		if( $key[0] == $title )
		{ 

			for($i=1; $i < sizeof($messages[0]); $i ++)
			{

				if($messages[0][$i] == $message)
				{
					if(($i+1) > sizeof($key))
						break;
					$answer =  $key[$i] ;
				}
			}
			
		}
	}
   	if($answer !='no')
   	{
   		return 'success:'.$answer;
   	}
    $unanswered_message ='';
    $range='unanswered';
    $un_response = $service->spreadsheets_values->get($spreadsheetId, $range);
    if(!$un_response)
    	return 'databaseerror1';
    $unanswered = $un_response->values;
 
 	$client->setAuthConfig(__DIR__.'/credentials.json');
	$service1 = new Google_Service_Sheets($client);
    //check if the unanswered message exist 
    $flag = 0;
    if (is_array($unanswered) || is_object($unanswered))
    foreach ($unanswered as $key) {
    	if(sizeof($key)>0)
		if(($key[0]==$title) &&($key[1]==$message) &&($key[2]==$message_url) &&($key[3]==$tab_title) &&($key[4]==$window) )	
		{
			$flag = 1;
			break;
		}

	}
	if($flag ==0)
	{
		if(!$title)
			$title = 'error';
		if(!$tab_title)
			$tab_title = 'error';
		if(!$window)
			$window = 'error';
		if(!$message)
			$message = 'error';
		$values = [[$title,$message, $message_url, $tab_title, $window],];
		  	 	
	    $body = new Google_Service_Sheets_ValueRange(['values'=>$values]);
	    $params = ['valueInputOption' =>'RAW'];
	    $insert = ['insertDataOption'=>'INSERT_ROWS'];
	    $result =  $service1->spreadsheets_values->append($spreadsheetId, $range, $body, $params, $insert);
	}
	
     return "success:".$answer;


});

  
