<?php
require APPPATH.'/libraries/REST_Controller.php';

//
class UserCtrl extends REST_Controller {

    var $me;

    function __construct() {
        // Call the Model constructor
        parent::__construct();

        //
        $this->load->model ( 'auth' );
        $this->load->model ( 'user' );

        // date_default_timezone_set($this->me->mb_time);
    }

    private function getUserSession() {
        $userSession = userSessionData ();
        if (!empty($userSession)) {
            $this->me = $this->auth->getUserByNo ( $userSession->mb_no );
        }

        return $userSession;
    }

    function profile_post() {
        $this->getUserSession();
        $result = $this->user->profile($this->me->mb_no);

        $this->response($result);
    }

    function saveUserProfile_post() {
        $this->getUserSession();
        $params = $this->post('userInfo');

        // error_log('update user params  : '.json_encode($params));
        $result = $this->user->updateProfile($this->me->mb_no, $params);
        
       
        $this->response($result);
    }

    function checkValidProfile_post() {
        $this->getUserSession();

        $result = $this->user->checkValidProfile($this->me->mb_no);
        $this->response(array('validProfile' => $result));
    }

    function saveUserAbility_post() {
        $this->getUserSession();
        $params_list = $this->post('ability');

        $result = $this->user->updateAbility($this->me->mb_no, $params_list);
        $this->response($result);
    }

    function saveUserCarrer_post() {
        $this->getUserSession();
        $params_list = $this->post('carrer');
        $result = $this->user->updateCarrer($this->me->mb_no, $params_list);
        $this->response($result);
    }

    public function certifyImageUpload_post() {
        $newName = $_GET["filename"];
        // error_log('file name : '.$filename);


        $FILE_ROOT = 'image_profile';

		if(isset($_FILES['file'])){

            // error_log('file contain');

		    //The error validation could be done on the javascript client side.
		    $errors= array();

		    //$file_name = uniqid('img_');
		    $file_name = $_FILES['file']['name'];

			//error_log("1111 $file_name");
		    $file_size =$_FILES['file']['size'];
		    $file_tmp =$_FILES['file']['tmp_name'];
		    $file_type=$_FILES['file']['type'];
		    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // error_log('file name : '.$newName);
            // error_log('file_size : '.$file_size);
            // error_log('file_tmp : '.$file_tmp);
            // error_log('file_type : '.$file_type);

            // byte :  max 20MB
            if($file_size > (20 * 1024 * 1024)){
		    	$errors[]='File size cannot exceed 20 MB';
		    }
			// $newName = uniqid('file_');
            $file_name = $newName.'.'.$file_ext;

		    if(empty($errors)==true){
                $temp_path = $FILE_ROOT."/".$file_name;
		        move_uploaded_file($file_tmp, $temp_path);
				//$full_path =" uploaded file: " . "images/" . $file_name;;
                chmod($temp_path, 0644);
				$this->response ( array (
					'file_name' => "/".$FILE_ROOT."/".$file_name
				));
		    }else{
		        print_r($errors);
		        $this->response ( array (
		        		'errors' => $errors
		        ));
		    }
		}
	}

    function account_post() {
        $this->getUserSession();
        $result = $this->user->account($this->me);

        $this->response($result);
    }

