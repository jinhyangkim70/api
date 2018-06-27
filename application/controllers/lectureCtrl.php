<?php
require APPPATH . '/libraries/REST_Controller.php';

// lecture (lesson의 집합)
class LectureCtrl extends REST_Controller {
    var $me;
    function __construct() {
        // Call the Model constructor
        parent::__construct ();

        date_default_timezone_set('UTC');

        //
        $this->load->model ( 'auth' );
        $this->load->model ( 'lecture' );
        $this->load->model ( 'user' );

        // date_default_timezone_set ( $this->me->mb_time );
    }

    private function getUserSession() {
        $userSession = userSessionData ();
        if (!empty($userSession)) {
            $this->me = $this->auth->getUserByNo ( $userSession->mb_no );
        }

        return $userSession;
    }

    // session 목록 폐이지 별로 get
    public function myPage_post() {
        $this->getUserSession();

        $page = $this->post('page');
        $count = $this->post('count');
        $filter = $this->post('filter');
        $result = $this->lecture->getLectureListByPage ( $this->me->mb_no, $this->me->mb_level, $page, $count, $filter );

        $this->response ( $result );
    }

    // 강의 평가 목록 폐이지 별로 get
    public function myEvaluationPage_post() {
        $this->getUserSession();

        $page = $this->post('page');
        $count = $this->post('count');
        $filter = $this->post('filter');
        $result = $this->lecture->getLectureAfterByPage($this->me->mb_no, $this->me->mb_level, $page, $count, $filter);

        $this->response($result);
    }

    // 패키지 리스트
    public function myPackageList_post() {
        $this->getUserSession();

        $page = $this->post('page');
        $count = $this->post('count');
        $result = $this->lecture->getPackageByPage($this->me->mb_no, $this->me->mb_level, $page, $count);

        $this->response($result);
    }

    public function myThisMonthSchedule_post() {
        $this->getUserSession();

        $last_date = $this->post('endOfMonth');
        $count = $this->lecture->getMonthScheduleCount($this->me->mb_no, $this->me->mb_level, $last_date);

        $this->response(array('count' => $count));
    }

    public function myThisMonthCompleteSchedule_post() {
        $this->getUserSession();

        $last_date = $this->post('endOfMonth');
        $start_date = $this->post('startOfMonth');
        $count = $this->lecture->getMonthScheduleCompelteCount($this->me->mb_no, $this->me->mb_level, $last_date, $start_date);

        $this->response(array('count' => $count));
    }

    public function myLectureAfter_post() {
        $this->getUserSession();

        $result = $this->lecture->getLectureAfter ( $this->me, '' );
        $this->response ( $result );
    }

    public function myLectureCategory_post() {
        $this->getUserSession();

        $result = $this->lecture->getLectureCategory ( $this->me->mb_no, $this->me->mb_level );

        $this->response ( $result );
    }
    public function mySessionAnalysis_post() {
        $this->getUserSession();

        $result = $this->lecture->getAnalysis ( $this->me->mb_no );

        $this->response ( $result );
    }

    public function themaList_post() {
        $this->response ( $this->lecture->getThemaTotal());
    }

    public function myLectureSetting_post() {
        $this->getUserSession();

        $result = $this->lecture->getSetting ( $this->me );
        $this->response ( $result );
    }

    public function getThemeList_post() {
        $result = $this->lecture->getThemeList();
        $this->response ( $result );
    }

    public function getRefundConditionList_post() {
        $result = $this->lecture->getRefundConditionList();
        $this->response ( $result );
    }

    public function getMonthSchedule_post() {
        $this->getUserSession();

        $startTime = $this->post('startTime');
        $endTime = $this->post('endTime');
        // error_log('startTime :: '.$startTime);
        // error_log('endTime :: '.$endTime);

        $result = $this->lecture->getLectureSchedule($this->me->mb_no, $startTime, $endTime);
        $this->response ( $result );
    }

    public function saveLecture_post() {
        $this->getUserSession();

        $lecture_data = (object) $this->post('data');
        // error_log('data seq 1= '. $object->lm_seq);

        if ($lecture_data->lm_seq == -1) {
            $this->lecture->insertLecture($this->me->mb_no, $lecture_data);
        } else {
            $this->lecture->updateLecture($this->me->mb_no, $lecture_data);
        }

        $this->response ( 'success' );
    }

    public function removeLecture_post() {
        $this->getUserSession();

        $lm_seq = $this->post('lm_seq');
        $ret = 'success';
        // error_log('data seq 1= '. $lm_seq);

        if ($lm_seq != -1) {
            $this->lecture->removeLecture($this->me->mb_no, $lm_seq);
        } else {
            $ret = 'fail';
        }

        $this->response ( $ret );
    }

