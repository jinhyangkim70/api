<?php
require APPPATH.'/libraries/REST_Controller.php';

// lesson 학생과의 실제 수업
class LessonCtrl extends REST_Controller {

	var $me;

	function __construct() {
		// Call the Model constructor
		parent::__construct();

		//
		$this->load->model ( 'auth' );
		$this->load->model('lesson');
        $this->load->model('lecture');
        $this->load->model('user');

		$userSession = userSessionData ();


		$this->me = $this->auth->getUserByNo ( $userSession->mb_no );

		// date_default_timezone_set($this->me->mb_time);
	}

	// 취소사유 route
	function cancelReason_post() {

		$lr_seq = $this->post('lr_seq');
		$lang = $this->post('lang');

		$result = $this->lesson->getCancelReason($lr_seq, $lang);

		$this->response($result);
	}

	// 강의 완료후 강의 평가 route
	function afterPoint_post() {

		$lr_seq = $this->post('lr_seq');

		$result = $this->lesson->getAfterPoint($lr_seq);

// 		$result['mb_no'] = $this->me->mb_no;

		$this->response($result);
	}

    // 학생 강의 완료후 강의 평가
    function insertComment_post() {
      $ta_lec_mb_no = $this->post('ta_lec_mb_no');
      $ta_lr_seq = $this->post('ta_lr_seq');
      $score = $this->post('score');
      $comment = $this->post('comment');

      $commentSuccess = $this->lesson->finishLectureAndComment($this->me->mb_no, $ta_lec_mb_no, $ta_lr_seq, $comment, $score);

      if ($commentSuccess) {
          $str_time = time();
          // noti 강사
          sendSystemMemo('28', $ta_lec_mb_no, '', $str_time, '', $ta_lr_seq, 0);

          // 강사에게 메일 보냄
          $tempUser = $this->user->profile($ta_lec_mb_no);
          date_default_timezone_set($tempUser ["member"]->mb_time);

          $lecture_detail = $this->lecture->getReservationDetail($ta_lr_seq);
          $strtime = $lecture_detail->lr_tsd_strtime;
          $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

          $ROOT_URL = TUTORK_ROOT_URL;
          $link1 = $ROOT_URL."/lessons/group/ALL";
          $link2 = $ROOT_URL."/finance/overview";

          $mail_subject="[TutorK] 강의를 들은 학생이 수업평가를 등록했습니다.";
          $mail_content_r = "<br/>강의를 들은 학생이 수업평가를 등록했습니다.";
          $mail_content_r = $mail_content_r."<br/>학생의 한국어 학습에 도움되는 말도 보내주시길 바랍니다.";
          $mail_content_r = $mail_content_r."<br/>Tutor-K 수수료를 제외한 강습료가 선생님 계정으로 이체됩니다.";
          $mail_content_r = $mail_content_r."<br/><a href='{$link1}' target='_blank'>강의평가 바로가기</a><br/>";
          $mail_content_r = $mail_content_r."<a href='{$link2}' target='_blank'>내통장 바로가기</a><br/>";
          $mail_content_r = $mail_content_r."<br/>";
          $mail_content_r = $mail_content_r."<br/>Sincerely,";
          $mail_content_r = $mail_content_r."<br/>The Tutor-K Team";
          sendMail($tempUser ["member"]->mb_email, $mail_subject, $mail_content_r);
      }

		$this->response('success');
	}

    // 학생 강의 환불 요청
	function refundRequest_post() {
        date_default_timezone_set('UTC');
		$ta_lec_mb_no = $this->post('ta_lec_mb_no');
        $ta_lr_seq = $this->post('ta_lr_seq');
        $ta_r_code = $this->post('ta_r_code');
        $comment = $this->post('comment');
        $str_time = time();
		$this->lesson->refundLectureAndComment($this->me->mb_no, $ta_lec_mb_no, $ta_lr_seq, $comment, $ta_r_code);


        // noti 강사
        sendSystemMemo('101', $ta_lec_mb_no, '', $str_time, '', $ta_lr_seq, 0);
        $lecture_detail = $this->lecture->getReservationDetail($ta_lr_seq);
        $tempStudent = $this->user->profile($this->me->mb_no);
        // $ROOT_URL = "http://www.tutor-k.com/bt2/#";
        $ROOT_URL = TUTORK_ROOT_URL;
        $student_link = $ROOT_URL."/lessons/sessions/REFUND";

        // 학생에게 메일 보냄
        date_default_timezone_set($tempStudent ["member"]->mb_time);
        $strtime = $lecture_detail->lr_tsd_strtime;
        $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

    	$mail_subject="[TutorK] Refund Request for Session ID ({$ta_lr_seq}) has been submitted.";
    	$mail_content_r = "<br/>You have submitted refund request for session ID ({$ta_lr_seq}).<br/>";
        $mail_content_r = $mail_content_r."<br/>Please wait for the tutor’s respond for this.";
        $mail_content_r = $mail_content_r."<br/><a href='{$student_link}' target='_blank'>{$student_link}</a><br/>";
        $mail_content_r = $mail_content_r."<br/>Sincerely,";
        $mail_content_r = $mail_content_r."<br/>The Tutor-K Team";
        sendMail($tempStudent ["member"]->mb_email, $mail_subject, $mail_content_r);

        // 강사에게 메일 보냄
        $tempUser = $this->user->profile($ta_lec_mb_no);
        date_default_timezone_set($tempUser ["member"]->mb_time);
        $strtime = $lecture_detail->lr_tsd_strtime;
        $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

        $ROOT_URL = TUTORK_ROOT_URL;
        // $link1 = $ROOT_URL."/lessons/sessions/REFUND";
		$link1 = $ROOT_URL."/lessons/group/EVAL_WAIT";

    	$mail_subject="[TutorK] Session ID ({$ta_lr_seq}) 에 대한 학생의 환불 요청이 있습니다.";
    	$mail_content_r = "<br/>Session ID ({$ta_lr_seq}) 에 대한 학생의 환불 요청이 있습니다.";
        $mail_content_r = $mail_content_r."<br/>학생과 협의 하셔서 진행하시길 바랍니다.";
        $mail_content_r = $mail_content_r."<br/>Please check your lesson management.";
        $mail_content_r = $mail_content_r."<br/><a href='{$link1}' target='_blank'>강의평가 바로가기</a><br/>";
        $mail_content_r = $mail_content_r."<br/>";
        $mail_content_r = $mail_content_r."<br/>Sincerely,";
        $mail_content_r = $mail_content_r."<br/>The Tutor-K Team";
        sendMail($tempUser ["member"]->mb_email, $mail_subject, $mail_content_r);


		$this->response('success');
	}