    // 강사/학생의 공통 프로필 정보
    function profile_get() {
        $userSession = $this->getUserSession();
        $seq = $this->get('seq');

        $userMB = $this->auth->getUserByNo ( $seq );
        $result = $this->user->profile($userMB->mb_no);

        $fav = false;
        $isPossibleTrial = false;

        if (!empty($userSession)) {
            // 즐겨찾기한 강사인가?
            $fav = $this->user->getFav($userMB->mb_no, $this->me->mb_no);

            // 시범강의가 가능한지?
            $isPossibleTrial = $this->user->isTrialPossible($userMB->mb_no, $this->me->mb_no);
        }

        $themeList = $this->user->getThemeList($userMB);

        $this->response(array(
            'seq' => $seq,
            'mb_profile' => $userMB->mb_profile,
            'mb_time' => $userMB->mb_time,
            'mb_nick' => $userMB->mb_nick,
            'mb_level' => $userMB->mb_level,
            'mb_signature' => $userMB->mb_signature,
            'mb_lang' => $userMB->mb_lang,
            'mb_lang2' => $userMB->mb_lang2,
            'str_mb_lang' => $userMB->str_mb_lang,
            'str_mb_lang2' => $userMB->str_mb_lang2,
            'str_mb_nation' => $userMB->str_mb_nation,
            'ta_score' => $userMB->ta_score,
            'str_star' => $userMB->str_star,
            'student_count' => $userMB->student_count,
            'lecture_count' => $userMB->lecture_count,
            'fav' => $fav,
            'isPossibleTrial' => $isPossibleTrial,
            'profile' => $result,
            'themeList' => $themeList,
            'mb_status' => $userMB->mb_status,
        ));
    }
  
    // 강사 프로필의 강의및 추가 정보
    function profileLecture_post() {
        $seq = $this->post('seq');

        $result = $this->user->profileLecture($seq);

        $this->response($result);
    }

    // 강사의 후기
    function profileReply_post() {
        $seq = $this->post('seq');

        $result = $this->user->profileReply($seq);
        $this->response($result);
    }

    // 국가 목록
    function getNationList_post() {
        $ret = $this->user->getNationList();

        $this->response($ret);
    }

    // user list
    function getUserList_post() {

        $index = $this->post('page_index');
        $count = $this->post('page_count');
        $nick_name = $this->post('nick_name');

        $ret = $this->user->getFindUser($index, $count, $nick_name);

        $this->response($ret);
    }

    public function imageBinaryUpload_post() {
        $ret = 'fail';

        $IMG_ROOT = 'image_profile';

        $this->getUserSession();

        if (isset($GLOBALS["HTTP_RAW_POST_DATA"]))
        {
            // save image file
            $prefix_no = $this->me->mb_no;
            $file_name = uniqid($prefix_no.'_');
            $file_path = $IMG_ROOT.'/'.$file_name.'.jpg';

            // Get the data
            $imageData=$GLOBALS['HTTP_RAW_POST_DATA'];

            // Remove the headers (data:,) part.
            // A real application should use them according to needs such as to check image type
            $filteredData=substr($imageData, strpos($imageData, ",")+1);

            // Need to decode before saving since the data we received is already base64 encoded
            $unencodedData=base64_decode($filteredData);

            //echo "unencodedData".$unencodedData;

            // Save file. This example uses a hard coded filename for testing,
            // but a real application can specify filename in POST variable
            $fp = fopen( $file_path, 'wb' );
            fwrite( $fp, $unencodedData);
            fclose( $fp );

            // remove file
            if (isset($this->me->mb_profile)) {
                // $find_index = strpos($this->me->mb_profile, "/");
                $temp_path = substr($this->me->mb_profile, 1);
                // error_log('temp_path'.$temp_path);
                if (file_exists($temp_path)) {
                    unlink($temp_path);
                }
            }

            // update db
            // path 기존과 맞춤
            $this->user->updateMember(
                array('mb_no' => $this->me->mb_no),
                array('mb_profile' => '/'.$file_path)
            );

            $ret = '/'.$file_path;
        }

        $this->response($ret);
    }

    public function updateProfile_post() {
        $ret = 'fail';

        // $this->user->updateMember(
        //     array('mb_no' => $this->me->mb_no),
        //     array('mb_profile' => '/'.$file_path)
        // );

        $this->response($ret);
    }

    public function updatePassword_post() {
        $this->getUserSession();
        $old_password = $this->post('oldPass');
        $new_password = $this->post('newPass');

        $ret = $this->user->updatePassword($old_password , $new_password, $this->me->mb_no);
        $this->response($ret);
    }

