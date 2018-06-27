<?php
class board extends CI_Model {
    function __construct() {
        // Call the Model constructor
        parent::__construct();

        date_default_timezone_set('UTC');
    }

    // file begin
    function addFile($params) {
        $date = new DateTime();
        $params['tma_create_date'] = $date->getTimestamp();
        $this->db->insert('tb2_material_attach', $params);

        $new_id = $this->db->insert_id();
        return $this->db->get_where('tb2_material_attach', array('tma_id' => $new_id))->row();
    }

    function updateFileForBoardId($tma_id, $params) {
		$this->db->update ( 'tb2_material_attach', $params, array (
				'tma_id' => $tma_id
		) );

        return $this->db->get_where('tb2_material_attach', array('tma_id' => $tma_id))->row();
    }

    function getFiles($board_id){
        return $this->db->get_where('tb2_material_attach', array('tma_board_id' => $board_id))->result();
    }


    function deleteAttachFile($tma_id) {
        $atthacFile = $this->db->get_where('tb2_material_attach', array('tma_id' => $tma_id))->row();

        // error_log('$atthacFile->tma_file_url = '.$atthacFile->tma_file_url);
        // error_log('$atthacFile->tma_rel_file_url = '.$atthacFile->tma_rel_file_url);

        // 맨 앞에 붙은 슬래시 제거
        $filePath = substr($atthacFile->tma_rel_file_url, 1);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->db->delete('tb2_material_attach', array('tma_id' => $tma_id));
    }


    // file end
    function deleteBoard($tm_id) {
        $this->db->delete('tb2_material', array('tm_id' => $tm_id));
        $atthacFileList = $this->db->get_where('tb2_material_attach', array('tma_board_id' => $tm_id))->result();

        foreach ($atthacFileList as $row) {
            // 맨 앞에 붙은 슬래시 제거
            $filePath = substr($row->tma_rel_file_url, 1);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $this->db->delete('tb2_material_attach', array('tma_id' => $row->tma_id));
        }
    }

    function getBoardDetail($tm_id) {
        $select_Board = 'tm_id, tm_mb_no, tm_create_date, tm_subject, tm_content, tm_view_count, tm_like_count, tm_hate_count, tm_sub_cd, tm_lang_cd';
        // $select_member = 'mb_name, mb_nick, mb_email, mb_level, mb_profile, mb_signature, mb_lang, mb_lang2';
        $select_code = 'cd_desc';
        $this->db->select($select_Board.' , '.$select_code);

        // $this->db->join('g4_member member', 'member.mb_no = tb2_material.tm_mb_no', 'LEFT');
        $this->db->join('tb_code code', 'code.cd_code = tb2_material.tm_sub_cd', 'LEFT');
        $ret = $this->db->get_where('tb2_material', array('tm_id' => $tm_id))->row();

        // view count
        $this->db->update ( 'tb2_material',
            array('tm_view_count' => ++$ret->tm_view_count),
            array('tm_id' => $tm_id)
        );

        return $ret;
    }

    function getPopulerBoard($tm_sub_cd) {
        $select_Board = 'tm_id, tm_mb_no, tm_create_date, tm_subject, tm_content, tm_view_count, tm_like_count, tm_hate_count, tm_sub_cd, tm_lang_cd';
        $select_member = 'mb_name, mb_nick, mb_email, mb_level, mb_profile';
        $select_code = 'cd_desc';
        $this->db->select($select_Board.' , '.$select_member.' , '.$select_code);

        $this->db->join('g4_member member', 'member.mb_no = tb2_material.tm_mb_no', 'LEFT');
        $this->db->join('tb_code code', 'code.cd_code = tb2_material.tm_sub_cd', 'LEFT');
        $this->db->order_by('tm_like_count', 'desc');
        $ret = $this->db->get_where('tb2_material', array('tm_sub_cd' => $tm_sub_cd))->row();

        return $ret;
    }

    // 해당 코드를 제외한 나머지 코드 랜덤 3개의 인기글 1개씩
    function getOthersPopuler($tm_sub_cd) {
        $this->db->select('cd_code, cd_desc, FLOOR(1 + RAND() * cd_seq) as rand_ind');
        $this->db->where('cd_type', 'MT');
        $this->db->where('cd_code !=', $tm_sub_cd);
        $this->db->order_by('rand_ind', 'ASC');
        $find_others = $this->db->get('tb_code', 3, 0)->result();

        // $sql = $this->db->last_query();
        // error_log('boardlist  ' .$sql);
        // error_log('find_others  ' .count($find_others));

        $return = array();
        foreach ($find_others as $key => $value) {
            $ret = $this->getPopulerBoard($value->cd_code);
            array_push($return,$ret);
        }

        return $return;
    }


