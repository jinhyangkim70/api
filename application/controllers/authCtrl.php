<?php
require APPPATH . '/libraries/REST_Controller.php';

//
use \Firebase\JWT\JWT;
class AuthCtrl extends REST_Controller {
	public function login_post() {
        date_default_timezone_set('UTC');

		$mb_id = $this->post ( 'mb_email' );
		$mb_password = $this->post ( 'mb_password' );
		$msg = "";

		// if (! trim ( $mb_id ) || ! trim ( $mb_password ))
		// $msg = $lang ['com_4'];

		$this->load->model ( 'auth' );

		$mb = $this->auth->getUser ( $mb_id );
		$tmp_flag = true;

		if (! $mb) {
			$msg = 'login_10';
            // $msg = 'not foud id';

			$this->response ( array (
					'errorTranslateKey' => $msg
			), 401);
		} else {
			$password = $this->auth->password ( $mb_password );

			$now = now ( 'Asia/Seoul' );

			if ($mb->mb_intercept_date && $mb->mb_intercept_date <= date ( "Ymd", $now )) {
				// 차단된 아이디인가?
				$date = preg_replace ( "/([0-9]{4})([0-9]{2})([0-9]{2})/", "\\1년 \\2월 \\3일", $mb->mb_intercept_date );
				$msg = 'com_5';
				// "회원님의 아이디는 접근이 금지되어 있습니다.\\n\\n처리일 : $date";
				$this->response ( array (
						'errorTranslateKey' => $msg
				), 401 );
			} else if ($mb->mb_leave_date && $mb->mb_leave_date <= date ( "Ymd", $now )) {
				// 탈퇴한 아이디인가?
				$date = preg_replace ( "/([0-9]{4})([0-9]{2})([0-9]{2})/", "\\1년 \\2월 \\3일", $mb->mb_leave_date );
				$msg = 'com_6';
				// "탈퇴한 아이디이므로 접근하실 수 없습니다.\\n\\n탈퇴일 : $date";
				$tmp_flag = false;
				$this->response ( array (
						'errorTranslateKey' => $msg
				), 401 );
			} else if ($mb->mb_password !== $password) {
				$msg = 'login_10';

				$this->response ( array (
						'errorTranslateKey' => $msg
				), 401 );
            } else if (!preg_match("/[1-9]/", $mb->mb_email_certify)){
                // 회원 가입후 이메일 인증을 받았는지 확인
                $msg = 'com_7';
                $this->response ( array (
                        'errorTranslateKey' => $msg
                ), 401 );
			} else {
				$jwt = setUserSessionData ( array(
						'mb_no' => $mb->mb_no,
                        'mb_lang' => $mb->mb_lang
				) );

				$this->response ( array (
						'token' => $jwt
				) );
			}



		}
	}

public function urlLogin_post() {
	$this->load->model ( 'auth' );
  $this->load->model ( 'user' );

	$mb_9 = $this->post ( 'id' );
	$msg = "";

	$mb = $this->auth->getUserBykey ( $mb_9 );

  // date_default_timezone_set('UTC');

	// error_log('TEST : '. $mb_9);
	// error_log('TEST member: '. json_encode($mb));
  if ($mb_9 && $mb) {
		// $result = $this->user->updateProfile($mb->mb_no, array('mb_email_certify' => date("Y-m-d H:i:s")));

		$jwt = setUserSessionData( array(
              'mb_no' => $mb->mb_no,
              'mb_lang' => $mb->mb_lang
          ));

		$this->response ( array ('token' => $jwt));
  } else {
      // $msg = 'login_10';
      $msg = 'login_fail';

      $this->response ( array ('errorTranslateKey' => $msg), 401 );
    }
}



    public function facebookLogin_post() {
        // $this->load->model ( 'auth' );
        $this->load->model ( 'user' );

		$mb_id = $this->post ( 'id' );
		$msg = "";

        $mb = $this->user->get_member($mb_id, 'mb_id, mb_no, mb_intercept_date, mb_leave_date, mb_lang');

        if ($mb_id && $mb) {
			$jwt = setUserSessionData( array(
                'mb_no' => $mb->mb_no,
                'mb_lang' => $mb->mb_lang
            ));

			$this->response ( array (
					'token' => $jwt
			) );
        } else {
            // $msg = 'login_10';
            $msg = 'login_fail';

            $this->response ( array (
                    'errorTranslateKey' => $msg
            ), 401 );
        }
	}