    public function getWeeklyTimes_post() {
        $this->getUserSession();

        $mb_no = $this->post('mb_no');
        if (empty($mb_no)) {
            $mb_no = $this->me->mb_no;
        }

        $weeklyTime = $this->lecture->getWeeklyTime($mb_no);
        $timezone = $this->user->get_member_by_mb_no($mb_no, 'mb_time');
        $exceptSchedule = $this->lecture->getExceptScheduleList($mb_no);

        $this->response(array('weeklyTime' => $weeklyTime, 'timezone' => $timezone, 'exceptSchedule' => $exceptSchedule));
    }

    public function saveWeeklyTimes_post() {
        $this->getUserSession();

        $tsw_week = $this->post('tsw_week');
        $tsw_start_date = $this->post('tsw_start_date');
        $tsw_end_date = $this->post('tsw_end_date');
        $timeList = $this->post('timeList');

        $ret = 'success';

        for($i = 0; $i < count($timeList); $i++) {
            // error_log('+++++++ '.$this->me->mb_no.'  '.$tsw_week.'  '.$timeList[$i].'  '.$tsw_start_date.'  '.$tsw_end_date);
            $this->lecture->saveWeeklyTimes($this->me->mb_no, $tsw_week, $timeList[$i], $tsw_start_date, $tsw_end_date);
        }

        $this->response ( $ret );
    }

    public function removeWeeklyTimes_post() {
        $this->getUserSession();

        $tsw_week = $this->post('day');
        $tsw_start_date = $this->post('startTime');
        $tsw_end_date = $this->post('endTime');

        // error_log('+++++++ '.$this->me->mb_no.'  '.$tsw_week.'  '.$tsw_start_date.'  '.$tsw_end_date);
        $this->lecture->removeWeeklyTimes($this->me->mb_no, $tsw_week, $tsw_start_date, $tsw_end_date);

        $ret = 'success';
        $this->response ( $ret );
    }

    public function getExceptSchedule_post() {
        $this->getUserSession();

        // $start_date = $this->post('startDate');
        // $end_date = $this->post('endDate');

        // error_log('+++++++ '.$this->me->mb_no.'  '.$tsw_week.'  '.$tsw_start_date.'  '.$tsw_end_date);
        $result = $this->lecture->getExceptScheduleList($this->me->mb_no);

        $this->response ( $result );
    }

    public function newExceptSchedule_post() {
        $this->getUserSession();

        $start_date = $this->post('startDate');
        $end_date = $this->post('endDate');
        $tse_type = $this->post('tse_type');

        // error_log('+++++++ '.$this->me->mb_no.'  '.$tsw_week.'  '.$tsw_start_date.'  '.$tsw_end_date);
        $this->lecture->newExceptSchedule($this->me->mb_no, $start_date, $end_date, $tse_type);

        $ret = 'success';
        $this->response ( $ret );
    }

    public function removeExceptSchedule_post() {
        $tse_id = $this->post('tse_id');

        // error_log('+++++++ '.$this->me->mb_no.'  '.$tsw_week.'  '.$tsw_start_date.'  '.$tsw_end_date);
        $this->lecture->removeExceptSchedule($tse_id);

        $ret = 'success';
        $this->response ( $ret );
    }

    public function getLectureInfo_post() {
        $lm_seq = $this->post('lm_seq');

        $params = array('lm_seq' => $lm_seq);
        $select_column = 'lm_seq, lm_mb_no, lm_lec_cd, lm_lec_cd_etc, lm_group_yn, lm_payment, '
        .'lm_group_payment, lm_lec_mem, lm_sort, lm_package_count, lm_package_payment,'
        .'mb_no, mb_id, mb_name, mb_nick, mb_profile, mb_time';

        $ret = $this->lecture->getLectureInfo($params, $select_column);

        $this->response ( $ret );
    }

    public function getTeacherSchedule_post() {
        $this->getUserSession();

        $tsd_mb_no = $this->post('tsd_mb_no');
        $start_unix = $this->post('start_unix');
        $end_unix = $this->post('end_unix');

        $params = array(
            'tsd_mb_no' => $tsd_mb_no,
            'tsd_strtime >=' => $start_unix,
            'tsd_strtime <=' => $end_unix
        );

        $isTeacher = 5;
        $ret = $this->lecture->getUserSchedule($params, $isTeacher, true);

        $this->response ( $ret );
    }