    function getThumbsup($tm_id, $mb_no) {
        return $this->db->get_where('tb2_material_like', array('tml_tm_id' => $tm_id, 'tml_mb_no' => $mb_no))->row();
    }


    function getBoardList($page, $count, $tm_sub_cd, $tm_lang_cd, $teacher_name) {

        // 선생님 이름 검색 할떄
        $find_mb_no_list = array();
        if ($teacher_name != '') {

            $this->db->distinct();
            $this->db->select('mb_no');
            $this->db->like('mb_nick', $teacher_name);
            $temp = $this->db->get('g4_member')->result();

            // error_log(' TEST find name');
            // error_log(json_encode($temp));
            foreach ($temp as $member) {
                array_push ($find_mb_no_list, $member->mb_no);
            }
        }

        $select_Board = 'tm_id, tm_create_date, tm_subject, tm_content, tm_view_count, tm_sub_cd, tm_lang_cd, tm_like_count, tm_hate_count';
        $select_member = 'mb_name, mb_nick, mb_email, mb_level, mb_profile';
        $select_lang = 'Tl_eng';
        $this->db->select($select_Board.' , '.$select_member.' , '.$select_lang);
        $this->db->join('g4_member member', 'member.mb_no = tb2_material.tm_mb_no', 'LEFT');
        $this->db->join('TB_language_zone language', 'language.Tl_code = tb2_material.tm_lang_cd', 'LEFT');

        if ($tm_sub_cd != 'ALL') {
            $this->db->where_in('tm_sub_cd', $tm_sub_cd);
        }
        if ($tm_lang_cd != 'ALL') {
            $this->db->where(array('tm_lang_cd' => $tm_lang_cd));
        }
        if (count($find_mb_no_list)) {
            // error_log(json_encode($find_mb_no_list));
            $this->db->where_in('tm_mb_no', $find_mb_no_list);
        }


        $this->db->order_by('tm_create_date', 'desc');
        $totalCount = $this->db->get('tb2_material')->num_rows();
        $sql = $this->db->last_query();

        // error_log('boardlist  ' .$sql);


        $startIndex = $count * $page;
        $sql = $sql . " limit {$startIndex} , {$count} ";
        $ret = $this->db->query($sql)->result();

        $hasNext = true;
        if ($totalCount <= $startIndex + $count) {
            $hasNext = false;
        }
        return array (
                'meta' => array (
                        'hasNext' => $hasNext,
                        // 'query' => $query
                ),
                'data' => $ret
        );
    }

    function getTeacherBoardList($page, $count, $mb_no) {
        $select_Board = 'tm_id, tm_create_date, tm_subject, tm_content, tm_view_count, tm_sub_cd, tm_lang_cd, tm_like_count, tm_hate_count';
        $select_member = 'mb_name, mb_nick, mb_email, mb_level, mb_profile';
        $this->db->select($select_Board.' , '.$select_member);
        $this->db->join('g4_member member', 'member.mb_no = tb2_material.tm_mb_no', 'LEFT');
        $this->db->order_by('tm_create_date', 'desc');
        $this->db->where(array('tm_mb_no' => $mb_no));


        $totalCount = $this->db->get('tb2_material')->num_rows();
        $sql = $this->db->last_query();

        // error_log('boardlist  ' .$sql);

        $startIndex = $count * $page;
        if ($count > 0) {
          $sql = $sql . " limit {$startIndex} , {$count} ";
        }
        $ret = $this->db->query($sql)->result();
        // error_log("ssql : ".$sql);

        $hasNext = true;
        if ($totalCount <= $startIndex + $count) {
            $hasNext = false;
        }

        return array (
                'meta' => array (
                        'hasNext' => $hasNext,
                        // 'query' => $query
                ),
                'data' => $ret
                ,
                'count' => $totalCount
        );
    }


    function getComments($tm_id) {
        $select_Board = 'tmc_id, tmc_mb_no, tmc_create_date, tmc_content';
        $select_member = 'mb_name, mb_nick, mb_email, mb_level, mb_profile';
        $this->db->select($select_Board.' , '.$select_member);
        $this->db->join('g4_member member', 'member.mb_no = tb2_material_comment.tmc_mb_no', 'LEFT');
        $this->db->order_by('tmc_create_date', 'asc');
        $this->db->where(array('tmc_tm_id' => $tm_id));

        return $this->db->get('tb2_material_comment')->result();
    }


    function insertComment($params) {
        date_default_timezone_set('UTC');
        $date = new DateTime();
        $params['tmc_create_date'] = $date->getTimestamp();
        $this->db->insert('tb2_material_comment', $params);

        $new_id = $this->db->insert_id();
        return $this->db->get_where('tb2_material_comment', array('tmc_id' => $new_id))->row();
    }

