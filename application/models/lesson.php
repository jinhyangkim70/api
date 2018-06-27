<?php
class lesson extends CI_Model {
	function __construct() {
		// Call the Model constructor
		parent::__construct ();
	}

	// 선생님  폐이지의 session 취소 사유
	public function getCancelReason($seq, $lang) {
		$query = "select * from TB_lec_reservation join tb_code on lr_can_memo=cd_code where lr_seq='{$seq}' ";
		$stmt = $this->db->query ( $query );
		$rowCount = $stmt->num_rows ();

		$query2 = "select lr_can_memo_txt from TB_lec_reservation where lr_seq='{$seq}' and lr_can_memo_txt is not null ";
		$stmt2 = $this->db->query ( $query2 );
		$rowCount2 = $stmt2->num_rows ();

		$str_desc = " ";

		if($rowCount > 0){

			$row = $stmt->row();

			if ($lang == "kr"){
				$str_desc = $row->cd_desc;
			}else{
				$str_desc = $row->cd_desc_eng;
			}
		}

		if($rowCount2 > 0){
			$row = $stmt2->row();
			$str_desc = $row->lr_can_memo_txt;
		}

		return array(
				'desc' => $str_desc
		);
	}

	// 선생님 폐이지의  lesson 완료후 평가 정보
	public function getAfterPoint($seq) {
		$query = "select * from TB_after_comment where ta_lr_seq = '{$seq}' ";
		$stmt = $this->db->query($query);

		if ($stmt->num_rows() > 0) {
			$row = $stmt->row();

			return array(
                    'ta_r_code' => $row->ta_r_code,
                    'ta_status' => $row->ta_status,
					'ta_score' => $row->ta_score,
					'ta_lec_mb_no' => $row->ta_lec_mb_no,
					'ta_commnet' => $row->ta_commnet,
                    'ta_teacher_comment' => $row->ta_teacher_comment,
			);
		} else {
			return array (
                    'ta_r_code' => null,
                    'ta_status' => 0,
					'ta_score' => 0,
					'ta_lec_mb_no' => 0,
					'ta_commnet' => '',
                    'ta_teacher_comment' => '',
			);
		}
	}


