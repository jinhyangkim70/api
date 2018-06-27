<?php
class auth extends CI_Model {
	function __construct() {
		// Call the Model constructor
		parent::__construct();
	}

	public function getUser($mb_id) {
		$query = "select * from g4_member where mb_id = TRIM('$mb_id') or mb_email = TRIM('$mb_id')";

		$stmt = $this->db->query($query);

		if ($stmt->num_rows() == 1) {
			return $stmt->row();
		}
		else {
			return false;
		}
	}

	// 필요 정보
	public function getUserByNo($mb_no){

		$select = " *, (select co_name_eng from TB_country where CO_CODE=mb_nation) as str_mb_nation ";
		$select = $select."    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang) as str_mb_lang ";
		$select = $select."    ,(select Tl_eng from TB_language_zone where Tl_no=mb_lang2) as str_mb_lang2 ";
        $select =$select."    ,(select avg(ta_score) from TB_after_comment where ta_lec_mb_no=mb_no  and ta_score > 0 and exists (select 1 from TB_lec_reservation where lr_status='F' and lr_seq=TB_after_comment.ta_lr_seq) group by ta_lec_mb_no ) as ta_score ";

		$this->db->select($select);
		$row = $this->db->get_where('g4_member', array('mb_no' => $mb_no))->row();

        // 선생님의 경우 수강했던 학생수
        $query = "select DISTINCT lr_mb_no from TB_lec_reservation where lr_lec_mb_no =('$mb_no')";
        $student_count = $this->db->query($query)->num_rows();

        // 선생님의 경우 전체 강의수
        // $query = "select * from TB_lec_reservation where lr_lec_mb_no =('$mb_no') and lr_status ='F'";
        // $lecture_count = $this->db->query($query)->num_rows();
        $this->db->select('tp_mb_no, tp_mode, tp_reg_dt, tp_lr_seq');
        $this->db->where('tp_mb_no', $mb_no);
        $this->db->where('tp_mode', 'I');
        $this->db->where('tp_lr_seq IS NOT NULL', null, false);
        $this->db->from('Tb_payment');
        $lecture_count = $this->db->count_all_results();

		if($row) {

            //아마 수강시 최대 koin값
			switch ($row->mb_level) {
				case 5:
					$row->max_koin = 150;
					$row->koin_com = 20;
					break;
				case 6:
					$row->max_koin = 250;
					$row->koin_com = 15;
					break;
				case 7:
					$row->max_koin = 1000;
					$row->koin_com = 10;
					break;
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
            $row->str_star = $str_star;
            $row->student_count = $student_count;
            $row->lecture_count = $lecture_count;

			return $row;
		} else {
			return $row;
		}
	}

	public function password($value) {
		$query = " select password('$value') as pass ";

		$stmt = $this->db->query($query);

		return $stmt->row()->pass;
	}

    public function getUserBykey($mb_9) {

			date_default_timezone_set('UTC');

        // find user
        $this->db->select('mb_no, mb_id, mb_datetime, mb_level, mb_lang');
        $this->db->where(array('mb_9' => $mb_9, 'mb_10' =>'login_email'));
        $user = $this->db->get('g4_member')->row();

        if (isset($user) && !empty($user)) {
            //  error_log(' user ' . json_encode($user));
            // error_log(' user ' . $user->mb_no);
            // error_log(' usermb_level ' . $user->mb_level);
            // update status

            // 로그인시 작성해야할 프로필 페이지로 이동시키려고 셋팅하는것 같음. not use
        	// if ($user->mb_level == 3) {
        	// 	$mb_10 = "lc_join_0";
        	// }else{
        	// 	$mb_10 = "login_1";
        	// }

            if (function_exists("date_default_timezone_set")) {
                // date_default_timezone_set("Asia/Seoul");
                date_default_timezone_set('UTC');
            }

            $temp_date = date("Y-m-d H:i:s");

            $params = array(
                'mb_10' => 'user_Email_Confirm',
                'mb_9' => '',
                'mb_email_certify' => $temp_date);
            $ids = array('mb_id' => $user->mb_id, 'mb_no' => $user->mb_no);

            $this->db->update('g4_member', $params, $ids);
            return $user;
        } else {
            return false;
        }

    }


}
