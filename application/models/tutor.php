<?php
class tutor extends CI_Model {
	function __construct() {
		// Call the Model constructor
		parent::__construct ();
	}

	public function landing() {
		$sql = "  select *, (select co_name_eng from TB_country where CO_CODE=mb_nation) as str_mb_nation ";
		$sql =$sql."   ,(select co_name_eng from TB_country where CO_CODE=mb_nation) as str_mb_nation ";
		$sql =$sql."    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang) as str_mb_lang ";
		$sql =$sql."    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang2) as str_mb_lang2 ";
		$sql =$sql."    ,(select avg(ta_score) from TB_after_comment where ta_lec_mb_no=mb_no  and ta_score > 0 and exists (select 1 from TB_lec_reservation where lr_status='F' and lr_seq=TB_after_comment.ta_lr_seq) group by ta_lec_mb_no ) as ta_score ";
		$sql =$sql."   from g4_member where mb_level in (5,6,7) ";
        // $sql =$sql."   order by mb_recom_ord desc, ta_score desc limit 0, 10";
		$sql =$sql."   order by mb_recom_ord desc, ta_score desc";

		$stmt = $this->db->query($sql);

		$ret = array();

		foreach($stmt->result() as $row) {
                    if($row->mb_profile != " "){
                            array_push($ret, array(
                                'mb_no' => $row->mb_no,
                                'name' => $row->mb_nick,
                                'profile' => $row->mb_profile,
                                'str_mb_lang' => $row->str_mb_lang,
                                'str_mb_lang2' => $row->str_mb_lang2
                            ));
                    }
		}

		return $ret;
	}

	public function find($mb, $search, $thema, $slang, $priceLevel, $list_so, $trialPrice) {

		$sql = "  select *, (select co_name_eng from TB_country where CO_CODE=mb_nation) as str_mb_nation ";
		$sql = $sql . "   ,(select co_name_eng from TB_country where CO_CODE=mb_nation) as str_mb_nation ";
		$sql = $sql . "    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang) as str_mb_lang ";
		$sql = $sql . "    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang2) as str_mb_lang2 ";

        // 로그인 한 경우
        if (!empty($mb)) {
            $sql = $sql . "    ,(select tf_mb_no from TB_fav_member where tf_mb_no='" . $mb->mb_no . "' and tf_mb_lec_no=mb_no) as fv_yn";
        }

		$sql = $sql . "    ,(select avg(ta_score) from TB_after_comment where ta_lec_mb_no=mb_no and ta_score > 0  and exists (select 1 from TB_lec_reservation where lr_status='F' and lr_seq=TB_after_comment.ta_lr_seq) group by ta_lec_mb_no ) as ta_score ";
		$sql = $sql . "   from g4_member where mb_level in (5,6,7) ";

		if ($search != "") {
			$sql = $sql . "  and mb_nick like '%{$search}%' ";
		}

		if ($thema != "") {
			$thema = trim ( $thema, "," );
			$thema = str_replace ( ",", "','", $thema );
			$sql = $sql . " and mb_no in (select lm_mb_no from TB_lec_main where lm_lec_Cd in ('{$thema}'))";
			// $detail_flag = true;
		}

		if ($slang != "") {
			$sql = $sql . " and (mb_lang={$slang} or mb_lang2={$slang})";
			// $detail_flag = true;
		}

		if ($priceLevel !== "") {
			$sql = $sql . " and mb_no in (select lm_mb_no from TB_lec_main where 1=2 ";
			if (strpos($priceLevel, '1') !== false) {
				$sql = $sql . " or lm_payment between 0 and 50 ";
			}
			if (strpos($priceLevel, '2') !== false) {
				$sql = $sql . " or lm_payment between 51 and 100";
			}
			if (strpos($priceLevel, '3') !== false) {
				$sql = $sql . " or lm_payment>=101";
			}
			$sql = $sql . " )";
			// $detail_flag = true;
		}


        // 시범강의 유료 또는 무료
		if ($trialPrice !== "") {
			$sql = $sql . " and mb_no in (select lm_mb_no from TB_lec_main where lm_lec_Cd='T00' ";
            // 유료
			if ($trialPrice == 'charge') {
				$sql = $sql . "and lm_payment > 0";
			}
            // 무료
			if ($trialPrice == 'free') {
				$sql = $sql . "and lm_payment = 0";
			}
			$sql = $sql . " )";
			//$detail_flag = true;
		}


		if ($list_so == "2") {
			$sql = $sql . "   order by ta_score desc";
		} elseif ($list_so == "1") {
			$sql = $sql . "   order by mb_datetime desc";
		} else {
			$sql = $sql . "   order by mb_recom_ord desc, mb_datetime desc";
		}
        // error_log(' sql ~~~'.$sql);

		$stmt = $this->db->query ( $sql );

		$ret = array ();

		if ($stmt->num_rows () > 0) {
			foreach ( $stmt->result () as $row ) {
				$item = $this->_createTutorItem ( $row, $mb );
				array_push ( $ret, $item );
			}
		}

		return $ret;
	}