	// 선생님 폐이지의  lesson 완료후 평가
	public function finishLectureAndComment($mb_no, $ta_lec_mb_no, $ta_lr_seq, $memo_content, $ta_score) {

        // 0. 선생님 정보 가저옴
        $this->db->select('mb_level');
        $this->db->where(array('mb_no' => $ta_lec_mb_no));
        $teacher = $this->db->get('g4_member')->row();


				// 학생
				$this->db->select('mb_email');
        $this->db->where(array('mb_no' => $mb_no));
        $student = $this->db->get('g4_member')->row();

        date_default_timezone_set('UTC');
				// 0. 코멘트가 이미 추가된 것인가

        $this->db->where(array('ta_mb_no' => $mb_no, 'ta_lr_seq' => $ta_lr_seq));
				$this->db->from('TB_after_comment');
        $find_comment_count = $this->db->count_all_results();

				// error_log('find_comment : '.$find_comment_count);
				// error_log('is_null : '.is_null($find_comment_count));
				// error_log('isset  : '.isset ($find_comment_count));

				if ($find_comment_count == 0) {

					        // 1. 코멘트 추가
					        $commment_params = array(
					            'ta_mb_no' => $mb_no,
					            'ta_lec_mb_no' => $ta_lec_mb_no,
					            'ta_lr_seq' => $ta_lr_seq,
					            'ta_commnet' => $memo_content,
					            'ta_score' => $ta_score,
					            'ta_status' => 'F',
					            'ta_reg_dt' => date("Y-m-d H:i:s"),
					        );
					        $this->db->insert('TB_after_comment', $commment_params);

					        // 2. 예약테이블 상태 업데이트
					        $reserv_params = array(
					            'lr_status' => 'F'
					        );
					        $reserv_condition = array(
					            'lr_seq' => $ta_lr_seq,
					            'lr_mb_no' => $mb_no,
					            'lr_lec_mb_no' => $ta_lec_mb_no
					        );
					        $this->db->update('TB_lec_reservation', $reserv_params, $reserv_condition);


					        // 수수료 계산
					        // $this->db->select('lr_real_koin');
									$lec_reserv = $this->db->get_where('TB_lec_reservation', array('lr_seq' => $ta_lr_seq))->row();
					        $lr_real_koin = $lec_reserv->lr_real_koin;
									$lr_group_yn = $lec_reserv->lr_group_yn;
									$lr_auto_koin = $lec_reserv->lr_auto_koin;


									// error_log('lec_reserv : '.json_encode($lec_reserv));

					        // 패키지는 realKoin 값은 0
					        // 패키지는 payment 진행 안한다.
					        $KOIN_fee = 0;
					        // error_log('lr_real_koin'.$lr_real_koin);

									// 패키지 강의가 아니고, 자동으로 지급된게 아니라면 - _auto_koin_insert.php
									// 시범강의도 처리해줌
					        if ($lr_group_yn != 'Y' && $lr_auto_koin != 'Y') {

											// error_log('student : '.json_encode($student));

											// 초대된 사람이 처음 수강시
											if ($student->mb_email) {
												$mb_no = $this->user->checkInviteUserIncome($student->mb_email, $mb_no);
											}

					            // error_log('$teacher '.$ta_lec_mb_no);
					            // error_log('$teacher->mb_level'.$teacher->mb_level);

					            /**
					             * 5 일반 강사 수수료 변경 ? 20%
					             * 6 스타 강사 - 인기강사로 워딩 변경 수수료 변경? 7%
					             * 7 프로 강사 - 전문강사로 워딩 변경 수수료 변경 ? 10%
					             * @var [type]
					             */
					            switch ($teacher->mb_level) {
							        		case 5:
							        			$KOIN_fee=0.2;
							        			break;
							        		case 6:
							        			$KOIN_fee=0.07;
							        			break;
							        		case 7:
							        			$KOIN_fee=0.1;
							        			break;
							    		}


					            // error_log('KOIN_fee'.$KOIN_fee);
											// 17/10/22 - 0원이어도 강의 평가 이체
											if ($lr_real_koin == 0) {
												$fee_koin = 0;
												$input_koin = 0;
											} else {
												$fee_koin = intval($lr_real_koin * $KOIN_fee);
												$input_koin = $lr_real_koin - $fee_koin;
											}


					            // 3. 수수료 제외하고 선생님 에게 입금
					            $payment_params = array(
					                'tp_mb_no' => $ta_lec_mb_no,
					                'tp_lr_seq' => $ta_lr_seq,
					                'tp_mode' => 'I',
					                'tp_in_koin' => $input_koin,
					                'tp_reg_dt' => date("Y-m-d H:i:s"),
					                'tp_memo' => 'Lecture income',
					            );
					            $this->db->insert('Tb_payment', $payment_params);


					            // 4. 수수료 시스템에 기록
					            $payment_fee_params = array(
					                'tp_mb_no' => '0',
					                'tp_lr_seq' => $ta_lr_seq,
					                'tp_mode' => 'I',
					                'tp_in_koin' => $fee_koin,
					                'tp_reg_dt' => date("Y-m-d H:i:s"),
					                'tp_memo' => 'Lecture fee',
					            );
					            $this->db->insert('Tb_payment', $payment_fee_params);

							        // 5. 코인관리 히스토리에 추가
							        $payment_fee_params = array(
							            'ph_lr_seq' => $ta_lr_seq,
							            'ph_memo' => '강의평가완료',
							            'ph_reg_dt' => date("Y-m-d H:i:s")
							        );

							        $this->db->insert('Tb_payment_history', $payment_fee_params);
					        }





					        // 6. 패키지 강의 일때 다른 해당 패키지 내의 모든 강의 lr_status 가'F'인 갯수가 tlp_max_count와 일치하면 paynemt 처리
					        $lecture_detail = $this->db->get_where('TB_lec_reservation', array('lr_seq' => $ta_lr_seq))->row();

					        // error_log('lr_tlp_id == '.$lecture_detail->lr_tlp_id);
					        if ($lecture_detail->lr_group_yn == 'P') {
					            $this->db->where(array('tlp_id' => $lecture_detail->lr_tlp_id));
					            $package = $this->db->get('tb2_lec_package')->row();

					            // 완료된 강의 갯수
					            $this->db->where(array('lr_tlp_id' => $lecture_detail->lr_tlp_id, 'lr_status' => 'F'));
					            $this->db->from('TB_lec_reservation');
					            $finish_count = $this->db->count_all_results();

					            $sql = $this->db->last_query();
					            if ($package->tlp_max_count == $finish_count && $package->tlp_status=='S') {

												switch ($teacher->mb_level) {
														case 5:
															$KOIN_fee=0.2;
															break;
														case 6:
															$KOIN_fee=0.07;
															break;
														case 7:
															$KOIN_fee=0.1;
															break;
												}

												// error_log('KOIN_fee'.$KOIN_fee);
					                $fee_koin = intval($package->tlp_koin * $KOIN_fee);
					    						$input_koin = $package->tlp_koin - $fee_koin;

					                // 3. 수수료 제외하고 선생님 에게 입금
					                $payment_params = array(
					                    'tp_mb_no' => $ta_lec_mb_no,
					                    // 'tp_lr_seq' => $ta_lr_seq,
					                    'tp_tlp_id' => $lecture_detail->lr_tlp_id,
					                    'tp_mode' => 'I',
					                    'tp_in_koin' => $input_koin,
					                    'tp_reg_dt' => date("Y-m-d H:i:s"),
					                    'tp_memo' => 'Package income',
					                );
					                $this->db->insert('Tb_payment', $payment_params);


					                // 4. 수수료 시스템에 기록
					                $payment_fee_params = array(
					                    'tp_mb_no' => '0',
					                    // 'tp_lr_seq' => $ta_lr_seq,
					                    'tp_tlp_id' => $lecture_detail->lr_tlp_id,
					                    'tp_mode' => 'I',
					                    'tp_in_koin' => $fee_koin,
					                    'tp_reg_dt' => date("Y-m-d H:i:s"),
					                    'tp_memo' => 'Package fee',
					                );
					                $this->db->insert('Tb_payment', $payment_fee_params);

					                // save Tb_payment_history
					                $history_params = array(
					                    // 'ph_lr_seq' => $reservation_id,
					                    'ph_tlp_id' => $lecture_detail->lr_tlp_id,
					                    'ph_memo' => '패키지 강의 완료',
					                    'ph_reg_dt' => date("Y-m-d H:i:s"),
					                );

					                $this->db->insert('Tb_payment_history', $history_params);

					                // 패키지 완료
					                $this->db->update('tb2_lec_package',
					                    array('tlp_status' => 'F'),
					                    array('tlp_id' => $lecture_detail->lr_tlp_id, 'tlp_status' => 'S')
					                );
					            }

						      }

						return true;
				} else {
					// error_log('already insert commment');
					return false;
				}

	}

