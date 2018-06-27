<?php
class lecture extends CI_Model {
	function __construct() {
		// Call the Model constructor
		parent::__construct ();
	}

    function getPackageByPage($mb_no, $mb_level, $page, $count) {
        $hasNext = true;
        $startIndex = $count * $page;

        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            $this->db->where(array('tlp_lec_mb_no' => $mb_no));
        } else {
            $this->db->where(array('tlp_mb_no' => $mb_no));
        }
        $totalCount = $this->db->get('tb2_lec_package')->num_rows();

        if ($totalCount <= $startIndex + $count) {
            $hasNext = false;
        }

        $select_pacakge = 'tlp_id, tlp_lec_cd, tlp_koin, tlp_mb_no, tlp_lec_mb_no, tlp_max_count, tlp_remain_count, tlp_reg_dt, tlp_status, tlp_can_memo_txt';
        $member_col = 'member.mb_nick, member.mb_profile, member.mb_skypeId';
        $thema_col = 'thema.cd_desc, thema.cd_desc_eng, thema.cd_desc_eng';
        $lecture_col = 'lecture.lm_lec_cd_etc';

        $this->db->select($select_pacakge.' , '.$member_col.' , '.$thema_col.' , '.$lecture_col);
        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            // 선생님일 경우 학생정보가저옴
            $this->db->join('g4_member member', 'member.mb_no = tb2_lec_package.tlp_mb_no', 'LEFT');
            $this->db->where(array('tlp_lec_mb_no' => $mb_no));
        } else {
            $this->db->join('g4_member member', 'member.mb_no = tb2_lec_package.tlp_lec_mb_no', 'LEFT');
            $this->db->where(array('tlp_mb_no' => $mb_no));
        }

        // 수업 이름
        $this->db->join('tb_code thema', 'thema.cd_code = tb2_lec_package.tlp_lec_cd', 'LEFT');
        // 수업 이름2
        $this->db->join('TB_lec_main lecture', 'lecture.lm_seq = tb2_lec_package.tlp_lm_seq', 'LEFT');

        $this->db->order_by('tlp_reg_dt', 'desc');
        $this->db->limit($count, $startIndex);
        $ret = $this->db->get('tb2_lec_package')->result();

        // $sql = $this->db->last_query();
        // error_log('my pacakge query === '.$sql);

				foreach ($ret as $row)
				{
					// error_log('pacakge === '.$row->tlp_id);
					$this->db->select('lr_tsd_strtime');
					$this->db->where(array('lr_tlp_id' => $row->tlp_id));
					$this->db->order_by('lr_tsd_strtime', 'desc');
					$find_lec_reserv = $this->db->get('TB_lec_reservation')->row();

					if ($find_lec_reserv) {
						$row->recent_lecture_dt = $find_lec_reserv->lr_tsd_strtime;
					} else {
						$row->recent_lecture_dt = '';
					}

					// error_log('lecture  === '.json_encode($find_lec_reserv));
					// error_log('my pacakge query === '.$sql);
				}

        return array(
                'meta' => array (
                        'hasNext' => $hasNext,
                        // 'query' => $query
                ),
                'data' => $ret
        );
    }
	function getLectureList($mb_no, $std_so) {
		$query = "select *, (select count(*) as cnt from TB_lec_reservation as b where a.lr_lec_mb_no=b.lr_lec_mb_no and a.lr_tsd_strtime=b.lr_tsd_strtime and b.lr_status in ('H','S')) as mem_cnt,
		(select COALESCE(tsd_group_status,'N') from TB_schedule_day where tsd_mb_no=a.lr_lec_mb_no and tsd_strtime=a.lr_tsd_strtime) as group_status
		from TB_lec_reservation as a
		join TB_lec_main on a.lr_lec_mb_no=lm_mb_no and a.lr_lec_cd=lm_lec_cd and a.lr_lec_mb_no='{$mb_no}'
		join g4_member on a.lr_mb_no=mb_no where 1=1 ";
		if ($std_so != "") {
			if ($std_so == "C") {
				$query = $query . " and lr_status IN ('C','D')";
			} else {
				$query = $query . " and lr_status='{$std_so}'";
			}
		} else {
			$query = $query . " and lr_status IN ('H','C','D','S')";
		}
		$query = $query . " order by lr_reg_dt desc";

		$stmt = $this->db->query ( $query );

		$ret = array ();
		if ($stmt->num_rows () > 0) {
			foreach ( $stmt->result () as $row ) {
				$item = $this->_createLecturePageItem ( $row, $mb_no );
				array_push ( $ret, $item );
			}
		}

		return $ret;
	}

    function getMonthScheduleCount($mb_no, $mb_level, $last_date) {
        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            // 강사 수업일정
            $schedule_day_col = 'tsd_group_status, tsd_mb_no, tsd_single_status, tsd_strtime, tsd_time_add_flag';
            $resevation_col = 'lr_seq, lr_group_yn';
            $this->db->select($schedule_day_col.' , '.$resevation_col);


            $this->db->join('TB_lec_reservation reservation', 'reservation.lr_tsd_strtime = TB_schedule_day.tsd_strtime', 'LEFT');

            $start_time = time();
            $this->db->where('tsd_strtime >=', $start_time);
            $this->db->where('tsd_strtime <=', $last_date);

            $this->db->where('tsd_mb_no', $mb_no);

            $temp_status = array('S');
            $this->db->where_in('lr_status', $temp_status);

            // 이미 기존에 만들어진 스케쥴의 경우 비어있는 스케쥴도 미리 생성해버림
            // 생성된 비어잇는 스케쥴의경우 tsd_time_add_flag 값이 NULL
            // 헷갈리니 tsd_group_status은 제외하고 생각
            $this->db->where("tsd_single_status IS NOT NULL");


            $this->db->from('TB_schedule_day');
            $scheudle_count = $this->db->count_all_results();

            // $sql = $this->db->last_query();
            // error_log('teacher sql : '.$sql);

            return $scheudle_count;
        } else {
            // 학생 일정

            $start_time = time();
            $student_search = array(
                'lr_mb_no' => $mb_no,
                'lr_tsd_strtime >=' => $start_time,
                'lr_tsd_strtime <=' => $last_date,
            );

            $this->db->where($student_search);

            $temp_status = array('S');
            $this->db->where_in('lr_status', $temp_status);

            $this->db->from('TB_lec_reservation');
            $scheudle_count = $this->db->count_all_results();

            // $sql = $this->db->last_query();
            // error_log('student sql : '.$sql);

            return $scheudle_count;
        }
    }

    // 완료된 강의갯수
    function getMonthScheduleCompelteCount($mb_no, $mb_level, $last_date, $start_time) {
        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            // 강사 수업일정
            $schedule_day_col = 'tsd_group_status, tsd_mb_no, tsd_single_status, tsd_strtime, tsd_time_add_flag';
            $resevation_col = 'lr_seq, lr_group_yn';
            $this->db->select($schedule_day_col.' , '.$resevation_col);


            $this->db->join('TB_lec_reservation reservation', 'reservation.lr_tsd_strtime = TB_schedule_day.tsd_strtime', 'LEFT');

            $this->db->where('tsd_strtime >=', $start_time);
            $this->db->where('tsd_strtime <=', $last_date);

            $this->db->where('tsd_mb_no', $mb_no);

            $temp_status = array('F');
            $this->db->where_in('lr_status', $temp_status);

            // 이미 기존에 만들어진 스케쥴의 경우 비어있는 스케쥴도 미리 생성해버림
            // 생성된 비어잇는 스케쥴의경우 tsd_time_add_flag 값이 NULL
            // 헷갈리니 tsd_group_status은 제외하고 생각
            $this->db->where("tsd_single_status IS NOT NULL");


            $this->db->from('TB_schedule_day');
            $scheudle_count = $this->db->count_all_results();

            // $sql = $this->db->last_query();
            // error_log('teacher sql : '.$sql);

            return $scheudle_count;
        } else {
            // 학생 일정
            $student_search = array(
                'lr_mb_no' => $mb_no,
                'lr_tsd_strtime >=' => $start_time,
                'lr_tsd_strtime <=' => $last_date,
            );

            $temp_status = array('F');
            $this->db->where_in('lr_status', $temp_status);

            $this->db->where($student_search);

            $this->db->from('TB_lec_reservation');
            $scheudle_count = $this->db->count_all_results();

            // $sql = $this->db->last_query();
            // error_log('student sql : '.$sql);

            return $scheudle_count;
        }
    }

    function getLectureListByPage($mb_no, $mb_level, $page, $count, $filter) {
        $str_today = date ( 'Y-m-j H:i' );
        $str_today = strtotime ( $str_today ) + 6400;
        $today_strtime = strtotime ( date ( 'Y-m-j H:i' ) );

        $this->db->join('TB_lec_main lecture', 'lecture.lm_mb_no = TB_lec_reservation.lr_lec_mb_no AND lecture.lm_lec_cd = TB_lec_reservation.lr_lec_cd ', 'LEFT');

        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            // 선생님일 경우 학생정보가저옴
            $this->db->join('g4_member member', 'member.mb_no = TB_lec_reservation.lr_mb_no', 'LEFT');
        } else {
            $this->db->join('g4_member member', 'member.mb_no = TB_lec_reservation.lr_lec_mb_no', 'LEFT');
        }

        $this->db->join('tb_code thema', 'thema.cd_code = TB_lec_reservation.lr_lec_cd', 'LEFT');
				$this->db->join('TB_after_comment comment', 'comment.ta_lr_seq = TB_lec_reservation.lr_seq', 'LEFT');

        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            $this->db->where('TB_lec_reservation.lr_lec_mb_no', $mb_no);
        } else {
        // } else if($mb_level == 3) {
            $this->db->or_where('TB_lec_reservation.lr_mb_no', $mb_no);
        }

        // $this->db->where("(TB_lec_reservation.lr_lec_mb_no={$mb_no} OR TB_lec_reservation.lr_mb_no={$mb_no})", NULL, FALSE);


        // 강의 상태 (lr_status )
        // H-대기,
        // S-수업종료, 평가대기상태
        // C-본인취소
        // D-강사취소
        // F- finish
        // R-환불
        // T-자유수업??? = 안쓴다고함

        switch ($filter) {

            case 'REQUEST' :
                // 강의 요청
                $this->db->where( array ('TB_lec_reservation.lr_status' => 'H'));
                break;
            case 'SCHEDULE_LECTURE' :
                    // 수업 예정
                    $this->db->where(
                        array (
                                'TB_lec_reservation.lr_status' => 'S',
                                'TB_lec_reservation.lr_tsd_strtime >=' => $str_today
                        ));
                    break;
            case 'RATING_LECTURE' :
                    // 수락 평가 기다림
                    // $this->db->where(
                    //     array (
                    //             'TB_lec_reservation.lr_status' => 'S',
                    //             'TB_lec_reservation.lr_tsd_strtime <=' => $today_strtime,
                    //             'TB_lec_reservation.lr_auto_koin' => NULL,
                    //     ));

                    // 최희정 요청 = 강의 신청한것까지 떠야한다고함
                    $accept_status = array('H', 'S');
                    $this->db->where_in('TB_lec_reservation.lr_status', $accept_status);
                    // $this->db->where('archived IS NOT NULL', null, false);
                    break;
            case "REFUND" :
                    // 문제 발생
                    $this->db->where( array ('TB_lec_reservation.lr_status' => 'R'));
                    break;
            case 'FINISH' :
                    // 강의 종료
                    $this->db->where( array ('TB_lec_reservation.lr_status' => 'F'));
                    break;
            case 'CANCEL' :
                    // 강의 취소
                    $cancel_status = array('C', 'D', 'T');
                    $this->db->where_in('TB_lec_reservation.lr_status', $cancel_status);
                    break;
            default :
                    // 전체
                    break;
        }

        // $this->db->order_by('lr_reg_dt', 'desc');
        $this->db->order_by('lr_tsd_strtime', 'desc');
        $totalCount = $this->db->get('TB_lec_reservation')->num_rows();
        $sql = $this->db->last_query();



        // data 조회
        $startIndex = $count * $page;
        if ($count) {
            $sql = $sql . " limit {$startIndex} , {$count} ";
        }
        $lec_list = $this->db->query($sql)->result();
        // $lec_list = $this->db->get('TB_lec_reservation')->result();

        // $sql = $this->db->last_query();
        // error_log('my page query === '.$sql);

        $hasNext = true;
        if ($totalCount <= $startIndex + $count) {
            $hasNext = false;
        }

        // error_log("lec_list".count($lec_list));
    	$ret = array ();
        if (count($lec_list)) {
            foreach ( $lec_list as $row ) {

        		$str_lec = $row->cd_desc;

        		// if ($row->lm_lec_cd_etc != "")
        		// 	$str_lec = $str_lec . " (" . $row->lm_lec_cd_etc . ")";

        		$comm_tool_info = "";
        		if ($row->lr_comm_tool != "") {
        			$comm_tool_info = $row->lr_comm_tool . " ID : (" . $row->lr_skypeId . ")";
        		} else {
        			$comm_tool_info = $row->mb_skypeId;
        		}

        		$item =  array (
        				// 'color' => "",
        				'lr_seq' => $row->lr_seq,
                        'cd_code' => $row->cd_code,
        				'str_lec' => $str_lec,
                        'lm_lec_cd_etc' => $row->lm_lec_cd_etc,
        				'lr_tsd_strtime' => ( int ) $row->lr_tsd_strtime,
        				// 'str_type' => $str_type,
        				// 'str_mem' => $str_mem,
        				// 'str_link' => $str_link,
        				'comm_tool_info' => $comm_tool_info,
        				// 'str_status' => $str_status,
        				// 'str_img' => $str_img,
                        'lr_group_yn' => $row->lr_group_yn,
        				'lr_real_koin' => $row->lr_real_koin,
        				'mb_nick' => $row->mb_nick,
        				'mb_no' => $row->mb_no,
                        'mb_level' => $row->mb_level,
        				'mb_profile' => $row->mb_profile,
        				'lr_tsd_strtime_m_j' => date ( 'M j', $row->lr_tsd_strtime ),
        				'lr_tsd_strtime_h_i' => date ( 'H:i', $row->lr_tsd_strtime ),
        				'lr_status' => $row->lr_status,
        				'lr_tsd_strtime' => $row->lr_tsd_strtime,
        				'lr_auto_koin' => $row->lr_auto_koin,
                        'lr_lec_mb_no' => $row->lr_lec_mb_no,
                        'lr_mb_no' => $row->lr_mb_no,
                        'lr_can_memo_txt' => $row->lr_can_memo_txt,
                        'lr_tlp_id' => $row->lr_tlp_id,
				                'ta_status' => $row->ta_status,
												'ta_r_code' => $row->ta_r_code,
				                'ta_teacher_comment' => $row->ta_teacher_comment,
        		);
                array_push ( $ret, $item );
            }
        }

        return array (
                'meta' => array (
                        'hasNext' => $hasNext,
                        // 'query' => $query
                ),
                'data' => $ret
        );
    }


    // not use
	// function getLectureListByPage($mb_no, $page, $count, $filter) {
	// 	$str_today = date ( 'Y-m-j H:i' );
	// 	$str_today = strtotime ( $str_today ) + 6400;
	// 	$today_strtime = strtotime ( date ( 'Y-m-j H:i' ) );
    //
	// 	$query = "select *, (select count(*) as cnt from TB_lec_reservation as b where a.lr_lec_mb_no=b.lr_lec_mb_no and a.lr_tsd_strtime=b.lr_tsd_strtime and b.lr_status in ('H','S')) as mem_cnt,
	// 	(select COALESCE(tsd_group_status,'N') from TB_schedule_day where tsd_mb_no=a.lr_lec_mb_no and tsd_strtime=a.lr_tsd_strtime) as group_status
	// 	from TB_lec_reservation as a
	// 	join TB_lec_main on a.lr_lec_mb_no=lm_mb_no and a.lr_lec_cd=lm_lec_cd and a.lr_lec_mb_no='{$mb_no}'
	// 	join g4_member on a.lr_mb_no=mb_no where 1=1 ";
    //
	// 	switch ($filter) {
	// 		case 1 :
	// 			{
	// 				// 전체
	// 				break;
	// 			}
    //
	// 		case 2 :
	// 			{
	// 				// 수업 예정
	// 				$query .= " and lr_status='S' and lr_tsd_strtime>='{$str_today}' ";
	// 				break;
	// 			}
    //
	// 		case 3 :
	// 			{
	// 				// 수락 평가 기다림
	// 				$query .= " and lr_status='S'  and lr_tsd_strtime<='{$today_strtime}' and lr_auto_koin is null ";
	// 				break;
	// 			}
    //
	// 		case 4 :
	// 			{
	// 				// 수락 평가하기
	// 				$query .= " and lr_status='H' ";
	// 				break;
	// 			}
    //
	// 		case 5 :
	// 			{
	// 				// 문제 발생
	// 				$query .= " and lr_status='R' ";
	// 				break;
	// 			}
    //
	// 		case 6 :
	// 			{
	// 				// 강의 수락
	// 				$query .= " and lr_status='S' ";
	// 				break;
	// 			}
    //
	// 		case 7 :
	// 			{
	// 				// 강의 요청
	// 				$query .= " and lr_status='H' ";
	// 				break;
	// 			}
    //
	// 		case 8 :
	// 			{
	// 				// 강의 취소
	// 				$query .= " and lr_status IN ('C','D') ";
	// 				break;
	// 			}
	// 	}
    //
	// 	$startIndex = $count * $page;
    //
	// 	$query = $query . " order by lr_reg_dt desc ";
    //
	// 	$totalCount = $this->db->query ( $query )->num_rows ();
    //
	// 	$query = $query . " limit {$startIndex} , {$count} ";
    //
	// 	$stmt = $this->db->query ( $query );
    //
    //     $sql = $this->db->last_query();
    //     error_log('my page query === '.$sql);
    //
	// 	$hasNext = true;
	// 	if ($totalCount <= $startIndex + $count) {
	// 		$hasNext = false;
	// 	}
    //
	// 	$ret = array ();
	// 	if ($stmt->num_rows () > 0) {
	// 		foreach ( $stmt->result () as $row ) {
	// 			$item = $this->_createLecturePageItem ( $row, $mb_no );
	// 			array_push ( $ret, $item );
	// 		}
	// 	}
    //
	// 	return array (
	// 			'meta' => array (
	// 					'hasNext' => $hasNext,
	// 					'query' => $query
	// 			),
	// 			'data' => $ret
	// 	);
    // }

	function getLectureCategory($mb_no, $mb_level) {
		$str_today = date ( 'Y-m-j H:i' );
		$str_today = strtotime ( $str_today ) + 6400;

		$today_strtime = strtotime ( date ( 'Y-m-j H:i' ) );

		$upcoming_color = "yellow";
		$awaiting_color = "l_sky";
		$action_color = "green";
		$problem_color = "sky";

		if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {

			$query1 = "select lr_seq from TB_lec_reservation where lr_lec_mb_no=" . $mb_no . " and lr_status='S' and lr_tsd_strtime>='{$str_today}'";
			$upcoming_item = $this->_createCategory ( $query1 );

			$query2 = "select lr_seq from TB_lec_reservation where lr_lec_mb_no=" . $mb_no . " and lr_status='S'  and lr_tsd_strtime<='{$today_strtime}' and lr_auto_koin is null";
			$awaiting_item = $this->_createCategory ( $query2 );

			$query3 = "select lr_seq from TB_lec_reservation where lr_lec_mb_no=" . $mb_no . " and lr_status='H'";
			$action_item = $this->_createCategory ( $query3 );

			$query4 = "select lr_seq from TB_lec_reservation where lr_lec_mb_no=" . $mb_no . " and lr_status='R'";
			$problem_item = $this->_createCategory ( $query4 );
		} else {

			$query1 = "select lr_seq from TB_lec_reservation where lr_mb_no=" . $mb_no . " and lr_status='S' and lr_tsd_strtime>='{$str_today}'";
			$upcoming_item = $this->_createCategory ( $query1 );

			$query2 = "select lr_seq from TB_lec_reservation where lr_mb_no=" . $mb_no . " and lr_status='H'";
			$awaiting_item = $this->_createCategory ( $query2 );

			$query3 = "select lr_seq from TB_lec_reservation where lr_mb_no=" . $mb_no . " and lr_status='S' and lr_tsd_strtime<='{$today_strtime}' and lr_auto_koin is null ";
			$action_item = $this->_createCategory ( $query3 );

			$query4 = "select lr_seq from TB_lec_reservation as A where lr_mb_no=" . $mb_no . " and lr_status='R' and lr_seq in (select ta_lr_seq from TB_after_comment where ta_lr_seq = A.lr_seq and ta_r_code is not null and ta_status not in ('S','F','N'))";
			$problem_item = $this->_createCategory ( $query4 );
		}

		return array (
				'upcoming' => $upcoming_item,
				'awaiting' => $awaiting_item,
				'action' => $action_item,
				'problem' => $problem_item
		);
	}
	public function getAnalysis($mb_no) {
		$f_strtime = strtotime ( date ( 'Y-m-j H:i' ) ) - 1800;
		$query = "select count(*) as cnt from TB_lec_reservation where lr_lec_mb_no='{$mb_no}' and lr_status IN ('S','F','R')";
		$total_cnt = $this->db->query ( $query )->row ()->cnt;

		$f_strtime = strtotime ( date ( 'Y-m-01 H:i' ) );
		$e_strtime = strtotime ( date ( 'Y-m-j H:i' ) );
		$query = "select count(*) as cnt from TB_lec_reservation where lr_lec_mb_no='{$mb_no}' and (lr_tsd_strtime between '{$f_strtime}' and '{$e_strtime}') and lr_status IN ('S','F','R')";
		$month_cnt = $this->db->query ( $query )->row ()->cnt;

		$s_strtime = strtotime ( date ( 'Y-m-j' ) . "00:00" );
		$e_strtime = strtotime ( date ( 'Y-m-j' ) . "23:59" );

		$query = "select count(*) as cnt from TB_lec_reservation where lr_lec_mb_no='{$mb_no}' and lr_status IN ('S') and  lr_tsd_strtime between '{$s_strtime}' and '{$e_strtime}' ";
		$to_cnt = $this->db->query ( $query )->row ()->cnt;

		return array (
				'total_cnt' => $total_cnt,
				'total_cnt_intval' => intval ( $total_cnt / 1 ),
				'month_cnt' => $month_cnt,
				'month_cnt_intval' => intval ( $month_cnt / 1 ),
				'to_cnt' => $to_cnt
		);
	}

	// 강의 평가 보기 main section
	public function getLectureAfter($mb, $std_so) {
		$f_strtime = strtotime ( date ( 'Y-m-j H:i' ) ) + 1800000;
		$today_strtime = $f_strtime;

		$query = "select *, (select count(*) as cnt from TB_lec_reservation as b where a.lr_mb_no=b.lr_mb_no and a.lr_tsd_strtime=b.lr_tsd_strtime and b.lr_status in ('H','S')) as mem_cnt ,
		(select COALESCE(tsd_group_status,'N') from TB_schedule_day where tsd_mb_no=a.lr_lec_mb_no and tsd_strtime=a.lr_tsd_strtime) as group_status
		from TB_lec_reservation as a
		join TB_lec_main on a.lr_lec_mb_no=lm_mb_no and a.lr_lec_cd=lm_lec_cd and a.lr_lec_mb_no='{$mb->mb_no}'
		join g4_member on a.lr_mb_no=mb_no where 1=1 ";
		$query = $query . " and lr_tsd_strtime<'{$f_strtime}'";
		if ($std_so != "") {
			if ($std_so == "S") {
				$query = $query . " and lr_status='{$std_so}'";
			} else {
				$query = $query . " and lr_status IN ('F','R')";
			}
		} else {
			$query = $query . " and lr_status IN ('S','F','R')";
		}
		$query = $query . " order by lr_reg_dt desc";

		$stmt = $this->db->query ( $query );

		$ret = array ();
		if ($stmt->num_rows () > 0) {
			foreach ( $stmt->result () as $row ) {
				$item = $this->_createLectureAfterItem ( $row, $mb->mb_no );
				array_push ( $ret, $item );
			}
		}

		return $ret;
	}

    // 강의 평가 italki 버전 paging
    public function getLectureAfterByPage($mb_no, $mb_level, $page, $count, $filter) {
        $f_strtime = strtotime ( date ( 'Y-m-j H:i' ) ) + 1800000;
        $today_strtime = $f_strtime;

        // 그룹정보 취급 안함
        $this->db->join('TB_lec_main lecture', 'lecture.lm_mb_no = TB_lec_reservation.lr_lec_mb_no AND lecture.lm_lec_cd = TB_lec_reservation.lr_lec_cd ', 'LEFT');

        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            // 선생님일 경우 학생정보가저옴
            $this->db->join('g4_member member', 'member.mb_no = TB_lec_reservation.lr_mb_no', 'LEFT');
        } else {
            $this->db->join('g4_member member', 'member.mb_no = TB_lec_reservation.lr_lec_mb_no', 'LEFT');
        }

        $this->db->join('tb_code thema', 'thema.cd_code = TB_lec_reservation.lr_lec_cd', 'LEFT');
        $this->db->join('TB_after_comment comment', 'comment.ta_lr_seq = TB_lec_reservation.lr_seq', 'LEFT');

        // 선생님일 경우 신청받은 수업만 봄
        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            $this->db->where('TB_lec_reservation.lr_lec_mb_no', $mb_no);
        } else {
            // } else if($mb_level == 3) {
            $this->db->or_where('TB_lec_reservation.lr_mb_no', $mb_no);
        }

        // $this->db->where("(TB_lec_reservation.lr_lec_mb_no={$mb_no} OR TB_lec_reservation.lr_mb_no={$mb_no})", NULL, FALSE);

        switch ($filter) {
            case 1 :
                $temp_status = array('S','F','R');
                $this->db->where_in('TB_lec_reservation.lr_status', $temp_status);
                break;
            case 2 :
                $temp_status = array('S');
                $this->db->where_in('TB_lec_reservation.lr_status', $temp_status);
                break;
            case 3 :
                $temp_status = array('F','R');
                $this->db->where_in('TB_lec_reservation.lr_status', $temp_status);
                break;
        }


        // $this->db->order_by('lr_reg_dt', 'desc');
        $this->db->order_by('lr_tsd_strtime', 'desc');
        $totalCount = $this->db->get('TB_lec_reservation')->num_rows();
        $sql = $this->db->last_query();


        // data 조회
        $startIndex = $count * $page;
        $sql = $sql . " limit {$startIndex} , {$count} ";
        $lec_list = $this->db->query($sql)->result();
        // $lec_list = $this->db->get('TB_lec_reservation')->result();

        // $sql = $this->db->last_query();
        // error_log('getLectureAfterByPage query === '.$sql);

        $hasNext = true;
        if ($totalCount <= $startIndex + $count) {
            $hasNext = false;
        }


        $ret = array ();
        if (count($lec_list)) {
            foreach ( $lec_list as $row ) {
                $str_status = '';
        		$str_status_key = '';
        		switch ($row->lr_status) {
        			case "F" :
        				$str_status = 'lecon_14';

        				$query = "select ta_status from TB_after_comment where ta_lr_seq = '{$row->lr_seq}' and ta_r_code is not null";

        				$stmt = $this->db->query ( $query );

        				$str_status_key = 'showEvaluation';

        				if ($stmt->num_rows () > 0) {
        					$result = $stmt->row ();
        					if ($result->ta_status == "F") {
        						$str_status = 'lecon_30';
        						$str_status_key = 'showEvaluation';
        					} else if ($result->ta_status == "N") {
        						$str_status = 'lecon_28';
        						$str_status_key = '';
        					}
        				}

        				break;
        			case "R" :
        				$query = "select ta_status from TB_after_comment where ta_lr_seq = '{$row->lr_seq}' and ta_r_code is not null";

        				$stmt = $this->db->query ( $query );

        				if ($stmt->num_rows () > 0) {
        					$result = $stmt->row ();
        					if ($result->ta_status == "S") {
        						$str_status = 'lecon_29';
        					} else if ($result->ta_status == "F") {
        						$str_status = 'lecon_30';
        					} elseif ($result->ta_status == "N") {
        						$str_status = 'lecon_28';
        					}
        				} else {
        					$str_status = 'lecon_21';
        				}
        				break;
        			case "S" :
        				if ($row->lr_auto_koin == "Y") { // 7일이 지나 자동으로 코인이 들어갔다면
        					$str_status = 'lecon_39';
        				} else {
        					$str_status = 'lecon_3';
        				}
        				break;
        		}

        		$color = "";
        		// if(strpos($action_list, $row[lr_seq])){ $color=$action_color; }
        		// elseif(strpos($problem_list, $row[lr_seq])){ $color=$problem_color; }

        		$comm_tool_info = "";
        		if ($row->mb_comm_tool != "") {
        			$comm_tool_info = $row->mb_comm_tool . " ID : (" . $row->mb_skypeId . ")";
        		} else {
        			$comm_tool_info = "Skype ID : (" . $row->mb_skypeId . ")";
        		}

        		$row->lr_tsd_strtime_m_j = date ( 'M j', $row->lr_tsd_strtime );
        		$row->lr_tsd_strtime_h_i = date ( 'H:i', $row->lr_tsd_strtime );
        		// $row->str_type = $str_type;
        		// $row->str_mem = $str_mem;
        		$row->comm_tool_info = $comm_tool_info;
        		$row->str_status = $str_status;

        		$str_lec = $row->cd_desc;

        		if ($row->lm_lec_cd_etc != "")
        			$str_lec = $str_lec . " (" . $row->lm_lec_cd_etc . ")";

        		$item = array(
        				'str_status' => $str_status,
        				// 'str_status_key' => $str_status_key,
        				'comm_tool_info' => $comm_tool_info,
        				// 'str_type' => $str_type,
        				// 'str_mem' => $str_mem,
        				'lr_tsd_strtime' => $row->lr_tsd_strtime,
        				'lr_seq' => $row->lr_seq,
                'mb_no' => $row->mb_no,
        				'mb_nick' => $row->mb_nick,
        				'mb_profile' => $row->mb_profile,
        				'lr_real_koin' => $row->lr_real_koin,
                'lr_status' => $row->lr_status,
                'lr_mb_no' => $row->lr_mb_no,
                'lr_lec_mb_no' => $row->lr_lec_mb_no,
                'lr_group_yn' => $row->lr_group_yn,
                'lr_tlp_id' => $row->lr_tlp_id,
								'lr_auto_koin' => $row->lr_auto_koin,
                'cd_code' => $row->cd_code,
        				'str_lec' => $str_lec,
                'lm_lec_cd_etc' => $row->lm_lec_cd_etc,
                'ta_status' => $row->ta_status,
								'ta_r_code' => $row->ta_r_code,
                'ta_teacher_comment' => $row->ta_teacher_comment,
        		);

                array_push ( $ret, $item );
            }
        }

        // $ret = array ();
        // if ($stmt->num_rows () > 0) {
        //     foreach ( $stmt->result () as $row ) {
        //         $item = $this->_createLectureAfterItem ( $row, $mb_no );
        //         array_push ( $ret, $item );
        //     }
        // }

        return array (
                'meta' => array (
                        'hasNext' => $hasNext
                ),
                'data' => $ret
        );
    }

	// not use
	// public function getLectureAfterByPage($mb_no, $page, $count, $filter) {
	// 	$f_strtime = strtotime ( date ( 'Y-m-j H:i' ) ) + 1800000;
	// 	$today_strtime = $f_strtime;
    //
	// 	$query = "select *,
    //     (select count(*) as cnt from TB_lec_reservation as b where a.lr_mb_no=b.lr_mb_no and a.lr_tsd_strtime=b.lr_tsd_strtime and b.lr_status in ('H','S')) as mem_cnt ,
	// 	(select COALESCE(tsd_group_status,'N') from TB_schedule_day where tsd_mb_no=a.lr_lec_mb_no and tsd_strtime=a.lr_tsd_strtime) as group_status
	// 	from TB_lec_reservation as a
	// 	join TB_lec_main on a.lr_lec_mb_no=lm_mb_no and a.lr_lec_cd=lm_lec_cd and a.lr_lec_mb_no='{$mb_no}'
	// 	join g4_member on a.lr_mb_no=mb_no where 1=1 ";
    //
	// 	// $query = $query . " and lr_tsd_strtime<'{$f_strtime}'";
    //
	// 	switch ($filter) {
	// 		case 1 :
	// 			$query .= " and lr_status IN ('S','F','R') ";
	// 			break;
	// 		case 2 :
	// 			$query .= " and lr_status = 'S' ";
	// 			break;
	// 		case 3 :
	// 			$query .= " and lr_status IN ('F','R') ";
	// 			break;
	// 	}
    //
	// 	$startIndex = $count * $page;
    //
	// 	$query .= " order by lr_reg_dt desc ";
    //
	// 	$totalCount = $this->db->query ( $query )->num_rows ();
    //
	// 	$query .= " limit {$startIndex} , {$count} ";
    //
	// 	$hasNext = true;
	// 	if ($totalCount <= $startIndex + $count) {
	// 		$hasNext = false;
	// 	}
    //
	// 	$stmt = $this->db->query ( $query );
    //
    //     $sql = $this->db->last_query();
    //     error_log('getLectureAfterByPage query === '.$sql);
    //
	// 	$ret = array ();
	// 	if ($stmt->num_rows () > 0) {
	// 		foreach ( $stmt->result () as $row ) {
	// 			$item = $this->_createLectureAfterItem ( $row, $mb_no );
	// 			array_push ( $ret, $item );
	// 		}
	// 	}
    //
	// 	return array (
	// 			'meta' => array (
	// 					'hasNext' => $hasNext
	// 			),
	// 			'data' => $ret
	// 	);
	// }

	// 강의 생성의 설정 정보
	public function getSetting($mb) {
		$query = "select * from TB_lec_main where lm_mb_no='{$mb->mb_no}' order by lm_sort asc, lm_seq asc";
		$stmt = $this->db->query ( $query );

		// 생성된 테마 리스트
		$themaSetting = array ();
		if ($stmt->num_rows () > 0) {
			foreach ( $stmt->result () as $row ) {
				$item = $this->_createThemaItem ( $row, $mb );
				array_push ( $themaSetting, $row );
			}
		}

		$lm_group_yn = 'N';
		if (count ( $themaSetting ) > 0) {
			$lm_group_yn = $themaSetting [0]->lm_group_yn;
		}

		// 테마 전체 목록
		$sql = "select * from tb_code where cd_type='LT' order by 3";
		$stmt = $this->db->query ( $sql );
		$themaTotal = $stmt->result ();

		// 강의 시작 종료 날짜
		$query = "select * from  TB_lec_term where mb_no='{$mb->mb_no}'";
		$stmt = $this->db->query ( $query );
		if ($stmt->num_rows () > 0) {
			$row = $stmt->row ();
			$std_date = date ( 'Y-m-d', $row->mb_std_date );
			$end_date = date ( 'Y-m-d', $row->mb_end_date );
		} else {
			$std_date = date ( 'Y-m-d', strtotime ( '0 day', strtotime ( date ( 'Y-m-j' ) ) ) );
			$end_date = date ( 'Y-m-d', strtotime ( '3 month', strtotime ( $std_date ) ) );
		}

		return array (
				'lm_group_yn' => $lm_group_yn,
				'themaList' => $themaSetting,
				'themaTotal' => $themaTotal,
				'schedule' => array (
						'start' => $std_date,
						'end' => $end_date
				)
		);
	}

    private function _createLecturePageItem($row, $mb_no) {
		if ($row->group_status != "N" || $row->mem_cnt > 1) {
			$str_type = "Group";
			$str_mem = $row->mem_cnt . ":" . $row->lm_lec_mem;
		} else {
			$str_type = "1:1";
			$str_mem = "1";
		}

		$query2 = "select *, (select cd_desc from tb_code where cd_code=lm_lec_cd) as lm_lec_desc from TB_lec_main where lm_mb_no='{$mb_no}' and lm_lec_cd = '" . $row->lm_lec_cd . "' ";
		$stmt = $this->db->query ( $query2 );

		$rs2 = $stmt->row ();
		$str_lec = $rs2->lm_lec_desc;
		if ($rs2->lm_lec_cd_etc != "")
			$str_lec = $str_lec . " (" . $rs2->lm_lec_cd_etc . ")";

		$color = "";

		$comm_tool_info = "";
		if ($row->lr_comm_tool != "") {
			$comm_tool_info = $row->lr_comm_tool . " ID : (" . $row->lr_skypeId . ")";
		} else {
			$comm_tool_info = $row->mb_skypeId;
		}

		return array (
				'color' => $color,
				'lr_seq' => $row->lr_seq,
				'str_lec' => $str_lec,
				'lr_tsd_strtime' => ( int ) $row->lr_tsd_strtime,
				'str_type' => $str_type,
				'str_mem' => $str_mem,
				// 'str_link' => $str_link,
				'comm_tool_info' => $comm_tool_info,
				// 'str_status' => $str_status,
				// 'str_img' => $str_img,
				'lr_real_koin' => $row->lr_real_koin,
				'mb_nick' => $row->mb_nick,
				'mb_no' => $row->mb_no,
				'mb_profile' => $row->mb_profile,
				'lr_tsd_strtime_m_j' => date ( 'M j', $row->lr_tsd_strtime ),
				'lr_tsd_strtime_h_i' => date ( 'H:i', $row->lr_tsd_strtime ),
				'lr_status' => $row->lr_status,
				'lr_tsd_strtime' => $row->lr_tsd_strtime,
				'lr_auto_koin' => $row->lr_auto_koin,
                'lr_lec_mb_no' => $row->lr_lec_mb_no,
		);
	}
	private function _createLectureAfterItem($row, $mb_no) {
		if ($row->group_status != "N" || $row->mem_cnt > 1) {
			$str_type = "Group";
			$str_mem = $row->mem_cnt . ":" . $row->lm_lec_mem;
		} else {
			$str_type = "1:1";
			$str_mem = "1";
		}

		$str_status = '';
		$str_status_key = '';
		switch ($row->lr_status) {
			case "F" :
				$str_status = 'lecon_14';

				$query = "select ta_status from TB_after_comment where ta_lr_seq = '{$row->lr_seq}' and ta_r_code is not null";

				$stmt = $this->db->query ( $query );

				$str_status_key = 'showEvaluation';

				if ($stmt->num_rows () > 0) {
					$result = $stmt->row ();
					if ($result->ta_status == "F") {
						$str_status = 'lecon_30';
						$str_status_key = 'showEvaluation';
					} else if ($result->ta_status == "N") {
						$str_status = 'lecon_28';
						$str_status_key = '';
					}
				}

				break;
			case "R" :
				$query = "select ta_status from TB_after_comment where ta_lr_seq = '{$row->lr_seq}' and ta_r_code is not null";

				$stmt = $this->db->query ( $query );

				if ($stum->num_rows () > 0) {
					$result = $stmt->row ();
					if ($result->ta_status == "S") {
						$str_status = 'lecon_29';
					} else if ($result->ta_status == "F") {
						$str_status = 'lecon_30';
					} elseif ($result->ta_status == "N") {
						$str_status = 'lecon_28';
					}
				} else {
					$str_status = 'lecon_21';
				}
				break;
			case "S" :
				if ($row->lr_auto_koin == "Y") { // 7일이 지나 자동으로 코인이 들어갔다면
					$str_status = 'lecon_39';
				} else {
					$str_status = 'lecon_3';
				}
				break;
		}

		$color = "";
		// if(strpos($action_list, $row[lr_seq])){ $color=$action_color; }
		// elseif(strpos($problem_list, $row[lr_seq])){ $color=$problem_color; }

		$comm_tool_info = "";
		if ($row->mb_comm_tool != "") {
			$comm_tool_info = $row->mb_comm_tool . " ID : (" . $row->mb_skypeId . ")";
		} else {
			$comm_tool_info = "Skype ID : (" . $row->mb_skypeId . ")";
		}

		$row->lr_tsd_strtime_m_j = date ( 'M j', $row->lr_tsd_strtime );
		$row->lr_tsd_strtime_h_i = date ( 'H:i', $row->lr_tsd_strtime );
		$row->str_type = $str_type;
		$row->str_mem = $str_mem;
		$row->comm_tool_info = $comm_tool_info;
		$row->str_status = $str_status;
		return array(
				'str_status' => $str_status,
				'comm_tool_info' => $comm_tool_info,
				'str_type' => $str_type,
				'str_mem' => $str_mem,
				'lr_tsd_strtime' => $row->lr_tsd_strtime,
				'lr_seq' => $row->lr_seq,
				'mb_nick' => $row->mb_nick,
				'mb_profile' => $row->mb_profile,
				'lr_real_koin' => $row->lr_real_koin,
				'str_status_key' => $str_status_key
		);
	}
	private function _createCategory($queryString) {
		$stmt = $this->db->query ( $queryString );

		$cnt = $stmt->num_rows ();

		return array (
				'count' => $cnt
		);
	}
	private function _createThemaItem($row, $mb) {
		// $str_mb_thema = "/\.(".$member[mb_thema].")$/i";
		// $sql="select * from tb_code where cd_type='LT' order by 3";
		// $result=mysql_query($sql);
		// $cnt=1;
		// while($data=mysql_fetch_array($result)){
		// switch($lang_pack){
		// case "kr":
		// $str_desc=$data[3];
		// break;
		// case "en":
		// $str_desc=$data[4];
		// break;
		// case "jp":
		// $str_desc=$data[4];
		// break;
		// case "cn":
		// $str_desc=$data[4];
		// break;
		// }
		// }
		$row->selectedThema = $row->lm_lec_cd;

		return $row;
	}

	function getThemeList() {
        // lecture type?
        $this->db->select('cd_code, cd_desc');
        $this->db->where('cd_type', 'LT');
        $this->db->order_by('cd_code', 'ASC');

        return $this->db->get('tb_code')->result();

        // $sql = "select * from tb_code where cd_type='LT' order by cd_code";
		// $stmt = $this->db->query ( $sql );
		// return $stmt->result ();
    }

    function getRefundConditionList() {
        // lecture type?
        $this->db->select('cd_code, cd_desc, cd_desc_eng');
        $this->db->where('cd_type', 'RE');
        $this->db->order_by('cd_code', 'ASC');

        return $this->db->get('tb_code')->result();

        // $sql = "select * from tb_code where cd_type='LT' order by cd_code";
        // $stmt = $this->db->query ( $sql );
        // return $stmt->result ();
    }


    function getLectureSchedule($mb_no, $startTime, $endTime) {
        //$query = "select tsd_strtime from TB_schedule_day where tsd_mb_no='{$member[mb_no]}' and tsd_strtime between {$startTime} and {$endTime} order by 1";

        $this->db->select('tsd_strtime');
        $this->db->where('tsd_mb_no', $mb_no);
        $this->db->where('tsd_strtime >=', $startTime);
        $this->db->where('tsd_strtime <=', $endTime);
        $this->db->order_by('tsd_strtime', 'ASC');

        $result = $this->db->get('TB_schedule_day')->result();

        // $sql = $this->db->last_query();
        // error_log('scheduel query === '.$sql);
        return $result;
    }


    // 새로운 강의 테마 그룹설정은 사용안한다.
    function insertLecture($mb_no, $lecture) {

        $params = array(
            'lm_mb_no' => $mb_no,
            'lm_lec_cd' => $lecture->lm_lec_cd,
            'lm_lec_cd_etc' => $lecture->lm_lec_cd_etc,
            'lm_group_yn' => 'N',
            'lm_payment' => $lecture->lm_payment,
            'lm_group_payment' => 0,
            'lm_lec_mem' => 0,
            'lm_sort' => 0,
            'lm_package_count' => $lecture->lm_package_count,
            'lm_package_payment' => $lecture->lm_package_payment,
        );

        $this->db->insert('TB_lec_main', $params);
    }

    // 테마는 변하지 않음
    function updateLecture($mb_no, $lecture) {
        $ids = array('lm_mb_no' => $mb_no, 'lm_seq' => $lecture->lm_seq);

        // error_log('$lecture->lm_package_count : '.$lecture->lm_package_count);

        $params = array(
						'lm_sort' => $lecture->lm_sort,
            'lm_lec_cd_etc' => $lecture->lm_lec_cd_etc,
            'lm_payment' => $lecture->lm_payment,
            'lm_package_count' => $lecture->lm_package_count,
            'lm_package_payment' => $lecture->lm_package_payment
        );

        $this->db->update('TB_lec_main', $params, $ids);
    }

    function removeLecture($mb_no, $lm_seq) {
        $params = array(
            'lm_mb_no' => $mb_no,
            'lm_seq' => $lm_seq);

        $this->db->where($params);
        $this->db->delete('TB_lec_main');
    }

    function getWeeklyTime($mb_no) {
        // TB_schedule_week where tsw_mb_no='{$member[mb_no]}' and tsw_week='{$set_position}' order by 1";

        // $this->db->select('tsd_strtime');
        $this->db->where('tsw_mb_no', $mb_no);
        $this->db->order_by('tsw_week asc, tsw_time_term asc');

        $result = $this->db->get('TB_schedule_week')->result();

        return $result;
    }

    function saveWeeklyTimes($mb_no, $tsw_week, $tsw_time_term, $tsw_start_date, $tsw_end_date) {

        $searchIds = array(
            'tsw_mb_no' => $mb_no,
            'tsw_week' => $tsw_week,
            'tsw_time_term' => $tsw_time_term
        );

        $params = array(
            'tsw_mb_no' => $mb_no,
            'tsw_week' => $tsw_week,
            'tsw_time_term' => $tsw_time_term,
            'tsw_start_date' => $tsw_start_date,
            'tsw_end_date' => $tsw_end_date,
        );

        $count = $this->db->get_where('TB_schedule_week',  $searchIds)->num_rows();

        if ($count == 0) {
            $this->db->insert('TB_schedule_week', $params);
        }

        // error_log('timeterm =  '.$tsw_time_term.'. count = '.$count);
    }

    function removeWeeklyTimes($mb_no, $tsw_week, $tsw_start_date, $tsw_end_date) {

        $params = array(
            'tsw_mb_no' => $mb_no,
            'tsw_week' => $tsw_week,
            'tsw_time_term >=' => $tsw_start_date,
            'tsw_time_term <=' => $tsw_end_date
        );

        // $count = $this->db->get_where('TB_schedule_week', $params)->num_rows();
        // error_log('timeterm =  '.$tsw_week.'. count = '.$count);

        $this->db->where($params);
        $this->db->delete('TB_schedule_week');
    }

    function getExceptScheduleList($mb_no) {
        return $this->db->get_where('tb_schedule_except', array('tse_mb_no' => $mb_no))->result();
    }

    function newExceptSchedule($mb_no, $start_date, $end_date, $tse_type) {
        $params = array(
            'tse_mb_no' => $mb_no,
            'tse_start_date' => $start_date,
            'tse_end_date' => $end_date,
            'tse_type' => $tse_type
        );

        $this->db->insert('tb_schedule_except', $params);
    }

    function removeExceptSchedule($tse_id) {
        $params = array(
            'tse_id' => $tse_id
        );

        $this->db->where($params);
        $this->db->delete('tb_schedule_except');
    }

    // 해당 강의정보, 강의자 정보
    function getLectureInfo($params, $select_column) {
        $this->db->select($select_column.', cd_desc');
        // $query = "select * from TB_lec_main where lm_mb_no='{$mb->mb_no}' order by lm_sort desc, lm_seq asc";
        $this->db->join('g4_member teacher', 'teacher.mb_no = TB_lec_main.lm_mb_no', 'LEFT');
        $this->db->where($params);

        // 수업 이름
        $this->db->join('tb_code thema', 'thema.cd_code = lm_lec_cd', 'LEFT');

        return $this->db->get('TB_lec_main')->row();
    }

    function getLectureCodeInfo($cd_code) {
        return $query = $this->db->get_where('tb_code', array('cd_code' => $cd_code))->row();
    }

    // 수업 일정
    function getUserSchedule($params, $mb_level, $isOnlyReservation = false) {

        $ret = array();
        if ($mb_level == 5 || $mb_level == 6 || $mb_level == 7) {
            // 강사 수업일정
            $schedule_day_col = 'tsd_group_status, tsd_mb_no, tsd_single_status, tsd_strtime, tsd_time_add_flag';
            $member_col = 'mb_nick';
            $resevation_col = 'lr_seq, lr_group_yn, lr_status';
            $this->db->select($schedule_day_col.' , '.$resevation_col.' , '.$member_col);


            $this->db->join('TB_lec_reservation reservation', 'reservation.lr_tsd_strtime = TB_schedule_day.tsd_strtime AND reservation.lr_lec_mb_no = TB_schedule_day.tsd_mb_no', 'LEFT');
            $this->db->join('g4_member member', 'member.mb_no = reservation.lr_mb_no', 'LEFT');


            $this->db->where($params);

            // 이미 기존에 만들어진 스케쥴의 경우 비어있는 스케쥴도 미리 생성해버림
            // 생성된 비어잇는 스케쥴의경우 tsd_single_status 값이 NULL
            // 헷갈리니 tsd_group_status은 제외하고 생각
            if ($isOnlyReservation) {
                $this->db->where("tsd_single_status IS NOT NULL");
                // $this->db->where('tsd_single_status IS NOT NULL', null, false);
                // $this->db->where('tsd_group_status IS NOT NULL', null, false);
            }

            $ret = $this->db->get('TB_schedule_day')->result();
            // $sql = $this->db->last_query();
            // error_log('scheduel query === '.$sql);
        } else {
            // 학생 일정
            $student_search = array(
                'lr_mb_no' => $params['tsd_mb_no'],
                'lr_tsd_strtime >=' => $params['tsd_strtime >='],
                'lr_tsd_strtime <=' => $params['tsd_strtime <='],
            );

            $this->db->join('g4_member member', 'member.mb_no = lr_lec_mb_no', 'LEFT');
            $this->db->where($student_search);

            $rows = $this->db->get('TB_lec_reservation')->result();
            foreach ( $rows as $reservation ) {

                $item = array(
                    'lr_status' => $reservation->lr_status,
                    'lr_seq' => $reservation->lr_seq,
                    'mb_nick' => $reservation->mb_nick,
                    'lr_group_yn' => $reservation->lr_group_yn,
                    'tsd_group_status' => '',
                    'tsd_mb_no' => $reservation->lr_lec_mb_no,
                    'tsd_single_status' => '',
                    'tsd_strtime' => $reservation->lr_tsd_strtime,
                    'tsd_time_add_flag' => '',
                );

                array_push ( $ret, $item );
            }

        }


        // $sql = $this->db->last_query();
        // error_log('scheduel query === '.$sql);

        return $ret;
    }

    function getScheduleDetail($lr_seq) {

        $member_col = 'taecher.mb_nick as teacher_name, taecher.mb_profile as teacher_profile, member.mb_nick as member_name, member.mb_profile as member_profile, member.mb_skypeId as member_skypeId';
        $resevation_col = 'lr_seq, lr_lec_cd, lr_tsd_strtime, lr_package_koin, lr_group_yn, lr_tlp_id, lr_status, lr_comm_tool, lr_skypeId';
        $this->db->select($resevation_col.' , '.$member_col);

        $this->db->join('g4_member taecher', 'taecher.mb_no = lr_lec_mb_no', 'LEFT');
        $this->db->join('g4_member member', 'member.mb_no = lr_mb_no', 'LEFT');
        $this->db->where(array('lr_seq' => $lr_seq));
        $ret = $this->db->get('TB_lec_reservation')->row();
        // $sql = $this->db->last_query();
        // error_log('scheduel query === '.$sql);

        return $ret;
    }

    function saveScheduleDay($tsd_mb_no, $tsd_strtime, $tsd_time_add_flag = 'W') {
        // 중복검사는 저장 이전에 하고 옴.
        // 이미 저장된 일정이 있으면 update
        $this->db->where(array('tsd_mb_no' => $tsd_mb_no, 'tsd_strtime' => $tsd_strtime));
        $find_schedule = $this->db->get('TB_schedule_day')->result();

        date_default_timezone_set('UTC');

        if (count($find_schedule)) {
            $params = array(
                'tsd_single_status' => date("Y-m-d H:i:s")
            );

            $where_ids = array(
                'tsd_mb_no' => $tsd_mb_no,
                'tsd_strtime' => $tsd_strtime
            );

            $this->db->update('TB_schedule_day', $params, $where_ids);
        } else {
            $params = array(
                'tsd_mb_no' => $tsd_mb_no,
                'tsd_strtime' => $tsd_strtime,
                'tsd_time_add_flag' => $tsd_time_add_flag, // 뭔지 모름
                'tsd_single_status' => date("Y-m-d H:i:s")
            );

            $this->db->insert('TB_schedule_day', $params);
        }
    }

    function emptyScheduleDay($tsd_mb_no, $tsd_strtime) {
        // 중복검사는 저장 이전에 하고 옴.
        // 이미 저장된 일정이 있으면 update
        $this->db->set('tsd_single_status', NULL);
        $this->db->set('tsd_group_status', NULL);
        $this->db->where(array('tsd_mb_no' => $tsd_mb_no, 'tsd_strtime' => $tsd_strtime));
        $this->db->update('TB_schedule_day');
    }

    function getPackageDetail($tlp_id) {
        $member_col = 'taecher.mb_nick as teacher_name, taecher.mb_profile as teacher_profile, taecher.mb_skypeId as teacher_skypeId, taecher.mb_time as teacher_timezone,'
                .'member.mb_nick as member_name, member.mb_profile as member_profile, member.mb_skypeId as member_skypeId';
        $package_col = 'tlp_id, tlp_lec_cd, tlp_lm_seq, tlp_koin, tlp_mb_no, tlp_lec_mb_no, tlp_max_count, tlp_remain_count, tlp_reg_dt, tlp_suc_dt, tlp_can_dt, tlp_status';
        $this->db->select($package_col.' , '.$member_col);

        $this->db->join('g4_member taecher', 'taecher.mb_no = tlp_lec_mb_no', 'LEFT');
        $this->db->join('g4_member member', 'member.mb_no = tlp_mb_no', 'LEFT');

        $this->db->where(array('tlp_id' => $tlp_id));
        return $this->db->get('tb2_lec_package')->row();
    }

    function insertPackage($params) {
        $this->db->insert('tb2_lec_package', $params);
        $tlp_id = $this->db->insert_id();

        return $tlp_id;
    }

    function updatePackage($params, $where_ids) {
        $this->db->update('tb2_lec_package', $params, $where_ids);
        // $sql = $this->db->last_query();
        // error_log('update reservation === '.$sql);
    }

    function insertReservation($params) {
        // insert reservation
        $this->db->insert('TB_lec_reservation', $params);
        $lr_seq = $this->db->insert_id();

        return $lr_seq;
    }

    // 수강 예약 리스트
    function getLectureReservation($params, $lr_status) {
        // error_log('call getLectureReservation !!!!!!'.json_encode($lr_status));
        $this->db->where($params);
        if (isset($lr_status)) {
            // error_log('call is set! lrstatus');
            $this->db->where_in('lr_status', $lr_status);
        }

        $this->db->order_by('lr_reg_dt', 'asc');
        return $this->db->get('TB_lec_reservation')->result();
    }


    function getReservationDetail($lr_seq) {
        $this->db->where(array('lr_seq' => $lr_seq));
        return $this->db->get('TB_lec_reservation')->row();
    }

    function updateReservation($params, $where_ids, $where_in = NULL) {
        $this->db->where($where_ids);
        if ($where_in != NULL) {
            $this->db->where_in('lr_status', $where_in);
        }

        $this->db->update('TB_lec_reservation', $params);

        if(isset($where_ids['lr_seq'])){
            $reservationInfo =  (object)$this->db->get_where('TB_lec_reservation', array('lr_seq' => $where_ids['lr_seq']))->row();
            // error_log('$where_ids  === '.json_encode($where_ids));
            // 일정이 취소되면 스케쥴 삭제
            // error_log('$params[lr_status]=== '.$params[lr_status]);
            if($reservationInfo->lr_status == 'C' || $reservationInfo->lr_status == 'D' ) {
                $params = array(
                    'tsd_mb_no' => $reservationInfo->lr_lec_mb_no,
                    'tsd_strtime' => $reservationInfo->lr_tsd_strtime);
                $this->db->where($params);
                $this->db->delete('TB_schedule_day');
            }
        }

        // $sql = $this->db->last_query();
        // error_log('update reservation === '.$sql);
    }

    function insertPaymentHistory($params) {
        $this->db->insert('Tb_payment_history', $params);
    }

    function insertPayment($params) {
        $this->db->insert('Tb_payment', $params);
    }

    function getRealKoinValue($ta_lr_seq) {
        $this->db->select('lr_real_koin');
        return $this->db->get_where('TB_lec_reservation', array('lr_seq' => $ta_lr_seq))->row()->lr_real_koin;
    }


    function getThemaTotal() {
        $sql = "select * from tb_code where cd_type='LT' order by 3";
        $stmt = $this->db->query ( $sql );
        return $stmt->result ();
    }


    function getCommentList($lr_seq) {
        $member_col = 'taecher.mb_nick as teacher_name, taecher.mb_profile as teacher_profile, '
                .'member.mb_nick as member_name, member.mb_profile as member_profile';
        $comment_col = 'ta_score, ta_lec_mb_no, ta_mb_no, ta_commnet, ta_reg_dt, ta_r_code, ta_status, ta_teacher_comment, ta_teacher_dt';
        $this->db->select($comment_col.' , '.$member_col);

        $this->db->join('g4_member member', 'member.mb_no = ta_mb_no', 'LEFT');
        $this->db->join('g4_member taecher', 'taecher.mb_no = ta_lec_mb_no', 'LEFT');
        $this->db->where(array('ta_lr_seq' => $lr_seq));
        return $this->db->get('TB_after_comment')->result();

        // $sql = $this->db->last_query();
        // error_log('scheduel query === '.$sql);
    }
}