    public function initPassword_post() {
        $email = $this->post('email');

        $ret = $this->user->initPassword($email);
        $this->response($ret);
    }

    // 사용자 차단
    public function blockUser_post() {
        $this->getUserSession();
        $block_mb_no = $this->post('mb_no');

        $result = $this->user->addBlockUser($this->me->mb_no, $block_mb_no);
        $this->response($result);
    }

    public function blockList_post() {
        $mb_no = $this->post('mb_no');

        $result = $this->user->blockList($mb_no);
        $this->response($result);
    }

    public function twoWayBlockList_post() {
        $mb_no = $this->post('mb_no');

        $result = $this->user->twoWayBlockList($mb_no);
        $this->response($result);
    }

    public function removeBlock_post() {
        $this->getUserSession();
        $block_mb_no = $this->post('mb_no');

        $result = $this->user->removeBlock($this->me->mb_no, $block_mb_no);
        $this->response('success');
    }


    // 사용자 신고
    public function declareReport_post() {
        $this->getUserSession();
        $declare_mb_no = $this->post('mb_no');
        $tdr_memo = $this->post('memo');

        $result = $this->user->addDeclareReport($this->me->mb_no, $declare_mb_no, $tdr_memo);
        $this->response($result);
    }

    // 강사 광고
    public function getRandomAd_post() {
        $result = $this->user->getRandomAd();
        $this->response($result);
    }

    // 친구 초대
    public function inviteFriends_post() {
        $this->getUserSession();
        $email = $this->post('email');

        $result = $this->user->inviteFriends($this->me->mb_no, $email);

        if ($result['msg'] == 'fail') {
            $this->response(array('msg' => 'fail'));
        } else {
            // send mail
            $ROOT_URL = TUTORK_ROOT_URL;

            $member = $this->user->simpleProfile($this->me->mb_no);
            // $mail_subject="[TutorK] 친구 초대";

            // 제목때문에 스팸처리됨
            $mail_subject="[TutorK] Invite a friend";
            $mail_content_r="<p>{$member->mb_nick} 이 온라인 한국어 회화 매칭 플랫폼, Tutor-K에 당신을 초대했습니다. 재미있게 한국어를 배워보세요. </p>";
            $mail_content_r=$mail_content_r."<br/>";
            $mail_content_r=$mail_content_r."<p>{$member->mb_nick} invited you to an online Korean conversation platform Tutor-K. Enjoy learning Korean with native Korean teachers!</p>";
            $mail_content_r=$mail_content_r."<br/>";
            $mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"$ROOT_URL\"></a></p>";
            sendMail($email, $mail_subject, $mail_content_r);

            // $mail_subject="[TutorK] F";
            // $mail_content_r="<p>{$member->mb_nick} 이 온라인 한국어 회화 매칭 플랫폼, Tutor-K에 당신을 초대했습니다. 재미있게 한국어를 배워보세요. </p>";
            // $mail_content_r=$mail_content_r."<br/>";
            // $mail_content_r=$mail_content_r."<p>{$member->mb_nick} invited you to an online Korean conversation platform Tutor-K. Enjoy learning Korean with native Korean teachers!</p>";
            // $mail_content_r=$mail_content_r."<br/>";
            // $mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"$ROOT_URL\"></a></p>";
            // sendMail("ms08you@gmail.com", $mail_subject, $mail_content_r);

            $this->response(array('msg' => 'success'));
        }
    }

    public function myInviteList_post() {
        $this->getUserSession();
        $result = $this->user->myInviteList($this->me->mb_no);
        $this->response($result);
    }


    public function getTimezoneList_post() {
        // error_log("constant test : TUTORK_ROOT_URL");
    	$zones_array = array();
    	$timestamp = time();
    	foreach(timezone_identifiers_list() as $key => $zone) {
    		date_default_timezone_set($zone);
    		$zones_array[$key]['zone'] = $zone;
    		$zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
    	}

        $this->response($zones_array);
    }
}