	// user(mb)가 등록한 즐겨착기 목록 리스트
	public function favoriteList($mb) {
		$sql = "  select *, (select co_name_eng from TB_country where CO_CODE=mb_nation) as str_mb_nation ";
		$sql =$sql."   ,(select co_name_eng from TB_country where CO_CODE=mb_nation) as str_mb_nation ";
		$sql =$sql."    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang) as str_mb_lang ";
		$sql =$sql."    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang2) as str_mb_lang2 ";
		$sql =$sql."    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang2) as str_mb_lang2 ";
		$sql =$sql."     ,(select tf_mb_no from TB_fav_member where tf_mb_no='".$mb->mb_no."' and tf_mb_lec_no=mb_no) as fv_yn";
		$sql =$sql."    ,(select avg(ta_score) from TB_after_comment where ta_lec_mb_no=mb_no group by ta_lec_mb_no ) as ta_score ";
		$sql =$sql."   from g4_member";
		$sql =$sql."   where mb_no in (select tf_mb_lec_no from TB_fav_member where tf_mb_no='".$mb->mb_no."') ";

// 		if ($list_so == "2"){
// 			$sql =$sql."   order by ta_score desc";
// 		}else{
			$sql =$sql."   order by mb_datetime desc";
// 		}
		$stmt = $this->db->query($sql);

		$ret = array();
		foreach ( $stmt->result () as $row ) {
			$item = $this->_createTutorItem ( $row, $mb );
			array_push ( $ret, $item );
		}

		return $ret;
	}

	public function toggleFavorite($isFav, $src_mb_no, $dest_mb_no) {
// 		$sql = " delete from TB_fav_member where tf_mb_no = '{$src_mb_no}' and tf_mb_lec_no = '{$dest_mb_no}' ";

		$this->db->delete('TB_fav_member', array('tf_mb_no' => $src_mb_no, 'tf_mb_lec_no' => $dest_mb_no));

		if(!$isFav) {
			$sql = " insert into TB_fav_member (tf_mb_no,tf_mb_lec_no) values('{$src_mb_no}','{$dest_mb_no}')";
			$this->db->query($sql);

			return TRUE;
		}

		return FALSE;
	}

