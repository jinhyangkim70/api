<?php
class payment extends CI_Model {
	function __construct() {
		// Call the Model constructor
		parent::__construct ();

        date_default_timezone_set('UTC');
	}
	public function getPaymentList($search_params, $page, $count) {
        $startIndex = $page * $count;

        $this->db->where($search_params);
        $totalCount = $this->db->get('Tb_payment')->num_rows();
        // error_log('totalCount count === '.$totalCount);

        $hasNext = true;
        if ($totalCount <= $startIndex + $count) {
            $hasNext = false;
        }

        $this->db->select('tp_lr_seq, tp_mode, tp_in_koin, tp_out_koin, tp_reg_dt, tp_memo, tp_po_status, tp_tlp_id');
        $this->db->where($search_params);
        $this->db->order_by("tp_reg_dt", "desc");
        $this->db->limit($count, $startIndex);
        $rows = $this->db->get('Tb_payment')->result();

        // $sql = $this->db->last_query();
        // error_log('getPaymentList query === '.$sql);

        return array('data' => $rows, 'hasNextValue' => $hasNext);
		// $query = "select * from Tb_payment where tp_mb_no='{$mb->mb_no}'";
		// if ($tp_md == "I" || $tp_md == "O") {
		// 	$query = $query . " and tp_mode ='{$tp_md}' ";
		// }
		// $query = $query . " order by tp_reg_dt desc limit {$start_pg},10;";
        //
		// $stmt = $this->db->query ( $query );
        //
		// $ret = array ();
        //
		// if ($stmt->num_rows () > 0) {
		// 	foreach ( $stmt->result () as $row ) {
		// 		$item = $this->_createPaymentItem ( $row, $mb );
		// 		array_push ( $ret, $item );
		// 	}
		// }
        //
		// return $ret;
	}

    public function insertTempPaypal($mb_no, $item_name, $item_number, $amount) {
        $params = array(
            'tp_mb_no' => $mb_no,
            'tp_item_name' => $item_name,
            'tp_item_number' => $item_number,
            'tp_amount' => $amount,
            'tp_reg_dt' => date("Y-m-d H:i:s"),
        );

        $this->db->insert('TB_temp_paypal', $params);

        $new_id = $this->db->insert_id();
        return $this->db->get_where('TB_temp_paypal', array('tp_seq' => $new_id))->row();
    }

    public function updateTempPaypal($params, $condition) {
        $this->db->update('TB_temp_paypal', $params, $condition);
    }

    public function insertPaypalInfo($params) {
        $this->db->insert('TB_paypal_info', $params);
    }

    public function insertEximbayInfo($params) {
        $this->db->insert('tb_eximbay_info', $params);

        $sql = $this->db->last_query();
        // error_log('insertEximbayInfo query === '.$sql);
    }

    public function insertPayment($params) {
        $this->db->insert('Tb_payment', $params);
    }

    public function getPaypalInfo($txn_id) {
        // $this->db->select('tp_lr_seq, tp_mode, tp_in_koin, tp_out_koin, tp_reg_dt, tp_memo, tp_po_status');
        $this->db->where(array('txn_id' => $txn_id));
        $this->db->from('TB_paypal_info');
        return $this->db->count_all_results();
    }


    public function findEximbayInfo($params) {
        $this->db->where($params);
        $this->db->from('tb_eximbay_info');

        $ret = $this->db->count_all_results();
        // $sql = $this->db->last_query();
        // error_log('findEximbayInfo query === '.$sql);
        return $ret;
    }


    function insertDrawPayment($mb_no, $password, $account, $drawout_payment){


        // paypal 계좌 정보가 있는경우 제외시킴
        // $query="select COALESCE(SUM(tp_in_koin),0) AS koin_in,"
        //     ." COALESCE(SUM(tp_out_koin),0) AS koin_out,"
        //     ." (COALESCE(SUM(tp_in_koin),0)-COALESCE(SUM(tp_out_koin),0)) AS koin_bal"
        //     ." from Tb_payment where (tp_payment_code is null and tp_mode='I' or tp_mode='O') and tp_mb_no={$mb_no}";

        // 요청으로 모두 합산하여 계산함
        $query="select COALESCE(SUM(tp_in_koin),0) AS koin_in,"
            ." COALESCE(SUM(tp_out_koin),0) AS koin_out,"
            ." (COALESCE(SUM(tp_in_koin),0)-COALESCE(SUM(tp_out_koin),0)) AS koin_bal"
            ." from Tb_payment where (tp_mode='I' or tp_mode='O') and tp_mb_no={$mb_no}";

        $result = $this->db->query($query)->row();
        // error_log('sql     '.$query);
        // error_log('find result '.json_encode($result));

        $member = $this->db->get_where('g4_member', array('mb_no' => $mb_no))->row();
        $ret = 'password not equal';
        if($member->mb_password == $this->get_password($password)) {
            if ($result->koin_bal > $drawout_payment && $drawout_payment >= 300){

                $params = array(
                    'tp_mb_no' => $mb_no,
                    'tp_mode' => 'O',
                    'tp_out_koin' => $drawout_payment,
                    'tp_reg_dt' => date("Y-m-d H:i:s"),
                    'tp_memo' => 'withdraw KOIN.',
                    'tp_po_status' => 'H',
                    'tp_po_reg_dt' => date("Y-m-d H:i:s"),
                    'tp_account' => $account,
                );

                $this->db->insert('Tb_payment', $params);
                $ret = 'success';
            } else {
                $ret = 'balance error';
            }
        } else {
            $ret = 'balance error';
    	}

        return $ret;
    }
    // not use
	private function _createPaymentItem($row, $mb) {
		$row->tp_reg_dt = changeDateByTimezone ( $row->tp_reg_dt, $mb->mb_time );
		$tmp_strtime = strtotime ( $row->tp_reg_dt );
		$row->tp_reg_dt_m_j = date ( 'M j', $tmp_strtime );
		$row->tp_reg_dt_h_i = date ( 'H:i', $tmp_strtime );
		$row->tp_memo_status = 0;
		if ($row->tp_mode == "O" && ($row->tp_po_status == "H" || $row->tp_po_status == "F")) {
			if ($row->tp_po_status == "H") {
				$row->tp_memo_status = 1;
			} else {
				$row->tp_memo_status = 2;
			}
		}

		return $row;
	}


