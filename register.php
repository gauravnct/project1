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

    } else if($action == 'linkedinloginsignup'){
        extract($request_data);

        $firstName = filtering($firstName, 'input', 'string');
        $lastName = filtering($lastName, 'input', 'string');
        $email = filtering($email, 'input', 'string');
        $pass = generatePassword(8);
        $deviceToken = filtering($deviceToken, 'input', 'text');
        $activationCode = base64_encode(time());
        $forgotPassCode = md5(genrateRandom(8));

		if($firstName != '' && $lastName != '' && $email != '' && $pass != '' && $deviceToken != ''){

		    $userRegQry = "SELECT id FROM tbl_users WHERE email = '".$email."' ";

		    $userRegExist = $db->pdoQuery($userRegQry)->affectedRows();

		    if($userRegExist > 0){

		    	$userRegDetail = $db->pdoQuery($userRegQry)->result();

		    	$userId = $userRegDetail['id'];

		    } else {

		    	$insArray = array(
		    		"first_name" => $firstName,
		    		"last_name" => $lastName,
		    		"email" => $email,
		    		"password" => md5($pass),
		    		"ip_address" => get_ip_address(),
		    		"is_linked_in_verify" => "y",
		    		"status" => "a",
		    		"is_active" => "y",
		    		"email_activation_code" => $activationCode,
		    		"forgot_pass_code" => $forgotPassCode,
		    		"created_date" => date('Y-m-d H:i:s'),
		    	);

		    	if($image != ''){
		            $upload_dir = DIR_UPD.'temp/';
		            if(!file_exists($upload_dir )){
		                mkdir($upload_dir,0777);
		            }
			        $img = $image;
			        $imgNm = md5(time().rand());
			        $image = $imgNm . '.jpg';
			        $content = file_get_contents($img);
			        file_put_contents($upload_dir.$image, $content);

		            $imageName = $image;
		            $TmpName  = $upload_dir.$image;
		            $ext = '.'.strtolower(getExt($imageName));
		            $newName = rand().time().$ext;

		            $upload_dir = DIR_UPD.'profile/';
		            if(!file_exists($upload_dir )){
		                mkdir($upload_dir,0777);
		            }

	                $th_arr = array();
	                $th_arr[0] = array('width' => '150', 'height' => '150');
	                $th_arr[1] = array('width' => '370', 'height' => '370');

	                $newName = GenerateThumbnail($newName,$upload_dir, $TmpName, $th_arr);
	                $profile_image = $newName;
	                $insArray['profile_image'] = $profile_image;

		    	}

		    	$userId = $db->insert("tbl_users", $insArray)->getLastInsertId();

		    	if($userId > 0){

			        manageDeviceId($userId, $deviceToken);

			        $to = $email;
			        $varArray = array(
		                'GREETINGS' => $firstName.' '.$lastName,
		                'EMAIL_ID' => $email,
		                'PASSWORD' => $pass,
		            );

			        $mailArray = generateEmailTemplate('social_signup',$varArray);
		            /*echo $mailArray['message']; exit;*/
			        sendEmailAddress($to,$mailArray['subject'],$mailArray['message']);

			        $status = true;
			        $message = "You have successfully signup for this app with linkedin.";
		    	}

		    }

		    $userQry = "SELECT u.* FROM tbl_users AS u  WHERE u.id = '".$userId."' ";

		    $userExist = $db->pdoQuery($userQry)->affectedRows();

			if($userExist > 0){

			    $userDetails = $db->pdoQuery($userQry)->result();

			    if($userDetails['is_active'] == 'n'){

			        $status = false;
			        $message = "Your email verification is pending. To activate your account you need to verify activation link sent to your email address.";

			    } else if($userDetails['status'] == 'd'){

			        $status = false;
			        $message = "Your account is deactivated by admin, please contact to admin to activate your account.";

			    } else {

			        $data_array['user_profile_details'] = getUserProfileDetails($userDetails['id']);

			        manageDeviceId($userDetails['id'], $deviceToken);

			        $status = true;
			        $message = "You are logged in successfully.";

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

    } else if($action == 'staticpages'){

	    $pageListQry = "SELECT * FROM tbl_content WHERE is_active = 'y' ORDER BY page_title ASC";

	    $pageListExist = $db->pdoQuery($pageListQry)->affectedRows();

	    if($pageListExist > 0){

			$pageListDetails = $db->pdoQuery($pageListQry)->results();

	        $i = 0;

	        foreach ($pageListDetails as $k => $v) {

				$data_array[$i]['page_id'] = $v['id'].'';

				$data_array[$i]['page_title'] = $v['page_title'].'';

				$data_array[$i]['page_desc'] = $v['page_desc'].'';

				$i++;

	        }

	        $status = true;
	        $message = "";

	    } else {

	        $status = false;
	        $message = "No records found.";

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