	private function _createTutorItem($row, $mb = NULL) {
        $fav = true;
        if (empty($mb)) {
            $fav = false;
        } else {
            // 로그인 한 상태일때
            if ($row->fv_yn == null) {
    			$fav = false;
    		}
        }

		$query = "select * from TB_lec_main where lm_mb_no='{$row->mb_no}' order by 1";
		$stmt = $this->db->query ( $query );

		$themeList = array ();

		if ($stmt->num_rows () > 0) {
			foreach ( $stmt->result () as $item ) {
				$str_group = false;
				if ($item->lm_group_yn == "Y") {
					$str_group = true;
				}

				array_push ( $themeList, array (
						'lm_lec_cd' => $item->lm_lec_cd,
						'lm_lec_cd_etc' => $item->lm_lec_cd_etc,
						'isGroup' => $str_group,
                        'lm_payment' => $item->lm_payment,
                        'lm_group_payment' => $item->lm_group_payment
				) );
			}
		}

		$str_star = "";
		if ($row->ta_score == "")
			$row->ta_score = 0;
		for($c = 1; $c <= 5; $c ++) {
			if ($row->ta_score >= $c) {
				$str_star = $str_star . "★";
			} else {
				$str_star = $str_star . "☆";
			}
		}


        $material_query = "select count(*) as count from tb2_material where tm_mb_no='{$row->mb_no}'";
        $material_row = $this->db->query ( $material_query )->row();

		return array (
				'mb_no' => $row->mb_no,
				'mb_profile' => $row->mb_profile,
                'mb_signature' => $row->mb_signature,
				'mb_nick' => $row->mb_nick,
				'mb_level' => $row->mb_level,
				'ta_score' => $row->ta_score,
				'str_star' => $str_star,
				'str_mb_nation' => $row->str_mb_nation,
				'str_mb_lang' => $row->str_mb_lang,
				'str_mb_lang2' => $row->str_mb_lang2,
				'fav' => $fav,
				'themeList' => $themeList,
				'mb_datetime' => $row->mb_datetime,
				'mb_recom_ord' => $row->mb_recom_ord,
                'material_count' => $material_row->count,
		);
	}

    function recentLectureReport($mb_no) {
        $scoreReport = array(
            'lastMonthAvg' => 0,
            'lastMonthTrialAvg' => 0,
            'threeMonthAvg' => 0,
            'threeMonthTrialAvg' => 0,
            'totalAvg' => 0,
            'totalTrialAvg' => 0,
        );

        date_default_timezone_set('UTC');

        // 지난달 완료된 lecture_count
        $this->db->join('TB_after_comment comment', 'comment.ta_lr_seq = lr_seq', 'LEFT');
        $this->db->select('ta_score, lr_lec_cd, lr_tsd_strtime');
        $result = $this->db->get_where(
            'TB_lec_reservation',
            array('lr_lec_mb_no' => $mb_no, 'lr_status' => 'F')
        )->result();

        // $sql = $this->db->last_query();
        // error_log('my pacakge query === '.$sql);
        // error_log('lastMonthAvg '.$scoreReport['lastMonthAvg']);
        // error_log('result count :  '.count($result));

        $firstDayLastMonth = strtotime('first day of last month');
        $lastDayLastMonth = strtotime('last day of last month');
        $threeMonthAgo = strtotime('3 months ago');

        $lectureSum = 0;
        $lectureTrialSum = 0;
        $lectureCount = 0;
        $trialCount = 0;

        $threeLectureSum = 0;
        $threeLectureTrialSum = 0;
        $threeLectureCount = 0;
        $threeTrialCount = 0;

        $totalLectureSum = 0;
        $totalLectureTrialSum = 0;
        $totalLectureCount = 0;
        $totalTrialCount = 0;

        foreach($result as $row) {
            if ($row->lr_lec_cd == 'T00') {
                $totalLectureTrialSum += $row->ta_score;
                $totalTrialCount++;
            } else {
                $totalLectureSum += $row->ta_score;
                $totalLectureCount++;
            }

            // 3개월 이내
            if ($row->lr_tsd_strtime > $threeMonthAgo) {
                if ($row->lr_lec_cd == 'T00') {
                    $totalLectureTrialSum += $row->ta_score;
                    $threeTrialCount++;
                } else {
                    $threeLectureSum += $row->ta_score;
                    $threeLectureCount++;
                }
            }

            // 지난달
            if ($row->lr_tsd_strtime >= $firstDayLastMonth && $row->lr_tsd_strtime <= $lastDayLastMonth) {
                if ($row->lr_lec_cd == 'T00') {
                    $lectureTrialSum += $row->ta_score;
                    $trialCount++;
                } else {
                    $lectureSum += $row->ta_score;
                    $lectureCount++;
                }
            }
        }

        // 종합
        if ($totalLectureCount) {
            $scoreReport['totalAvg'] = $totalLectureSum / $totalLectureCount;
        }
        if ($totalTrialCount) {
            $scoreReport['totalTrialAvg'] = $totalTrialCount / $totalTrialCount;
        }

        // 3개월 이내
        if ($threeLectureCount) {
            $scoreReport['threeMonthAvg'] = $threeLectureSum / $threeLectureCount;
        }
        if ($threeTrialCount) {
            $scoreReport['threeMonthTrialAvg'] = $threeLectureTrialSum / $threeTrialCount;
        }

        // 지난달
        if ($lectureCount) {
            $scoreReport['lastMonthAvg'] = $lectureSum / $lectureCount;
        }
        if ($trialCount) {
            $scoreReport['lastMonthTrialAvg'] = $lectureTrialSum / $trialCount;
        }

        $finishLectureReport = array(
            'lastMonth' => $lectureCount,
            'lastMonthTrial' => $trialCount,
            'threeMonth' => $threeLectureCount,
            'threeMonthTrial' => $threeTrialCount,
            'total' => $totalLectureCount,
            'totalTrial' => $totalTrialCount,
        );

        //return array('scoreReport' => $scoreReport, 'finishLectureReport' => $finishLectureReport);
		return $scoreReport;
    }