    function get_password($value) {
        $ret = '';
        $query = "select password('".$value."') as pass";

        $stmt = $this->db->query($query);

        if ($stmt->num_rows() == 1) {
            $ret = $stmt->row()->pass;
        }

        return $ret;
    }

    // 프로모션, 상품권 입력
    function insertCodePayment($mb_no, $code) {
        date_default_timezone_set('UTC');

        // 같은 이름의 코드 찾는다
        $find_code = $this->db->get_where('tb2_coupon', array('tc_code' => $code))->row();

        // 사용 안된 코드
        if ($find_code && $find_code->tc_use == 0) {
            // 프로모션일때는 사용자가 일치해야함
            if ($find_code->tc_is_promotion == 0) {
                if ($mb_no == $find_code->tc_use_mb_no) {
                    // 사용자 payment 갱신
                    $payment_params = array(
                        'tp_mb_no' => $mb_no,
                        // 'tp_lr_seq' => $reservation_id,
                        'tp_mode' => 'I',
                        'tp_in_koin' => $find_code->tc_koin,
                        'tp_reg_dt' => date("Y-m-d H:i:s"),
                        'tp_memo' => 'Promotion',
                    );
                    $this->db->insert('Tb_payment', $payment_params);

                    // 사용 표시
                    $use_date = strtotime(date('Y-m-d H:i:s'));
                    $this->db->update('tb2_coupon', array('tc_use' => 1, 'tc_use_date' => $use_date), array('tc_code' => $code));
                    return 'success';
                } else {
                    return 'error';
                }
            } else {
                // 상품권
                // 사용자 payment 갱신
                $payment_params = array(
                    'tp_mb_no' => $mb_no,
                    // 'tp_lr_seq' => $reservation_id,
                    'tp_mode' => 'I',
                    'tp_in_koin' => $find_code->tc_koin,
                    'tp_reg_dt' => date("Y-m-d H:i:s"),
                    'tp_memo' => 'Gift Card',
                );
                $this->db->insert('Tb_payment', $payment_params);

                // 사용 표시
                $use_date = strtotime(date('Y-m-d H:i:s'));
                $this->db->update('tb2_coupon', array('tc_use' => 1, 'tc_use_mb_no' => $mb_no, 'tc_use_date' => $use_date), array('tc_code' => $code));
                return 'success';
            }

        } else {
            return 'error';
        }

    }

    function insertGiftcard($mb_no, $tc_mb_email, $tc_mb_name, $tc_user_email, $tc_user_name, $tc_email_content) {
        $create_date = strtotime(date('Y-m-d H:i:s'));

        $params = array(
            'tc_mb_no' => $mb_no,
            'tc_mb_email' => $tc_mb_email,
            'tc_mb_name' => $tc_mb_name,
            'tc_recv_email' => $tc_user_email,
            'tc_recv_name' => $tc_user_name,
            'tc_email_content' => $tc_email_content,
            'tc_create_date' => $create_date,
            'tc_is_promotion' => 1,
        );

        $this->db->insert('tb2_coupon', $params);
        return $this->db->insert_id();
    }

    function giftcardSetcode($tc_id, $tp_in_koin) {
        $code = md5(uniqid(rand(), true));
        $condition = array('tc_id' => $tc_id, 'tc_giftcard_status' => 0);
        $params = array('tc_code' => $code, 'tc_koin' => $tp_in_koin, 'tc_giftcard_status' => 1);

        // code, koin 업데이트
        $this->db->update('tb2_coupon', $params, $condition);

        // 메일 발송을 위해 정보가저와서 리턴
        $this->db->where(array('tc_id' => $tc_id));
        return $this->db->get('tb2_coupon')->row();
    }

    function insertBankTransfer($params) {

        $params['tbt_create_date'] = strtotime(date('Y-m-d H:i:s'));

        $this->db->insert('tb2_bank_transfer', $params);
        return 'success';
    }
}
