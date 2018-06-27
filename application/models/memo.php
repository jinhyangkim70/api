<?php
class memo extends CI_Model {
    var $tableName = 'g4_memo';

	function __construct() {
		// Call the Model constructor
		parent::__construct ();
	}

    function getById($id) {
        $this->db->select(
            'me_id,
            me_recv_mb_no,
            me_send_mb_no,
            me_send_datetime,
            me_read_datetime,
            me_memo,
            send_user.mb_no,
            send_user.mb_id,
            send_user.mb_nick,
            send_user.mb_profile');

        $this->db->join('g4_member send_user', 'send_user.mb_no = g4_memo.me_send_mb_no', 'LEFT');

		return $this->db->get_where($this->tableName, array('me_id' => $id))->row();
	}

	public function getMemoList($mb) {
		// error_log('call getMemoList');

		$sql = "  select mb_nick, mb_profile, mb_level, c.* from g4_member as e join" . "\n";
		$sql = $sql . " (select b.me_mb_no, count(b.me_mb_no) as cnt, max(b.me_id) as max_me_id, (select me_memo from g4_memo as a where max(b.me_id)=a.me_id) as me_memo, (select me_send_datetime from g4_memo as a where max(b.me_id)=a.me_id) as me_send_datetime from " . "\n";
		$sql = $sql . " (" . "\n";
		$sql = $sql . "	select me_id, me_send_mb_no as me_mb_no from g4_memo where me_recv_mb_no='{$mb->mb_no}' " . "\n"; // 내가 받은 쪽지
		$sql = $sql . "	union all" . "\n";
		$sql = $sql . "	select me_id, me_recv_mb_no as me_mb_no from g4_memo where me_send_mb_no='{$mb->mb_no}'" . "\n"; // 내가 보낸 쪽지
		$sql = $sql . " ) as b" . "\n";
		$sql = $sql . "  where me_mb_no not in (select tr_rm_mb_no from TB_rm_memo_member where tr_mb_no='{$mb->mb_no}')" . "\n"; // 지운 메시지를 빼고 보여준다
		$sql = $sql . " group by me_mb_no) as c" . "\n";
		$sql = $sql . " on c.me_mb_no=e.mb_no" . "\n";
		$sql = $sql . " order by max_me_id desc" . "\n";

		$stmt = $this->db->query ( $sql );

		$ret = array ();
		if ($stmt->num_rows () > 0) {
			foreach ( $stmt->result () as $row ) {

// 				switch ($row->mb_level) {
// 					case 5 :
// 						$str_l_icon_img = "";
// 						break;
// 					case 6 :
// 						$str_l_icon_img = "<img src=\"../images/common/icon/ic_s.gif\" alt=\"star\" />Star Lecturer";
// 						break;
// 					case 7 :
// 						$str_l_icon_img = "<img src=\"../images/common/icon/ic_p.gif\" alt=\"pro\" />Professional Teacher";
// 						break;
// 				}
				$str_msg = $row->me_memo;
				$str_msg = nl2br ( $str_msg );
				$row->str_msg = str_replace ( "\\", "", $str_msg );
// 				$row->str_date_time = changeDateByTimezone ( $row->me_send_datetime, $mb->mb_time );

				array_push ( $ret, $row );
			}
		}


		return $ret;
	}

	public function getMemoUnreadCount($mb) {
        $this->db->where('me_recv_mb_no', $mb->mb_no);
        $this->db->where('me_read_datetime', '0000-00-00');
        $this->db->from('g4_memo');
        $ret = $this->db->count_all_results();

        // $sql = $this->db->last_query();
        // error_log("unreadmemo count == {$sql}");
        return $ret;
	}

    public function getNotiUnreadCount($mb) {
        $this->db->where('me_recv_mb_no', $mb->mb_no);
        $this->db->where('me_read_datetime', '0000-00-00');
        $this->db->from('tb_memo_system');
        $ret = $this->db->count_all_results();

        // $sql = $this->db->last_query();
        // error_log("unreadmemo count == {$sql}");
        return $ret;
    }

    // not use
    public function getMemoPage($me, $nick_name, $index, $count) {
        $this->db->join('g4_member send_user', 'send_user.mb_no = g4_memo.me_send_mb_no', 'LEFT');
        if (!empty($nick_name)) {
            $this->db->like('send_user.mb_nick', $nick_name);
        }

		$this->db->where('me_recv_mb_no', $me->mb_no);
		$memoCount = $this->db->get('g4_memo')->num_rows();


        $this->db->select(
            'me_id,
            me_recv_mb_no,
            me_send_mb_no,
            me_send_datetime,
            me_read_datetime,
            me_memo,
            send_user.mb_no,
            send_user.mb_id,
            send_user.mb_nick,
            send_user.mb_profile');

        $this->db->join('g4_member send_user', 'send_user.mb_no = g4_memo.me_send_mb_no', 'LEFT');
        if (!empty($nick_name)) {
            $this->db->like('send_user.mb_nick', $nick_name);
        }

        $this->db->order_by('me_id', 'DESC');
        $this->db->limit ( $count, $index * $count);
        $this->db->where(array('me_recv_mb_no' => $me->mb_no));
        $memoList = $this->db->get('g4_memo')->result();

        return array('memoList' => $memoList, 'memoCount' => $memoCount);
    }

    public function getNotificationPage($mb_no, $index, $count) {
		$this->db->where('me_recv_mb_no', $mb_no);
		$totalCount = $this->db->get('tb_memo_system')->num_rows();

        $this->db->select(
            'me_id,
            me_recv_mb_no,
            me_send_datetime,
            me_read_datetime,
            me_memo,
            ');

        $this->db->order_by('me_id', 'DESC');
        $this->db->limit ( $count, $index * $count);
        $this->db->where(array('me_recv_mb_no' => $mb_no));
        $memoList = $this->db->get('tb_memo_system')->result();

        $hasNext = true;
        if ($totalCount <= $index + $count) {
            $hasNext = false;
        }
        return array (
                'hasNext' => $hasNext,
                'memoList' => $memoList
        );
    }

    public function readNotfication($me_id) {
        date_default_timezone_set('UTC');
        $data = array( 'me_read_datetime' => date("Y-m-d H:i:s") );
        $update_condition = array('me_id' => $me_id);
        $this->db->where($update_condition);
        $this->db->update('tb_memo_system', $data);
    }

    public function getMemoListSortUser($me, $nick_name) {

        $this->db->select(
            '
            me_id,
            me_recv_mb_no,
            me_send_mb_no,
            me_send_datetime,
            me_read_datetime,
            me_memo,
            send_user.mb_nick as sender_nick,
            send_user.mb_profile as sender_profile,
            recv_user.mb_nick as recver_nick,
            recv_user.mb_profile as recver_profile,
            '
        );

        $this->db->join('g4_member send_user', 'send_user.mb_no = g4_memo.me_send_mb_no', 'LEFT');
        $this->db->join('g4_member recv_user', 'recv_user.mb_no = g4_memo.me_recv_mb_no', 'LEFT');

        $where_condition = "(me_recv_mb_no='{$me->mb_no}' OR me_send_mb_no='{$me->mb_no}')";
        $this->db->where($where_condition, NULL, FALSE);

        if (!empty($nick_name)) {
            $like_condition = "(send_user.mb_nick like '%$nick_name%' OR recv_user.mb_nick like '%$nick_name%')";
            $this->db->where($like_condition, NULL, FALSE);
            // $this->db->like('send_user.mb_nick', $nick_name);
            // $this->db->or_like('recv_user.mb_nick', $nick_name);
        }



        $this->db->order_by('me_id', 'DESC');
        $memoList = $this->db->get('g4_memo')->result();

        // $sql = $this->db->last_query();
        // error_log("getMemoPage == {$sql}");
        // 회원 id array에 담자
        $mb_no_total_list = array();
		foreach( $memoList as $memo) {
            if ( $memo->me_recv_mb_no != $me->mb_no) {
                array_push($mb_no_total_list, $memo->me_recv_mb_no);
            }

            if ( $memo->me_send_mb_no != $me->mb_no) {
                array_push($mb_no_total_list, $memo->me_send_mb_no);
            }
		}
        $mb_no_list = array_unique($mb_no_total_list);


        // 회원 별로 마지막 memo를 담음
        $result = array();
        foreach( $mb_no_list as $temp_mb_no) {

            for ($i = 0; $i < count($memoList); $i++) {
                $temp_memo = $memoList[$i];

                // if ($temp_memo->me_recv_mb_no == 100188 || $temp_memo->me_send_mb_no == 100188) {
                //     error_log('memo - '.$temp_memo->me_memo);
                // }
                // 일치하는 경우 하나만 담음.
                if ($temp_memo->me_recv_mb_no == $temp_mb_no || $temp_memo->me_send_mb_no == $temp_mb_no) {

                    $memo_temp = array(
                        'me_send_datetime' => $temp_memo->me_send_datetime,
                        'me_read_datetime' => $temp_memo->me_read_datetime,
                        'me_memo' => $temp_memo->me_memo,
                        'me_send_mb_no' => $temp_mb_no,
                    );

                    if ($temp_memo->me_recv_mb_no == $temp_mb_no) {
                        $memo_temp['isMe'] = true;
                        $memo_temp['mb_nick'] = $temp_memo->recver_nick;
                        $memo_temp['mb_profile'] = $temp_memo->recver_profile;
                    } else {
                        $memo_temp['isMe'] = false;
                        $memo_temp['mb_nick'] = $temp_memo->sender_nick;
                        $memo_temp['mb_profile'] = $temp_memo->sender_profile;
                    }

                    array_push($result, $memo_temp);
                    break;
                }
            }

        }



        // $sql = $this->db->last_query();
        // error_log("getMemoPage == {$sql}");

        return array('memoList' => $result);
    }

	public function getMemoChat($me, $chat_user_no) {
        date_default_timezone_set('UTC');

        $this->db->select(
            'me_id,
            me_recv_mb_no,
            me_send_mb_no,
            me_send_datetime,
            me_read_datetime,
            me_memo,
            send_user.mb_no,
            send_user.mb_id,
            send_user.mb_nick,
            send_user.mb_profile');

        $this->db->join('g4_member send_user', 'send_user.mb_no = g4_memo.me_send_mb_no', 'LEFT');

        $where_condition = "(me_recv_mb_no='{$me->mb_no}' AND me_send_mb_no='{$chat_user_no}') OR (me_send_mb_no='{$me->mb_no}' AND me_recv_mb_no='{$chat_user_no}')";
        $this->db->where($where_condition);

        // $this->db->where(array('me_recv_mb_no' => $me->mb_no, 'me_send_mb_no', $chat_user_no));
        // $this->db->or_where(array('me_send_mb_no' => $me->mb_no, 'me_recv_mb_no', $chat_user_no));

        $this->db->order_by('me_send_datetime', 'ASC');
        $memoList = $this->db->get('g4_memo')->result();



        // update me_read_datetime
        date_default_timezone_set('UTC');
        $data = array( 'me_read_datetime' => date("Y-m-d H:i:s") );
        $update_condition = array('me_recv_mb_no' => $me->mb_no, 'me_send_mb_no' => $chat_user_no);
        $this->db->where($update_condition);
        $this->db->update('g4_memo', $data);
        // $sql = $this->db->last_query();
        // error_log("getMemoChat == {$sql}");

		return $memoList;
	}

	public function getMemoTotalCount($mb) {
		$sql = "SELECT count(*) as cnt FROM g4_memo a JOIN g4_member b ON a.me_send_mb_no = b.mb_no WHERE me_recv_mb_no = '{$mb->mb_no}'";
		$stmt = $this->db->query ( $sql );

        // $sql = $this->db->last_query();
        // error_log("getMemoTotalCount == {$sql}");

		return $stmt->num_rows();
	}

    public function insertNewMemo($memo) {
        // 차단 중인지 확인
        $this->db->where(array('tbu_mb_no' => $memo['me_recv_mb_no'], 'tbu_block_mb_no' => $memo['me_send_mb_no']));
        $this->db->from('tb2_block_user');
        $count = $this->db->count_all_results();

        if ($count > 0) {
            // error_log("oh it's here!");
            return array();
        } else {
            date_default_timezone_set('UTC');
            $memo['me_send_datetime'] = date ( 'Y-m-j H:i' );
            // error_log("memo == ".json_encode($memo));

            $this->db->insert('g4_memo', $memo);
            // error_log('id == '.$this->db->insert_id());
            return $this->getById($this->db->insert_id());
        }
    }

    public function deleteMemo($me_id) {
        $this->db->where('me_id', $me_id);
        $this->db->delete($this->tableName);
    }

	public function getDetail($from, $seq) {
		if($seq == "system"){
			$sql = "  select * from tb_memo_system where me_recv_mb_no='{$from->mb_no}' order by me_id ";
// 			$timp_time = sql_fetch("select mb_lang from g4_member where mb_no=".$from->mb_no." limit 1 ");
		}else{
// 			$sql = "  select * from g4_member where mb_no='{$seq}'";
// 			$to_info = sql_fetch($sql, false);

// 			switch ($to_info[mb_level]) {
// 				case 5:
// 					$str_l_icon_img_to="";
// 					break;
// 				case 6:
// 					$str_l_icon_img_to="<img src=\"../images/common/icon/ic_s.gif\" alt=\"Star\" />";
// 					break;
// 				case 7:
// 					$str_l_icon_img_to="<img src=\"../images/common/icon/ic_p.gif\" alt=\"Pro\" />";
// 					break;
// 			}

// 			switch ($member[mb_level]) {
// 				case 5:
// 					$str_l_icon_img_me="";
// 					break;
// 				case 6:
// 					$str_l_icon_img_me="<img src=\"../images/common/icon/ic_s.gif\" alt=\"Star\" />";
// 					break;
// 				case 7:
// 					$str_l_icon_img_me="<img src=\"../images/common/icon/ic_p.gif\" alt=\"pro\" />";
// 					break;
// 			}
			$sql = "  select * from g4_memo where (me_recv_mb_no='{$seq}' and me_send_mb_no='{$from->mb_no}') or (me_send_mb_no='{$seq}' and me_recv_mb_no='{$from->mb_no}')order by me_id ";
		}

		$stmt = $this->db->query($sql);

		$ret = array();
		foreach( $stmt->result() as $row) {
			$row->str_date_time = changeDateByTimezone($row->me_send_datetime, $from->mb_time);
			array_push($ret, $row);
		}

		return $ret;
	}
}