    function recentLectureCount($mb_no) {
        date_default_timezone_set('UTC');

        // 지난달 완료된 lecture_count
        $this->db->select('lr_lec_cd, lr_tsd_strtime');
        $this->db->where('lr_lec_mb_no', $mb_no);
        $this->db->where_in('lr_status', array('F', 'R'));

        // $this->db->or_where('lr_status', 'F');
        // $this->db->or_where('lr_status', 'R');
        $result = $this->db->get('TB_lec_reservation')->result();

        // $sql = $this->db->last_query();
        // error_log('my pacakge query === '.$sql);
        // error_log('lastMonthAvg '.$scoreReport['lastMonthAvg']);
        // error_log('result count :  '.count($result));

        $this->db->select('tp_mb_no, tp_mode, tp_reg_dt, tp_lr_seq');
        $this->db->where('tp_mb_no', $mb_no);
        $this->db->where('tp_mode', 'I');
        $this->db->where('tp_lr_seq IS NOT NULL', null, false);
        $payment_result = $this->db->get('Tb_payment')->result();

        $firstDayLastMonth = strtotime('first day of last month');
        $lastDayLastMonth = strtotime('last day of last month');
        $threeMonthAgo = strtotime('3 months ago');

        $lectureCount = 0;
        $trialCount = 0;

        $threeLectureCount = 0;
        $threeTrialCount = 0;

        $totalLectureCount = 0;
        $totalTrialCount = 0;

        foreach($result as $row) {
            if ($row->lr_lec_cd == 'T00') {
                $totalTrialCount++;
            } else {
                // $totalLectureCount++;
            }

            // 3개월 이내
            if ($row->lr_tsd_strtime > $threeMonthAgo) {
                if ($row->lr_lec_cd == 'T00') {
                    $threeTrialCount++;
                } else {
                    // $threeLectureCount++;
                }
            }

            // 지난달
            if ($row->lr_tsd_strtime >= $firstDayLastMonth && $row->lr_tsd_strtime <= $lastDayLastMonth) {
                if ($row->lr_lec_cd == 'T00') {
                    $trialCount++;
                } else {
                    // $lectureCount++;
                }
            }
        }

        foreach($payment_result as $row) {
            $tempDate = strtotime($row->tp_reg_dt);

            $totalLectureCount++;

            // 3개월 이내
            if ($tempDate > $threeMonthAgo) {
                $threeLectureCount++;
            }

            // 지난달
            if ($tempDate >= $firstDayLastMonth && $tempDate <= $lastDayLastMonth) {
                $lectureCount++;
            }
        }

        $finishLectureReport = array(
            'lastMonth' => $lectureCount,
            'lastMonthTrial' => $trialCount,
            'threeMonth' => $threeLectureCount,
            'threeMonthTrial' => $threeTrialCount,
            'total' => $totalLectureCount,
            'totalTrial' => $totalTrialCount,
        );


        //return array('finishLectureReport' => $finishLectureReport);
		return $finishLectureReport;
    }
}