    public function getUserSchedule_post() {
        $this->getUserSession();

        $tsd_mb_no = $this->post('tsd_mb_no');
        $start_unix = $this->post('start_unix');
        $end_unix = $this->post('end_unix');

        $params = array(
            'tsd_mb_no' => $tsd_mb_no,
            'tsd_strtime >=' => $start_unix,
            'tsd_strtime <=' => $end_unix
        );

        $ret = $this->lecture->getUserSchedule($params, $this->me->mb_level, true);

        $this->response ( $ret );
    }
 //public function getTeacherinfo_post() {
 //       $lr_seq = $this->post('lr_seq');
 //       $ret = $this->lecture->getTeacherinfo($lr_seq);
 //       $this->response ( $ret );
  //  }

    public function getScheduleDetail_post() {
        $lr_seq = $this->post('lr_seq');

        $ret = $this->lecture->getScheduleDetail($lr_seq);
        $this->response ( $ret );
    }

    public function getLectureComments_post() {
        $lr_seq = $this->post('lr_seq');

        $ret = $this->lecture->getCommentList($lr_seq);
        $this->response ( $ret );
    }

    // 시범강의는 한 학생이, 강사 당 한번씩만 수강 가능하도록 제한
    // $sql3="select * from TB_lec_reservation where lr_mb_no='{$member[mb_no]}' and  lr_lec_mb_no='{$lmn}' and lr_lec_cd='T00' and lr_status in ('H','S', 'F')";
    public function checkTrialLecture_post() {
        $lr_mb_no = $this->post('mb_no');
        $lr_lec_mb_no = $this->post('lr_lec_mb_no');
        $lr_lec_cd = 'T00';

        $params = array(
            'lr_mb_no' => $lr_mb_no,
            'lr_lec_mb_no' => $lr_lec_mb_no,
            'lr_lec_cd' => $lr_lec_cd
        );

        $lr_status = array('H', 'S', 'F');

        $ret = $this->lecture->getLectureReservation($params, $lr_status);
        $this->response ( $ret );
    }