	function me_post() {
		$this->load->model ( 'auth' );
        $this->load->model ( 'lecture' );
        $this->load->model ( 'user' );

		$userSession = userSessionData ();

        // error_log('userSession :: '.json_encode($userSession));

        if (!empty($userSession) && $userSession->mb_no) {
            $user = $this->auth->getUserByNo ( $userSession->mb_no );

            // error_log('=======================================================');
            //
            // // current date + 24houre 일자가 지난 강의요청들을 찾아서 강의 취소 상태로 변경.
            // $reuest_lecture_list = $this->lecture->getLectureListByPage ( $user->mb_no, $user->mb_level, 1, 0, "REQUEST");
            // $reuest_lecture_list = $reuest_lecture_list['data'];
            //
            // $str_today = date ( 'Y-m-j H:i' );
            // $str_today = strtotime ( $str_today ) + 6400;
            //
            // $past_lecture_list = array();
            // foreach ($reuest_lecture_list as $req_lecture) {
            //     if ($req_lecture['lr_tsd_strtime'] < $str_today) {
            //         array_push ( $past_lecture_list, $req_lecture);
            //     }
            // }
            //
            // error_log('past lecture count'. count($past_lecture_list));
            //
            // $lr_can_dt = date("Y-m-d H:i:s");
            // // 시간이 지난 수업들 취소 처리
            //
            // foreach ($past_lecture_list as $key => $lecture_detail) {
            //
            //     if($key < 1) {
            //     error_log('key = '.$key);
            //     error_log(json_encode($lecture_detail));
            //     // as $key => $value
            //
            //
            //     $lr_seq = $lecture_detail['lr_seq'];
            //     $lr_real_koin = $lecture_detail['lr_real_koin'];
            //     $data_params;
            //     $where_params;
            //
            //     $temp_str = '강사 취소';
            //     $data_params = array('lr_status' => 'D', 'lr_can_dt' => $lr_can_dt, 'lr_can_memo_txt' => '');
            //     $where_params= array('lr_seq' => $lr_seq);
            //
            //     error_log('cancel lecture ');
            //     error_log('data  '.json_encode($data_params));
            //     error_log('condition  '.json_encode($where_params));
            //     // 강의 취소
            //     $this->lecture->updateReservation($data_params, $where_params);
            //
            //     // 환불 처리
            //     $data_params = array(
            //         'tp_mb_no' => $lecture_detail['lr_mb_no'],
            //         'tp_lr_seq' => $lr_seq,
            //         'tp_mode' => 'I',
            //         'tp_in_koin' => $lr_real_koin,
            //         'tp_reg_dt' => $lr_can_dt,
            //         'tp_memo' => 'Cancel for the lecture.',
            //     );
            //     $this->lecture->insertPayment($data_params);
            //
            //     $data_params = array(
            //         'ph_lr_seq' => $lr_seq,
            //         'ph_memo' => $temp_str,
            //         'ph_reg_dt' => $lr_can_dt
            //     );
            //
            //     $this->lecture->insertPaymentHistory($data_params);
            //
            //     // 스케줄 비움 처리 필요할까>
            //     $this->lecture->emptyScheduleDay($lecture_detail['lr_lec_mb_no'], $lecture_detail['lr_tsd_strtime']);
            //
            //     // 패키지에 속한 강의 일때 remain_package을 증가 시켜줌
            //     if ($lecture_detail['lr_tlp_id']) {
            //         $package = $this->lecture->getPackageDetail($temp_reservation->lr_tlp_id);
            //
            //         $package->tlp_remain_count++;
            //         $this->lecture->updatePackage(
            //                 array('tlp_remain_count' => $package->tlp_remain_count),
            //                 array('tlp_id' => $temp_reservation->lr_tlp_id, 'tlp_status' => 'S')
            //         );
            //     }
            //
            //
            //     // error_log('lr_lec_mb_no :  '.$lecture_detail['lr_lec_mb_no']);
            //
            //     $tempTeacher = $this->user->simpleProfile($lecture_detail['lr_lec_mb_no']);
            //
            //     // error_log('$tempTeacher :  '.$tempTeacher->mb_nick);
            //     $teacher_name = $tempTeacher->mb_nick;
            //     // noti 학생
            //     // sendSystemMemo('03', $lecture_detail['lr_mb_no'], $teacher_name, $lecture_detail['lr_tsd_strtime'], '', $lr_seq, 0);
            //     // noti 강사
            //     // sendSystemMemo('22', $lecture_detail['lr_lec_mb_no'], $teacher_name, $lecture_detail['lr_tsd_strtime'], '', $lr_seq, 0);
            //     }
            // }





    		$this->response ( array(
    				'mb_nick' => $user->mb_nick,
    				'mb_profile' => $user->mb_profile,
    				'mb_level' => (int)$user->mb_level,
    				'mb_email' => $user->mb_email,
    				'mb_comm_tool' => $user->mb_comm_tool,
    				'mb_skypeId' => $user->mb_skypeId,
    				'mb_birth' => $user->mb_birth,
    				'mb_sex' => $user->mb_sex,
    				'mb_time' => $user->mb_time,
    				'mb_engStudy' => $user->mb_engStudy,
    				'mb_korLevel' => $user->mb_korLevel,
    				'mb_thema' => $user->mb_thema,
                    'mb_no' => $user->mb_no,
                    'mb_lorder_date' => $user->mb_lorder_date,
    		) );
        } else {
            $this->response (array('isGuest' => true ));
        }
	}