    // 강사 강의 환불 수락
	function refundAccept_post() {
        date_default_timezone_set('UTC');

        $ta_mb_no = $this->post('ta_mb_no');
		$ta_lec_mb_no = $this->post('ta_lec_mb_no');
        $ta_lr_seq = $this->post('ta_lr_seq');
        // $ta_r_code = $this->post('ta_r_code');
        // $comment = $this->post('comment');
        $str_time = time();
        $this->lesson->refundAccept($ta_mb_no, $ta_lec_mb_no, $ta_lr_seq);


        // noti 강사
        sendSystemMemo('101', $ta_lec_mb_no, '', $str_time, '', $ta_lr_seq, 0);
        $lecture_detail = $this->lecture->getReservationDetail($ta_lr_seq);
        $tempStudent = $this->user->profile($ta_mb_no);
        // $ROOT_URL = "http://www.tutor-k.com/bt2/#";
        $ROOT_URL = TUTORK_ROOT_URL;
        $student_link = $ROOT_URL."/lessons/sessions/REFUND";

        // 학생에게 메일 보냄
        date_default_timezone_set($tempStudent ["member"]->mb_time);
        $strtime = $lecture_detail->lr_tsd_strtime;
        $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

    	$mail_subject="[TutorK] Refund For Session ID ({$ta_lr_seq}) completed.";
    	$mail_content_r = "<br/>Refund For Session ID ({$ta_lr_seq}) has been completed.";
        $mail_content_r = $mail_content_r."<br/>Please check your lesson management.<br/>";
        $mail_content_r = $mail_content_r."<a href='{$student_link}' target='_blank'>{$student_link}</a><br/>";
        $mail_content_r = $mail_content_r."<br/>Sincerely,";
        $mail_content_r = $mail_content_r."<br/>The Tutor-K Team";
        sendMail($tempStudent ["member"]->mb_email, $mail_subject, $mail_content_r);

		$this->response('success');
	}

    // 강사 강의 환불 거절
	function refundResponse_post() {
        date_default_timezone_set('UTC');

        $ta_mb_no = $this->post('ta_mb_no');
		$ta_lec_mb_no = $this->post('ta_lec_mb_no');
        $ta_lr_seq = $this->post('ta_lr_seq');
        $file_path = $this->post('file_path');
        // $ta_r_code = $this->post('ta_r_code');
        // $comment = $this->post('comment');

		$this->lesson->refundResponse($ta_mb_no, $ta_lec_mb_no, $ta_lr_seq, $file_path);


        $lecture_detail = $this->lecture->getReservationDetail($ta_lr_seq);
        $tempStudent = $this->user->profile($ta_mb_no);
        // $ROOT_URL = "http://www.tutor-k.com/bt2/#";
        $ROOT_URL = TUTORK_ROOT_URL;
        $student_link = $ROOT_URL."/lessons/sessions/REFUND";

        // 학생에게 메일 보냄
        date_default_timezone_set($tempStudent ["member"]->mb_time);
        $strtime = $lecture_detail->lr_tsd_strtime;
        $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

    	$mail_subject="[TutorK] Response for refund request for Session ID ({$ta_lr_seq})";
    	$mail_content_r = "<br/>Refund request for Session ID ({$ta_lr_seq}) can not be processed due to the tutor did not accept your request.";
        $mail_content_r = $mail_content_r."<br/>Please check your lesson management.<br/>";
        $mail_content_r = $mail_content_r."<a href='{$student_link}' target='_blank'>{$student_link}</a><br/>";
        $mail_content_r = $mail_content_r."<br/>Sincerely,";
        $mail_content_r = $mail_content_r."<br/>The Tutor-K Team";
        sendMail($tempStudent ["member"]->mb_email, $mail_subject, $mail_content_r);

		$this->response('success');
	}

    // 강사 강의 코멘트
	function insertCommentTeacher_post() {
        $lr_mb_no = $this->post('lr_mb_no');
        $ta_lr_seq = $this->post('ta_lr_seq');
        $comment = $this->post('comment');
        $str_time = time();

		$this->lesson->updateTeacherComment($ta_lr_seq, $comment);

        // noti 학생에게 보냄
        sendSystemMemo('102', $lr_mb_no, '', $str_time, '', $ta_lr_seq, 0);

		$this->response('success');
	}
}