    // 강의 추가
    public function insertReservation_post() {
        $this->getUserSession();

        $strtime_list = $this->post('strtime_list');
        $teacher_mb_no = $this->post('teacher_mb_no');
        $lr_lec_cd = $this->post('lr_lec_cd');
        $lr_single_koin = $this->post('lr_single_koin');
        $lr_package_koin = $this->post('lr_package_koin');
        $lr_comm_tool = $this->post('lr_comm_tool');
        $lr_skypeId = $this->post('lr_skypeId');
        $tlp_id = $this->post('tlp_id');
        $isPackage = $this->post('isPackage');
        $package_count = $this->post('package_count');
        $lm_seq = $this->post('lm_seq');

        $lm_lec_desc = $this->post('lm_lec_desc');

        $lr_group_yn = 'N';
        $lr_real_koin = $lr_single_koin;

        date_default_timezone_set('UTC');
        $today = date("Y-m-d H:i:s");


        if ($isPackage == '1') {
            // package
            $lr_group_yn = 'P';
            $lr_real_koin = 0;
        }

        // error_log('count : '.count($strtime_list));

        // $find_reservation = $this->lecture->getLectureReservation($params, $lr_status);
        if (count($strtime_list)) {

            $resevationAllGreen = true;
            //강의가 예약중인지 확인.
            //lr_status H-대기, S-성공, C-본인취소, D-강사취소, F- finish?

            $nextday = time() + (24 * 60 * 60); // 24 hours; 60 mins; 60 secs
            foreach ($strtime_list as $strtime) {
                // error_log("strtime :: {$strtime}");
                $params = array(
                    'tsd_mb_no' => $teacher_mb_no,
                    'tsd_strtime' => $strtime
                );

                // 수업 요청 시간이 24시간 이후의 시간보다 작다면 예약 불가
                if ($strtime < $nextday) {
                    $resevationAllGreen = false;
                }

                // error_log('$params : '.json_encode($params));
                $find_schedule = $this->lecture->getUserSchedule($params, 5, true);
                if (count($find_schedule)) {
                    $resevationAllGreen = false;
                }

            }

            // 중복된 시간이 없을때
            if ($resevationAllGreen) {
                // error_log("schedule insert");

                // 패키지는 모든 강의가 완료되었을때 payment남김
                if ($isPackage == '1') {
                    // error_log("schedule tlp_id".$tlp_id);
                    if ($tlp_id == '0') {
                        // 패키지 생성
                        $remain_package = $package_count - count($strtime_list);
                        $package_params = array(
                            // 'tp_lr_seq' => $reservation_id,
                            'tlp_lec_cd' => $lr_lec_cd,
                            'tlp_lm_seq' => $lm_seq,
                            'tlp_koin' => $lr_package_koin,
                            'tlp_mb_no' => $this->me->mb_no,
                            'tlp_lec_mb_no' => $teacher_mb_no,
                            'tlp_max_count' => $package_count,
                            'tlp_remain_count' => $remain_package,
                            'tlp_reg_dt' => $today,
                            'tlp_status' => 'H',
                        );
                        $tlp_id = $this->lecture->insertPackage($package_params);

                        $tempTeacher = $this->user->profile($teacher_mb_no);
                        $teacher_name = $tempTeacher['member']->mb_nick;
                        $str_time = time();
                        // noti 학생
                        sendSystemMemo('41', $this->me->mb_no, $teacher_name, $str_time, '', 0, $tlp_id);
                        // noti 강사
                        sendSystemMemo('51', $teacher_mb_no, $teacher_name, $str_time, '', 0, $tlp_id);

                        // save Tb_payment and Tb_payment_history
                        $payment_params = array(
                            'tp_mb_no' => $this->me->mb_no,
                            // 'tp_lr_seq' => $reservation_id,
                            'tp_tlp_id' => $tlp_id,
                            'tp_mode' => 'O',
                            'tp_out_koin' => $lr_package_koin,
                            'tp_reg_dt' => $today,
                            'tp_memo' => 'Apply for the lecture Package.',
                        );
                        $this->db->insert('Tb_payment', $payment_params);

                        // save Tb_payment_history
                        $history_params = array(
                            // 'ph_lr_seq' => $reservation_id,
                            'ph_tlp_id' => $tlp_id,
                            'ph_memo' => '패키지 강의 신청',
                            'ph_reg_dt' => $today,
                        );

                    } else {
                        // 이미 시작된 패키지 남은 강의수 업데이트
                        $package = $this->lecture->getPackageDetail($tlp_id);

                        $remain_package = $package->tlp_remain_count - count($strtime_list);

                        $this->lecture->updatePackage(
                                array('tlp_remain_count' => $remain_package),
                                array('tlp_id' => $tlp_id, 'tlp_status' => 'S')
                        );
                    }


                }

                foreach ($strtime_list as $strtime) {
                    // error_log("strtime :: {$strtime}");
                    if ($isPackage != '1') {
                        $tlp_id = NULL;
                    }

                    date_default_timezone_set('UTC');

                    $reservation_params = array(
                        'lr_mb_no' => $this->me->mb_no,
                        'lr_lec_mb_no' => $teacher_mb_no,
                        'lr_lec_cd' => $lr_lec_cd,
                        'lr_tsd_strtime' => $strtime,
                        'lr_group_yn' => $lr_group_yn,
                        'lr_single_koin' => $lr_single_koin,
                        'lr_group_koin' => $lr_single_koin,
                        'lr_real_koin' => $lr_real_koin,
                        'lr_package_koin' => $lr_package_koin,
                        'lr_status' => 'H',
                        'lr_reg_dt' => $today,
                        'lr_comm_tool' => $lr_comm_tool,
                        'lr_skypeId' => $lr_skypeId,
                        'lr_tlp_id' => $tlp_id,
                    );

                    // insert reservation
                    $reservation_id = $this->lecture->insertReservation($reservation_params);

                    // 패키지가 아닐때에만 남김.
                    if ($isPackage != '1') {
                        // save Tb_payment and Tb_payment_history
                        $payment_params = array(
                            'tp_mb_no' => $this->me->mb_no,
                            'tp_lr_seq' => $reservation_id,
                            'tp_mode' => 'O',
                            'tp_out_koin' => $lr_real_koin,
                            'tp_reg_dt' => $today,
                            'tp_memo' => 'Apply for the lecture.',
                        );
                        $this->lecture->insertPayment($payment_params);

                        // save Tb_payment_history
                        $history_params = array(
                            'ph_lr_seq' => $reservation_id,
                            'ph_memo' => '강의요청',
                            'ph_reg_dt' => $today,
                        );
                        $this->lecture->insertPaymentHistory($history_params);
                    }

                    // save schedule day
                    $this->lecture->saveScheduleDay($teacher_mb_no, $strtime);

                    $tempTeacher = $this->user->profile($teacher_mb_no);
                    $teacher_name = $tempTeacher['member']->mb_nick;
                    // noti 학생
                    sendSystemMemo('01', $this->me->mb_no, $teacher_name, $strtime, '', $reservation_id, 0);
                    // noti 강사
                    sendSystemMemo('21', $teacher_mb_no, $teacher_name, $strtime, '', $reservation_id, 0);

                    // 신청자 메일로 발송
                    $student = $this->user->simpleProfile($this->me->mb_no);
                    $root_url = TUTORK_ROOT_URL;
                    date_default_timezone_set($student->mb_time);
                	$str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

                    $endTime = $strtime + (60 * 30);
                    $str_tmp_end_time=date( 'H:i', $endTime);

                	$mail_subject="[TutorK] Introduction to a lesson scheduled.";
                	$mail_content_r="<p>{$str_timp_time} - {$str_tmp_end_time} Lesson has been scheduled.</p>";
                	$mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"http://www.tutor-k.com\">Check on reservation</a></p>";
                	sendMail($student->mb_email, $mail_subject, $mail_content_r);

                    // 선생님한테 메일 발송
                    $tempTeacher = $this->user->simpleProfile($teacher_mb_no);
                    date_default_timezone_set($tempTeacher->mb_time);

                    $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);
                    $strtime1 = $strtime - 86400;
                    $str_timp_time1 = date ('M',$strtime1)." ".date ('j',$strtime1)." ".date( 'H:i', $strtime1);

                    $lec_mb_name = $tempTeacher->mb_nick;
                    $student_name = $student->mb_nick;
                    $student_no = $student->mb_no;
                    $lm_comm_tool = $student->mb_comm_tool;
                    $lm_skypeId = $student->mb_skypeId;

                    // $lecture_detail = $this->lecture->getReservationDetail($lr_seq);
                    $lecture_code = $this->lecture->getLectureCodeInfo($lr_lec_cd);

                    $ROOT_URL = TUTORK_ROOT_URL;
                    $teacher_link = $ROOT_URL."lessons/sessions/REQUEST";

                	$mail_subject="[TutorK] 선생님, 새 강의 요청이 있습니다. : Tutor-K({$student_name})";
                	$mail_content_r="{$lec_mb_name} 선생님,<br/>
    						Tutor-K.com을 통해 학생으로부터 수업 요청이 있습니다. <br/>
    						학생: {$student_name} (Member ID: {$student_no})<br/>
    						Course Name: {$lecture_code->cd_desc_eng}<br/>
    						<br/>
    						아래 수업 요청 안내 보시고, 수업을 수락해주시기 바랍니다.<br/>
                            수업 ID: {$reservation_id}<br/>
                            강의 주제: {$lecture_code->cd_desc_eng}<br/>
    						수업 날짜와 시간: {$str_timp_time}<br/>
    						30분 수업료 : {$lr_real_koin} Koin credits <br/>
    						수업시 연락 방법 : {$lm_comm_tool} (ID : {$lm_skypeId}) <br/>
    						<a href='{$teacher_link}' target='_blank'>마이페이지 바로 가기</a><br/>
    						- - - - - - - - - - <br/>
    						수업 수락은 반드시 {$str_timp_time1} 까지 해 주시기 바랍니다. 수업 요청 시간이 지나면 수업 요청은 자동으로 만료됩니다.<br/>
    						<br/>
    						감사합니다.<br/>
    						Tutor-K 팀 드림</p>";
                    sendMail($tempTeacher->mb_email, $mail_subject, $mail_content_r);

                    // TEST code
                    // sendMail('ms08you@gmail.com', $mail_subject, $mail_content_r);
                }
            }
        }