    // 2 일반회원, 3강사신청, 5 일반강사, 6 스타강사, 7 전문강사
    function newUserByEmail_post() {
        $this->load->model ( 'user' );
        $mb_email = trim(mysql_real_escape_string($this->post('mb_email')));
        $password = trim(mysql_real_escape_string($this->post('password')));
        $mb_level = $this->post('mb_level');
        $mb_time = $this->post('mb_time');
        $mb_tmp = $this->user->get_member($mb_email);
        $result = 'fail';
	$message = '';

        if ($mb_tmp) {
            // $message = 'Email is already in use';
            $message = 'front_2';
            // error_log('Email is already in use');

            if (function_exists("date_default_timezone_set"))
                date_default_timezone_set("Asia/Seoul");

            if ($mb_tmp->mb_leave_date != '' && $mb_tmp->mb_leave_date <= date("Ymd")) {
                // $message = 'No access since it is quitted ID.';
                $message = 'com_6';
            }
        } else {
            // error_log('new email');
            $mb_no = $this->user->insertUser($mb_email, $password, $mb_level, $mb_time);
            sendConfirmMail($mb_email, $mb_level);
            $mb_no = $this->user->checkInviteUser($mb_email);

            $message = 'email send';
            $result = 'success';
        }

        $this->response ( array('result'=>$result, 'message' =>$message));
    }

    function sendConfirmMail_post() {
        $this->load->model ( 'user' );
        $mb_email = trim(mysql_real_escape_string($this->post('mb_email')));

	// email로 가입한 회원만 쓰는 기능
	$mb_tmp = $this->user->get_member($mb_email);
        // $mb_level = $this->post('mb_level');
	$mb_level = $mb_tmp->mb_level;

        sendConfirmMail($mb_email, $mb_level);
        $this->response ( array('result'=>'success', 'message' =>'email send'));
    }


    public function newUserByFacebook_post() {
        $this->load->model ( 'user' );

        $user = $this->post('user');
        $mb_level = $this->post('mb_level');
        $mb_time = $this->post('mb_time');
        $result = 'fail';
        $message = '';

        // error_log('$user'.json_encode($user));
        // $mb_id = "fb_".$user['id'];
        // $mb_tmp = $this->user->get_member($mb_id);
        // 페이스북 id가 아닌 email로 검색
        $mb_tmp = $this->user->get_member($user['email']);

        if ($mb_tmp) {
            // $message = 'Email is already in use';
            // $message = 'front_2';
						$message = 'ik-additional_11'; //If you already have an account, please log in.

            if (function_exists("date_default_timezone_set")) {
                // date_default_timezone_set("Asia/Seoul");
                date_default_timezone_set('UTC');
            }

            if ($mb_tmp->mb_leave_date != '' && $mb_tmp->mb_leave_date <= date("Ymd")) {
                // $message = 'No access since it is quitted ID.';
                $message = 'com_6';
            }
        } else {
            // error_log('new email');

            // save profil image
            $IMG_ROOT = 'image_profile';
            // $IMG_ROOT = 'image_test';

            $imgURL = 'http://graph.facebook.com/'.$user['id'].'/picture?type=square&width=93&height=93';
            $img = file_get_contents($imgURL);
            $dest_path = $IMG_ROOT . '/fb_' . $user['id'] . '.jpg';

            # delete file if exists
            if (file_exists($dest_path)) { unlink ($dest_path); }

            file_put_contents($dest_path, $img);

            $mb_no = $this->user->insertFacebookUser($user,'/'.$dest_path, $mb_level, $mb_time);
            sendConfirmMail($user['email'], $mb_level);
            $mb_no = $this->user->checkInviteUser($user['email']);

            $message = 'email send';
            $result = 'success';
        }

        $this->response ( array('result'=>$result, 'message' =>$message));
    }


    function findUser_post() {
        $this->load->model ( 'user' );

        $mb_id = $this->post('id');

        $mb_tmp = $this->user->get_member($mb_id, 'mb_id, mb_no, mb_intercept_date, mb_leave_date');
        $this->response ( $mb_tmp );
    }
}