    // 강의 환불 요청
    public function refundLectureAndComment($mb_no, $ta_lec_mb_no, $ta_lr_seq, $memo_content, $ta_r_code) {

        date_default_timezone_set('UTC');

        // 1. 코멘트 추가
        $commment_params = array(
            'ta_mb_no' => $mb_no,
            'ta_lec_mb_no' => $ta_lec_mb_no,
            'ta_lr_seq' => $ta_lr_seq,
            'ta_commnet' => $memo_content,
            'ta_r_code' => $ta_r_code,
            'ta_score' => 0,
            'ta_status' => 'R',
            'ta_reg_dt' => date("Y-m-d H:i:s"),
        );
        $this->db->insert('TB_after_comment', $commment_params);

        // 2. 예약테이블 상태 업데이트
        $reserv_params = array(
            'lr_status' => 'R'
        );
        $reserv_condition = array(
            'lr_seq' => $ta_lr_seq,
            'lr_mb_no' => $mb_no,
            'lr_lec_mb_no' => $ta_lec_mb_no
        );

        $this->db->update('TB_lec_reservation', $reserv_params, $reserv_condition);
        // $sql = $this->db->last_query();
        // error_log('refundLectureAndComment === '.$sql);
    }

    // 강의 환불 수락
    public function refundAccept($ta_mb_no, $ta_lec_mb_no, $ta_lr_seq) {

        $member = $this->db->get_where('g4_member', array('mb_no' => $ta_lec_mb_no))->row();
        // $reservation = $this->db->get_where('g4_member', array('mb_no' => $ta_lec_mb_no))->row();
        // $query = "select * from TB_after_comment join TB_lec_reservation on lr_seq=ta_lr_seq where ta_lr_seq = '{$ta_lr_seq}' and ta_r_code is not null and lr_status='R'";

        $this->db->join('TB_lec_reservation', 'lr_seq = ta_lr_seq', 'LEFT');
        $this->db->where('ta_lr_seq', $ta_lr_seq);
        $this->db->where('ta_r_code is not null', null, false);
        $this->db->where('lr_status', 'R');
        $reservation = $this->db->get('TB_after_comment')->row();

        date_default_timezone_set('UTC');

        // 1. 코멘트 찾아서 업데이트
        $ta_params = array(
            'ta_proc_dt' => date("Y-m-d H:i:s"),
            'ta_proc_name' => $member->mb_name,
            'ta_status' => 'F'
        );

        $ta_where = array(
            'ta_lr_seq' => $ta_lr_seq,
            'ta_status' => 'R'
        );

        $this->db->update('TB_after_comment', $ta_params, $ta_where);


        // 2. 예약테이블 상태 업데이트
        $reserv_params = array(
            'lr_status' => 'F'
        );
        $reserv_condition = array(
            'lr_seq' => $ta_lr_seq,
            'lr_mb_no' => $ta_mb_no,
            'lr_lec_mb_no' => $ta_lec_mb_no
        );

        $this->db->update('TB_lec_reservation', $reserv_params, $reserv_condition);


        // 3. 수강료 환불
        $payment_params = array(
            'tp_mb_no' => $ta_mb_no,
            'tp_lr_seq' => $ta_lr_seq,
            'tp_mode' => 'I',
            'tp_in_koin' => $reservation->lr_real_koin,
            'tp_reg_dt' => date("Y-m-d H:i:s"),
            'tp_memo' => 'Instructor Refund agree',
        );
        $this->db->insert('Tb_payment', $payment_params);

        $this->db->insert('Tb_payment_history', array('ph_lr_seq' => $ta_lr_seq, 'ph_memo' => '강사 환불요청', 'ph_reg_dt' => date("Y-m-d H:i:s")));
    }

    // 강의 환불 거부
    public function refundResponse($ta_mb_no, $ta_lec_mb_no, $ta_lr_seq, $file_path) {

        $member = $this->db->get_where('g4_member', array('mb_no' => $ta_lec_mb_no))->row();

        date_default_timezone_set('UTC');

        // 1. 코멘트 찾아서 업데이트
        $ta_params = array(
            'ta_evid_dt' => date("Y-m-d H:i:s"),
            'ta_evid_file' => $file_path,
            'ta_status' => 'S'
        );

        $ta_where = array(
            'ta_lr_seq' => $ta_lr_seq,
            'ta_status' => 'R'
        );

        $this->db->update('TB_after_comment', $ta_params, $ta_where);
    }
    // 강사 강의 코멘트
    public function updateTeacherComment($ta_lr_seq, $ta_teacher_comment) {
        date_default_timezone_set('UTC');

        $reserv_params = array(
            'ta_teacher_comment' => $ta_teacher_comment,
            'ta_teacher_dt' => date("Y-m-d H:i:s"),
        );

        $reserv_condition = array(
            'ta_lr_seq' => $ta_lr_seq,
        );

        $this->db->update('TB_after_comment', $reserv_params, $reserv_condition);
    }
}