        if ($resevationAllGreen) {
            $this->response (array('msg' =>'success'));
        } else {
            $this->response (array('msg' =>'fail'));
        }
    }

    public function getPackageDetail_post() {
        $tlp_id = $this->post('tlp_id');
        $package = $this->lecture->getPackageDetail($tlp_id);
        $lecture_List = $this->lecture->getLectureReservation(array('lr_tlp_id' => $tlp_id), NULL);

        $this->response (array(
            'package' => $package,
            'lectureList' => $lecture_List,
        ));
    }

    public function checkPackageStatus_post() {
        $tlp_id = $this->post('tlp_id');
        $package = $this->lecture->getPackageDetail($tlp_id);
        $this->response (array('packageStatus' => $package->tlp_status));
    }

    // 패키지 성사시
    public function acceptPackage_post() {
        $tlp_id = $this->post('tlp_id');

        $tlp_status = 'S';
        $lr_suc_dt =  date("Y-m-d H:i:s");

        $this->lecture->updatePackage(
            array('tlp_status' => $tlp_status, 'tlp_suc_dt' => $lr_suc_dt),
            array('tlp_id' => $tlp_id, 'tlp_status' => 'H')
        );

        $this->lecture->insertPaymentHistory(
            array('ph_tlp_id' => $tlp_id,
            'ph_memo' => '패키지 강의수락',
            'ph_reg_dt' => $lr_suc_dt)
        );


        $package = $this->lecture->getPackageDetail($tlp_id);
        $tempTeacher = $this->user->profile($package->tlp_lec_mb_no);
        $teacher_name = $tempTeacher ["member"]->mb_nick;
        $str_time = time();
        // noti 학생
        sendSystemMemo('42', $package->tlp_mb_no, $teacher_name, $str_time, '', 0, $tlp_id);

        // 패키지 수락시 강의 전체 수락하려햇지만 따로 수락하도록 함
        // $lecture_List = $this->lecture->getLectureReservation(array('lr_tlp_id' => $tlp_id), array('H'));
        // foreach ($lecture_List as $lecture_item) {
        //
        //     // error_log('pacakage items: '.$lecture_item->lr_seq);
        //     $this->lecture->updateReservation(
        //         array('lr_status' => $tlp_status, 'lr_suc_dt' => $lr_suc_dt),
        //         array('lr_tlp_id' => $lecture_item->lr_seq, 'lr_status' => 'H')
        //     );
        //
        //     $this->lecture->insertPaymentHistory(
        //         array('ph_lr_seq' => $lecture_item->lr_seq,
        //         'ph_memo' => '강의수락',
        //         'ph_reg_dt' => $lr_suc_dt)
        //     );
        // }

        $this->response ('success');
    }

    // 패키지 취소
    public function cancelPackage_post() {
        $tlp_id = $this->post('tlp_id');
        $lr_status = $this->post('lr_status');
        $lr_can_memo_txt = $this->post('lr_can_memo_txt');

        $lr_suc_dt = date("Y-m-d H:i:s");

        $temp_str = '학생취소';
        if ($lr_status === 'C') {
            $temp_str = '학생취소';
        } else {
            $temp_str = '강사취소';
        }

        $data_params;
        $where_params;

        $this->lecture->updatePackage(
            array('tlp_status' => $lr_status, 'tlp_can_dt' => $lr_suc_dt, 'tlp_can_memo_txt' => $lr_can_memo_txt),
            array('tlp_id' => $tlp_id, 'tlp_status' => 'H')
        );

        $package = $this->lecture->getPackageDetail($tlp_id);

        $lr_real_koin = $package->tlp_koin;
        $data_params = array(
            'tp_mb_no' => $package->tlp_mb_no,
            // 'tp_lr_seq' => $lr_seq,
            'tp_tlp_id' => $tlp_id,
            'tp_mode' => 'I',
            'tp_in_koin' => $lr_real_koin,
            'tp_reg_dt' => $lr_suc_dt,
            'tp_memo' => 'Cancel for the lecture package.',
        );
        $this->lecture->insertPayment($data_params);

        $this->lecture->insertPaymentHistory(
            array('ph_tlp_id' => $tlp_id,
            'ph_memo' => '패키지 '.$temp_str,
            'ph_reg_dt' => $lr_suc_dt)
        );

        // error_log("cancel pacakge koin = ".$package->tlp_koin);

        $lecture_List = $this->lecture->getLectureReservation(array('lr_tlp_id' => $tlp_id), array('H'));
        foreach ($lecture_List as $lecture_item) {
            if ($lr_status === 'C') {
                // 학생취소
                $data_params = array('lr_status' => $lr_status, 'lr_can_dt' => $lr_suc_dt, 'lr_can_memo_txt' => $lr_can_memo_txt);
                $where_params= array('lr_seq' => $lecture_item->lr_seq, 'lr_status' => 'H');
            } else {
                // 강사취소
                $data_params = array('lr_status' => $lr_status, 'lr_can_dt' => $lr_suc_dt, 'lr_can_memo_txt' => $lr_can_memo_txt);
                $where_params= array('lr_seq' => $lecture_item->lr_seq, 'lr_status' => 'H');
            }

            $this->lecture->updateReservation($data_params, $where_params);

            $this->lecture->insertPaymentHistory(
                array('ph_lr_seq' => $lecture_item->lr_seq,
                'ph_memo' => $temp_str,
                'ph_reg_dt' => $lr_suc_dt)
            );

            // save schedule day 비움
            $this->lecture->emptyScheduleDay($lecture_item->lr_lec_mb_no, $lecture_item->lr_tsd_strtime);
        }


        $tempTeacher = $this->user->profile($package->tlp_lec_mb_no);
        $teacher_name = $tempTeacher ["member"]->mb_nick;
        $str_time = time();
        // noti 학생
        sendSystemMemo('43', $package->tlp_mb_no, $teacher_name, $str_time, '', 0, $tlp_id);
        // noti 강사
        sendSystemMemo('52', $package->tlp_lec_mb_no, $teacher_name, $str_time, '', 0, $tlp_id);



        $this->response ('success');
    }


    // 강의 성사시
    public function acceptReservation_post() {
        $lr_seq = $this->post('lr_seq');
        $lr_lec_mb_no = $this->post('lr_lec_mb_no');

        $lr_status = 'S';
        $lr_suc_dt = date("Y-m-d H:i:s");

        $this->lecture->updateReservation(
            array('lr_status' => $lr_status, 'lr_suc_dt' => $lr_suc_dt),
            array('lr_seq' => $lr_seq, 'lr_lec_mb_no' => $lr_lec_mb_no, 'lr_status' => 'H')
        );

        $this->lecture->insertPaymentHistory(
            array('ph_lr_seq' => $lr_seq,
            'ph_memo' => '강의수락',
            'ph_reg_dt' => $lr_suc_dt)
        );

        $lecture_detail = $this->lecture->getReservationDetail($lr_seq);
        $tempStudent = $this->user->profile($lecture_detail->lr_mb_no);
        $tempTeacher = $this->user->profile($lr_lec_mb_no);
        $teacher_name = $tempTeacher ["member"]->mb_nick;
        $str_time = time();
        // noti 학생
        sendSystemMemo('02', $lecture_detail->lr_mb_no, $teacher_name, $str_time, '', $lr_seq, 0);


        $lecture_detail = $this->lecture->getReservationDetail($lr_seq);
        $lecture_code = $this->lecture->getLectureCodeInfo($lecture_detail->lr_lec_cd);

        $ROOT_URL = TUTORK_ROOT_URL;
        // 학생에게 메일 보냄
        date_default_timezone_set($tempStudent ["member"]->mb_time);
        $strtime = $lecture_detail->lr_tsd_strtime;
        $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

    	$mail_subject="[TutorK] New Session Accepted: ({$tempTeacher ["member"]->mb_nick}) (Session ID: {$lr_seq})";
    	$mail_content_r = "<br/>Dear ({$tempStudent ["member"]->mb_nick}),<br/>";
        $mail_content_r = $mail_content_r."Your teacher has accepted a scheduled lesson on tutor-k.com.<br/><br/>Please follow the link below to view this lesson request.";
        $mail_content_r = $mail_content_r."<br/><br/><a href='$ROOT_URL' target='_blank'>$ROOT_URL</a>";
        $mail_content_r = $mail_content_r."<br/>- - - - - - - - - - -";
        $mail_content_r = $mail_content_r."<br/>Teacher: {$tempTeacher ["member"]->mb_nick} (Member ID: {$lr_lec_mb_no})";
        $mail_content_r = $mail_content_r."<br/>Session Date: {$str_timp_time}";
        $mail_content_r = $mail_content_r."<br/>Session ID: {$lr_seq}";
        $mail_content_r = $mail_content_r."<br/><br/>This lesson is for 30 minutes.";
        $mail_content_r = $mail_content_r."<br/>- - - - - - - - - - -";
        $mail_content_r = $mail_content_r."<br/>Course Name: {$lecture_code->cd_desc_eng}";
        $mail_content_r = $mail_content_r."<br/>Session Price: {$lecture_detail->lr_real_koin} Tutor-K credits (KOIN)";
        $mail_content_r = $mail_content_r."<br/><br/>Sincerely,";
        $mail_content_r = $mail_content_r."<br/>The Tutor-K Team";

        sendMail($tempStudent ["member"]->mb_email, $mail_subject, $mail_content_r);
        $this->response ('success');
    }

    // 강의 취소
    public function cancelReservation_post() {
        $this->getUserSession();

        $lr_seq = $this->post('lr_seq');
        $lr_status = $this->post('lr_status');
        $lr_can_memo_txt = $this->post('lr_can_memo_txt');

        $lr_suc_dt = date("Y-m-d H:i:s");

        $temp_str = '학생취소';
        $data_params;
        $where_params;
        if ($lr_status === 'C') {
            // 학생취소
            $temp_str = '학생취소';
            $data_params = array('lr_status' => $lr_status, 'lr_can_dt' => $lr_suc_dt, 'lr_can_memo_txt' => $lr_can_memo_txt);

            // 강의 수락이후에도 수업시간 시작 24시간 이전에 취소 가능하도록 - 170704 tutork 요청
            // $where_params= array('lr_seq' => $lr_seq, 'lr_mb_no' => $this->me->mb_no, 'lr_status' => 'H');
            $where_params= array('lr_seq' => $lr_seq, 'lr_mb_no' => $this->me->mb_no);
        } else {
            // 강사취소
            $temp_str = '강사취소';
            $data_params = array('lr_status' => $lr_status, 'lr_can_dt' => $lr_suc_dt, 'lr_can_memo_txt' => $lr_can_memo_txt);

            // 강의 수락이후에도 수업시간 시작 24시간 이전에 취소 가능하도록 - 170704 tutork 요청
            // $where_params= array('lr_seq' => $lr_seq, 'lr_lec_mb_no' => $this->me->mb_no, 'lr_status' => 'H');
            $where_params= array('lr_seq' => $lr_seq, 'lr_lec_mb_no' => $this->me->mb_no);
        }

        $lecture_detail = $this->lecture->getReservationDetail($lr_seq);

        if ($lecture_detail->lr_status === 'H' || $lecture_detail->lr_status === 'S') {
          $this->lecture->updateReservation($data_params, $where_params, array('H', 'S'));

          $lr_real_koin = $this->lecture->getRealKoinValue($lr_seq);
          $data_params = array(
              'tp_mb_no' => $lecture_detail->lr_mb_no,
              'tp_lr_seq' => $lr_seq,
              'tp_mode' => 'I',
              'tp_in_koin' => $lr_real_koin,
              'tp_reg_dt' => $lr_suc_dt,
              'tp_memo' => 'Cancel for the lecture.',
          );
          $this->lecture->insertPayment($data_params);

          $data_params = array(
              'ph_lr_seq' => $lr_seq,
              'ph_memo' => $temp_str,
              'ph_reg_dt' => $lr_suc_dt
          );
          $this->lecture->insertPaymentHistory($data_params);

          // 스케쥴 날짜 비움
          $temp_reservation = $this->lecture->getReservationDetail($lr_seq);
          $this->lecture->emptyScheduleDay($temp_reservation->lr_lec_mb_no, $temp_reservation->lr_tsd_strtime);

          // 패키지에 속한 강의 일때 remain_package을 증가 시켜줌
          if ($temp_reservation->lr_tlp_id) {
              $package = $this->lecture->getPackageDetail($temp_reservation->lr_tlp_id);

              $package->tlp_remain_count++;
              $this->lecture->updatePackage(
                      array('tlp_remain_count' => $package->tlp_remain_count),
                      array('tlp_id' => $temp_reservation->lr_tlp_id, 'tlp_status' => 'S')
              );
          }

          $tempStudent = $this->user->profile($lecture_detail->lr_mb_no);
          $tempTeacher = $this->user->profile($lecture_detail->lr_lec_mb_no);
          $teacher_name = $tempTeacher ["member"]->mb_nick;
          // noti 학생
          sendSystemMemo('03', $lecture_detail->lr_mb_no, $teacher_name, $lecture_detail->lr_tsd_strtime, '', $lr_seq, 0);
          $ROOT_URL = TUTORK_ROOT_URL;
          // 학생에게 메일 보냄
          date_default_timezone_set($tempStudent ["member"]->mb_time);
          $strtime = $temp_reservation->lr_tsd_strtime;
          $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

      	$mail_subject="[Tutor-K] Lesson cancellation notice (session ID :{$lr_seq}}";
      	$mail_content_r="<br/>
  				{$str_timp_time} lesson has been canceled<br/>
  				<br/>
  				<a href='$ROOT_URL' target='_blank'>Check cancellation reason</a><br/>
  				";
          sendMail($tempStudent ["member"]->mb_email, $mail_subject, $mail_content_r);
          // noti 강사
          sendSystemMemo('22', $lecture_detail->lr_lec_mb_no, $teacher_name, $lecture_detail->lr_tsd_strtime, '', $lr_seq, 0);

          // 강사에게 메일 보냄
          date_default_timezone_set($tempTeacher ["member"]->mb_time);
          $strtime = $temp_reservation->lr_tsd_strtime;
          $str_timp_time=date ('M',$strtime)." ".date ('j',$strtime)." ".date( 'H:i', $strtime);

      	$mail_subject="[Tutor-K] 수업 취소 안내 (session ID :{$lr_seq}}";
      	$mail_content_r="<br/>
  				{$str_timp_time}  강의가 취소 되었습니다.<br/>
  				<br/>
  				<a href='$ROOT_URL' target='_blank'>Check cancellation reason</a><br/>";
          sendMail($tempTeacher ["member"]->mb_email, $mail_subject, $mail_content_r);
        }


        $this->response ('success');
    }
}