    function deleteComment($params) {
        $this->db->delete('tb2_material_comment', $params);
    }

    function insertLike($params, $tm_id) {
        // 게시물 like, hate count
        $this->db->select('tm_like_count, tm_hate_count');
        $material = $this->db->get_where('tb2_material', array('tm_id' => $tm_id))->row();
        if ($params['tml_type'] == '0' ) {
            // like
            $material->tm_like_count++;
        } else {
            // hate
            $material->tm_hate_count++;
        }
        $updateCount = array(
            'tm_like_count' => $material->tm_like_count,
            'tm_hate_count' => $material->tm_hate_count
        );
        $this->db->update ( 'tb2_material', $updateCount, array (
                'tm_id' => $tm_id
        ));


        $this->db->insert('tb2_material_like', $params);
        $new_id = $this->db->insert_id();
        return $this->db->get_where('tb2_material_like', array('tml_id' => $new_id))->row();
    }

    function updateLike($tml_type, $tml_id, $tm_id) {
        // 게시물 like, hate count
        $this->db->select('tm_like_count, tm_hate_count');
        $material = $this->db->get_where('tb2_material', array('tm_id' => $tm_id))->row();
        if ($tml_type == '0' ) {
            // like
            $material->tm_like_count++;
            $material->tm_hate_count--;
        } else {
            // hate
            $material->tm_like_count--;
            $material->tm_hate_count++;
        }
        $updateCount = array(
            'tm_like_count' => $material->tm_like_count,
            'tm_hate_count' => $material->tm_hate_count
        );
        $this->db->update ( 'tb2_material', $updateCount, array (
                'tm_id' => $tm_id
        ));


        $this->db->update ( 'tb2_material_like',
             array('tml_type' => $tml_type),
             array('tml_id' => $tml_id)
        );
        return $this->db->get_where('tb2_material_like', array('tml_id' => $tml_id))->row();
    }

    function addNewBoard($params) {
        $date = new DateTime();
        $params['tm_create_date'] = $date->getTimestamp();
        $this->db->insert('tb2_material', $params);

        $new_id = $this->db->insert_id();
        return $this->db->get_where('tb2_material', array('tm_id' => $new_id))->row();
    }

    function updateBoard($params, $board_id) {
		$this->db->update ( 'tb2_material', $params, array (
				'tm_id' => $board_id
		) );
        return $this->db->get_where('tb2_material', array('tm_id' => $board_id))->row();
    }

    function getSubjectList() {
        // lecture type?
        $this->db->select('cd_code, cd_desc');
        $this->db->where('cd_type', 'MT');
        $this->db->order_by('cd_code', 'ASC');

        return $this->db->get('tb_code')->result();

        // $sql = "select * from tb_code where cd_type='LT' order by cd_code";
		// $stmt = $this->db->query ( $sql );
		// return $stmt->result ();
    }

    function getFaqList() {
        // $sql = " select wr_id, wr_subject,wr_content,wr_1,wr_2 from g4_write_FAQ as b where 1=1 {$str_where}  order by wr_datetime desc ";
        // $this->db->where('cd_type', 'MT');
        $this->db->select('wr_id, wr_subject, wr_content, wr_1, wr_2, ca_name');
        $this->db->order_by('wr_datetime', 'ASC');
        return $this->db->get('g4_write_FAQ')->result();
    }

    function getNewsList() {
        $this->db->select('wr_datetime,wr_subject,wr_1,wr_2');
        $this->db->order_by('wr_datetime', 'desc');
        return $this->db->get('g4_write_news')->result();
    }

    // 맞춤형 학습

    function addNewCustomStudy($params) {
        $date = new DateTime();
        $params['tcs_create_date'] = $date->getTimestamp();
        $this->db->insert('tb2_custom_study', $params);

        $new_id = $this->db->insert_id();
        return $this->db->get_where('tb2_custom_study', array('tcs_id' => $new_id))->row();
    }


    // file begin
    function customStudyAddFile($params) {
        $date = new DateTime();
        $params['tcsa_create_date'] = $date->getTimestamp();
        $this->db->insert('tb2_custom_study_attach', $params);

        $new_id = $this->db->insert_id();
        return $this->db->get_where('tb2_custom_study_attach', array('tcsa_id' => $new_id))->row();
    }


    function customStudyUpdateFileForBoardId($tcsa_id, $updateParams) {
		$this->db->update ( 'tb2_custom_study_attach', $updateParams, array (
				'tcsa_id' => $tcsa_id
		) );

        return $this->db->get_where('tb2_custom_study_attach', array('tcsa_id' => $tcsa_id))->row();
    }
}
