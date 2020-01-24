<?php

    require_once("config.php");
    error_reporting(-1);
	$request_data = $_POST;

	$action = (isset($request_data['action']) && $request_data['action']!='') ? $request_data['action'] : NULL;

	$res_array = array();
	$data_array = array();
	$status = false;
	$message = "Something went wrongs.";

    if($action == 'register'){
		/*echo '<pre>'; print_r($request_data); exit;*/
        extract($request_data);
        $first_name = filtering($first_name, 'input', 'string');
        $last_name = filtering($last_name, 'input', 'text', '');
        $email_address = filtering($email_address, 'input', 'text', '');
        $password = filtering($password, 'input', 'text', '');
        $confirm_password = filtering($confirm_password, 'input', 'text', '');

		if($first_name != '' && $last_name != '' && $email_address != '' && $password != '' && $confirm_password != ''){

		    $userQry = "SELECT id FROM tbl_users WHERE email_address = '".$email_address."' ";

		    $userExist = $db->pdoQuery($userQry)->affectedRows();

		    if($userExist > 0){

			    $status = false;
			    $message = "Email you have entered is already registered.";

		    } else if($password != $confirm_password){

			    $status = false;
			    $message = "Password and confirm password must be same.";

		    } else {

		    	$insArray = array(
		    		"first_name" => $first_name,
		    		"last_name" => $last_name,
		    		"email_address" => $email_address,
		    		"password" => md5($password),
		    		"is_active" => "n",
		    		"created_date" => date('Y-m-d H:i:s')
		    	);

		    	$userId = $db->insert("tbl_users", $insArray)->getLastInsertId();

		    	if($userId > 0){

			        $status = true;
			        $message = "Your account has been registered successfully. Please check your mail to activate account.";

		    	} else {

				    $status = false;
				    $message = "Something went wrongs while registering account.";

		    	}

		    }

		} else {

		    $status = false;
		    $message = "Please provide all required details.";

		}

    } else if($action == 'emailexist'){
    	extract($request_data);

    	$email = filtering($email, 'input', 'string');
    	if($email != ''){

		    $userQry = "SELECT id FROM tbl_users WHERE email = '".$email."' ";

		    $userExist = $db->pdoQuery($userQry)->affectedRows();

		    if($userExist > 0){

			    $status = true;
			    $message = "Email you have entered is already registered.";
			    $data_array['email_exist'] = 'y';

		    } else {

			    $status = true;
			    $message = "Email not registered.";
			    $data_array['email_exist'] = 'n';

		    }

    	} else {
		    $status = false;
		    $message = "Please provide email id.";
    	}

    } else {

		$status = false;
		$message = "Something went wrongs.";

	}

	$res_array['status'] = $status;
	$res_array['message'] = $message;
	/*$res_array['data'] = $data_array;*/
	if($status){
		$res_array['data'] = $data_array;
	} else {
		$res_array['data'] = null;
	}

    $res_json = json_encode($res_array);
   	echo $res_json;
   	exit;

?>