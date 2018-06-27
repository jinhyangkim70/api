<?php

class user extends CI_Model {
    function __construct() {
        // Call the Model constructor
        parent::__construct ();
    }

    function get($ids){
        return $this->db->get_where('g4_member', $ids)->row();
    }

    function updateMember($ids, $params) {
        $this->db->update('g4_member', $params, $ids);
    }

    public function simpleProfile($mb_no) {
        $user_select = 'mb_no, mb_nick, mb_email, mb_nation, mb_time, mb_profile, mb_comm_tool, mb_skypeId';
        $user_select = $user_select.', mb_signature, mb_vod, mb_lang, mb_lang2, mb_engStudy';
        $user_select = $user_select.', tl1.Tl_eng as str_mb_lang, tl2.Tl_eng as str_mb_lang2';
        $this->db->select($user_select);
        $this->db->join('TB_language_zone tl1', 'tl1.Tl_no = mb_lang', 'LEFT');
        $this->db->join('TB_language_zone tl2', 'tl2.Tl_no = mb_lang2', 'LEFT');
        $member = $this->db->get_where('g4_member', array('mb_no' => $mb_no))->row();

        return $member;
    }

    public function sameLangTutor($mb_lang, $mb_lang2, $mb_no) {
        $user_select = 'mb_no, mb_nick, mb_email, mb_nation, mb_time, mb_profile';
        $user_select = $user_select.', mb_signature, mb_vod, mb_lang, mb_lang2, mb_engStudy';
        $user_select = $user_select.', tl1.Tl_eng as str_mb_lang, tl2.Tl_eng as str_mb_lang2, FLOOR(1 + RAND() * mb_no) as rand_ind';
        $this->db->select($user_select);
        $this->db->join('TB_language_zone tl1', 'tl1.Tl_no = mb_lang', 'LEFT');
        $this->db->join('TB_language_zone tl2', 'tl2.Tl_no = mb_lang2', 'LEFT');

        $levels = array(5, 6, 7);
        $this->db->where_in('mb_level', $levels);
        $this->db->where(array('mb_lang' => $mb_lang, 'mb_lang2' => $mb_lang2, 'mb_no !=' => $mb_no));
        $this->db->order_by('rand_ind', 'DESC');
        $member = $this->db->get('g4_member', 3, 0)->result();

        return $member;
    }

    public function profile($mb_no) {
        $user_select = 'mb_no, mb_nick, mb_email, mb_nation, mb_time, mb_comm_tool, mb_skypeId, mb_birth, mb_sex, mb_tel, mb_datetime';
        $user_select = $user_select.', mb_signature, mb_certifi01, mb_certifi01_desc, mb_certifi02, mb_certifi02_desc, mb_vod, mb_lang, mb_lang2, mb_engStudy, mb_korLevel, mb_thema';
        $user_select = $user_select.', mb_account, mb_k_bank, mb_k_account_no, mb_k_holdname, mb_status';
        $user_select = $user_select.', tl1.Tl_eng as str_mb_lang, tl2.Tl_eng as str_mb_lang2';
        $this->db->select($user_select);
        $this->db->join('TB_language_zone tl1', 'tl1.Tl_no = mb_lang', 'LEFT');
        $this->db->join('TB_language_zone tl2', 'tl2.Tl_no = mb_lang2', 'LEFT');
        $member = $this->db->get_where('g4_member', array('mb_no' => $mb_no))->row();

        // 경력
        $career = $this->_getCareer($mb_no);
        // 학력
        $ability = $this->_getAbility($mb_no);
        return array(
            // 'thema' => $thema,
            // 'userCountry' => $userCountry,
            // 'userLang' => $userLang,
            // 'userLang2' => $userLang2,
            //
            // 'introduce' => $introduce,
            // 'mb_certifi01' => $member->mb_certifi01,
            // 'mb_certifi01_desc' => $member->mb_certifi01_desc,
            // 'mb_certifi02' => $member->mb_certifi02,
            // 'mb_certifi02_desc' => $member->mb_certifi02_desc,
            // 'mb_vod' => $member->mb_vod,
            // 'youtubeId' => $youtubeId,
            // 'mb_nation' => $member->mb_nation,
            // 'mb_comm_tool' => $member->mb_comm_tool,
            // 'mb_lang' => $member->mb_lang,
            // 'mb_lang2' => $member->mb_lang2,
            'member' => $member,
            'career' => $career,
            'ability' => $ability,
        );
    }
     public function updateProfile($mb_no, $params) {
        $this->db->where('mb_no', $mb_no);
        $this->db->update('g4_member', $params);

        $user_select = 'mb_id, mb_nick, mb_email, mb_profile, mb_nation, mb_time, mb_comm_tool, mb_skypeId, mb_birth, mb_sex, mb_tel';
        $user_select = $user_select.', mb_signature, mb_certifi01, mb_certifi01_desc, mb_certifi02, mb_certifi02_desc, mb_vod, mb_lang, mb_lang2, mb_engStudy, mb_korLevel, mb_thema';
        $user_select = $user_select.', mb_account, mb_k_bank, mb_k_account_no, mb_k_holdname, mb_lorder_date, mb_level';

        $this->db->select($user_select);
        $member = $this->db->get_where('g4_member', array('mb_no' => $mb_no))->row();
      
       
        if ($member->mb_lorder_date == '0000-00-00 00:00:00') {
            // 프로필 입력 안됨 상태
            // 이메일, 이름, 국가, 기준 시간 만 필수로 요청 - 170822
            $isProfileComplete = true;
             if (empty($member->mb_email)) {
                // error_log('mb_email null');
                $isProfileComplete = false;
            }
            if (empty($member->mb_nick)) {
                // error_log('mb_nick null');
                $isProfileComplete = false;
            }
  
            if (empty($member->mb_nation)) {
                // error_log('mb_nation null'.$member->mb_nation);
                $isProfileComplete = false;
            }
            if (empty($member->mb_time)) {
                // error_log('mb_time null'.$member->mb_time);
                $isProfileComplete = false;
            }
  
            if ($isProfileComplete) {
                // mb_lorder_date 갱신  이날자를 기준으로 프로필완성이 됬는지 구분
                date_default_timezone_set('UTC');
                $this->db->where('mb_no', $mb_no);
                $this->db->update('g4_member', array('mb_lorder_date' => date("Y-m-d H:i:s")));
                if ($member->mb_level == 3) {
                    $str_time = time();
                    //강사 신청 완료
                    sendSystemMemo('30', $mb_no, '', $str_time, '', 0, 0);
                }
            }
        }
        if($member->mb_level == 3||$member->mb_level == 4||$member->mb_level == 5||$member->mb_level == 6||$member->mb_level == 7){
               $mail_subject=$member->mb_nick."님이 Profile을 수정하였습니다.";
               $mail_content_r="ID:".$member->mb_id.". Email:".$member->mb_email;
               $email_address1="jinhyangkim70@gmail.com";
               $email_address2="tutor.kore@gmail.com";
               $email_address3="tutor-k@naver.com";
               sendMail($email_address1, $mail_subject, $mail_content_r);
               sendMail($email_address2, $mail_subject, $mail_content_r);
               sendMail($email_address3, $mail_subject, $mail_content_r);
        }
        return $member;
    }


    public function checkValidProfile($mb_no) {
        $member = $this->db->get_where('g4_member', array('mb_no' => $mb_no))->row();

        if ($member->mb_lorder_date == '0000-00-00 00:00:00') {
            // 프로필 입력 안됨 상태

            // 필수 값 확인
            // 성별, 이메일, 이름, 생년, 스카이프 아이디, 국가, 기준 시간, 사용언어, 추가사용언어, 영어로 수업 가능


            // 이메일, 이름, 국가, 기준 시간 만 필수로 요청 - 170822
            $isProfileComplete = true;
            // if (empty($member->mb_profile)) {
            //     // error_log('mb_profile null');
            //     $isProfileComplete = false;
            // }
            // if (empty($member->mb_sex)) {
            //     // error_log('mb_sex null');
            //     $isProfileComplete = false;
            // }
            if (empty($member->mb_email)) {
                // error_log('mb_email null');
                $isProfileComplete = false;
            }
            if (empty($member->mb_nick)) {
                // error_log('mb_nick null');
                $isProfileComplete = false;
            }
            // if (empty($member->mb_birth)) {
            //     // error_log('mb_birth null '.$member->mb_birth);
            //     $isProfileComplete = false;
            // }
            // if (empty($member->mb_skypeId)) {
            //     // error_log('mb_skypeId null');
            //     $isProfileComplete = false;
            // }
            if (empty($member->mb_nation)) {
                // error_log('mb_nation null'.$member->mb_nation);
                $isProfileComplete = false;
            }
            if (empty($member->mb_time)) {
                // error_log('mb_time null'.$member->mb_time);
                $isProfileComplete = false;
            }
            // if (empty($member->mb_lang)) {
            //     // error_log('mb_lang null'.$member->mb_lang);
            //     $isProfileComplete = false;
            // }
            // if (empty($member->mb_lang2)) {
            //     // error_log('mb_lang2 null'.$member->mb_lang2);
            //     $isProfileComplete = false;
            // }if (empty($member->mb_engStudy)) {
            //     // error_log('mb_engStudy null'/$member->mb_engStudy);
            //     $isProfileComplete = false;
            // }

            if ($isProfileComplete) {
                return true;
            } else {
                return false;
            }
        }
        else {
            return true;
        }
    }


    public function updateCarrer($mb_no, $params_list){
        // 기존 코드대로 전부 삭제 하고 새로 추가
        $this->db->where('mb_no', $mb_no);
        $this->db->delete('g4_member_career');

        $user_select = 'mb_id, mb_nick, mb_email,mb_level';
        $this->db->select($user_select);
        $member = $this->db->get_where('g4_member', array('mb_no' => $mb_no))->row();
        foreach ($params_list as $item) {
            $item['mb_no'] = $mb_no;
            $this->db->insert('g4_member_career', $item);
      //      $content=$member->mb_nick."님이 Carrer을 수정하였습니다.";
        }
     //   sent_email($member->id,$member->nick, $content );
        if($member->mb_level == 3||$member->mb_level == 4||$member->mb_level == 5||$member->mb_level == 6||$member->mb_level == 7){
               $mail_subject=$member->mb_nick."님이 Carrer를 수정하였습니다.";
               $mail_content_r="ID:".$member->mb_id.". Email:".$member->mb_email;
               $email_address1="jinhyangkim70@gmail.com";
               $email_address2="tutor.kore@gmail.com";
               $email_address3="tutor-k@naver.com";
               sendMail($email_address1, $mail_subject, $mail_content_r);
               sendMail($email_address2, $mail_subject, $mail_content_r);
               sendMail($email_address3, $mail_subject, $mail_content_r);
        }
        
        return $this->_getCareer($mb_no);
    }

    public function updateAbility($mb_no, $params_list){
        // 기존 코드대로 전부 삭제 하고 새로 추가
        $this->db->where('mb_no', $mb_no);
        $this->db->delete('g4_member_ability');
        $user_select = 'mb_id, mb_nick, mb_email,mb_level';
        $this->db->select($user_select);
        $member = $this->db->get_where('g4_member', array('mb_no' => $mb_no))->row();
   
        foreach ($params_list as $item) {
            
            $item['mb_no'] = $mb_no;
            $this->db->insert('g4_member_ability', $item);
          //  $content=$member->mb_nick."님이 Ability를 수정하였습니다.";
        }
          if($member->mb_level == 3||$member->mb_level == 4||$member->mb_level == 5||$member->mb_level == 6||$member->mb_level == 7){
               $mail_subject=$member->mb_nick."님이 Ability를 수정하였습니다.";
               $mail_content_r="ID:".$member->mb_id.". Email:".$member->mb_email;
               $email_address1="jinhyangkim70@gmail.com";
               $email_address2="tutor.kore@gmail.com";
               $email_address3="tutor-k@naver.com";
               sendMail($email_address1, $mail_subject, $mail_content_r);
               sendMail($email_address2, $mail_subject, $mail_content_r);
               sendMail($email_address3, $mail_subject, $mail_content_r);
        }
        return $this->_getAbility($mb_no);
    }

    public function account($mb) {

        // koin 정보
        $koin = $this->_getKoin($mb);

        // 은행 정보
        $state = -1;
        $mb_k_bank_value = '';
        if($mb->mb_account && $mb->mb_k_bank && $mb->mb_k_account_no && $mb->mb_k_holdname) {
            $state = 1;
            $mb_k_bank_value = getBankData($mb->mb_k_bank);
        } else if($mb->mb_k_bank && $mb->mb_k_account_no && $mb->mb_k_holdname){
            $state = 2;
            $mb_k_bank_value = getBankData($mb->mb_k_bank);
        } else {
            $state = 3;
        }

        // 계정 비밀번호 기본 비밀번호 같음
        $default = false;
        if( $mb->mb_password === "*F7997666A094562FC5D5A9698AF1391FC12A6AE6") {
            $default = true;
        }



        return array(
            'koin' => $koin,
            'mb_account' => $mb->mb_account,
            'mb_k_bank' => $mb->mb_k_bank,
            'mb_k_bank_value' => $mb_k_bank_value,
            'mb_k_account_no' => $mb->mb_k_account_no,
            'mb_k_holdname' => $mb->mb_k_holdname,
            'state' => $state,
            'default' => $default,
        );
    }

    // $src가 $target을 즐겨찾기를 등록한 경우 true
    public function getFav($src, $target) {
        $query = "select (select tf_mb_no from TB_fav_member where tf_mb_no='".$target."' and tf_mb_lec_no=mb_no) as fv_yn from g4_member where mb_no = {$src}";
        $stmt = $this->db->query($query);

        $row = $stmt->row();

        if($row) {
            if($row->fv_yn == null) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    // 한번이라도 시범강의를 들었는지?
    // 17-11-08 요청 학생이 취소하거나 자동만료취소의 경우 허용해줌
    public function isTrialPossible($teacher_mb_no, $student_mb_no) {
        $this->db->where(array(
            'lr_lec_cd' => 'T00',
            'lr_mb_no' => $student_mb_no,
            'lr_lec_mb_no' => $teacher_mb_no
        ));
        $names = array('C', 'T');
        $this->db->where_not_in('lr_status', $names);

        $this->db->from('TB_lec_reservation');
        $count = $this->db->count_all_results();

        // $sql = $this->db->last_query();
        // error_log('isTrialPossible === '.$sql);

        if ($count > 0) {
            return false;
        } else {
            return true;
        }
    }

    public function getThemeList($mb) {

        if($mb->mb_level == 5 || $mb->mb_level == 6 || $mb->mb_level == 7) {
            // 선생님 theme list
            $query = "select * from TB_lec_main where lm_mb_no='{$mb->mb_no}' order by 1";
            $stmt = $this->db->query ( $query );

            $ret = array ();

            foreach ( $stmt->result () as $item ) {
                $str_group = false;
                if ($item->lm_group_yn == "Y") {
                    $str_group = true;
                }

                array_push ( $ret, array (
                    'lm_lec_cd' => $item->lm_lec_cd,
                    'lm_lec_cd_etc' => $item->lm_lec_cd_etc,
                    'isGroup' => $str_group
                    ) );
                }

            } else {
                // 학생 theme list
                $query = "select cd_desc, cd_code from  tb_code where cd_type='LT' and cd_code in ('".str_replace("|", "','", $mb->mb_thema)."') and cd_use_yn='Y' order by cd_code; ";
                $stmt = $this->db->query($query);

                $ret = array();

                foreach ( $stmt->result () as $row ) {
                    array_push($ret, (object)array(
                        'desc' => $row->cd_desc,
                        'lm_lec_cd' => $row->cd_code,
                    ));
                }
            }

            return $ret;
        }

        // 강사의 렉쳐 정보
        public function profileLecture($seq) {
            // $query = "select * from TB_lec_main where lm_mb_no='{$seq}' order by lm_sort desc";
            //
            // $stmt = $this->db->query($query);
            //
            // $ret = array();
            // foreach( $stmt->result() as $row) {
            // 	array_push($ret, $row);
            // }

            $this->db->where(array('lm_mb_no' => $seq));
            $this->db->order_by('lm_sort', 'asc');
            $this->db->order_by("lm_seq", "asc");
            $lectureList = $this->db->get('TB_lec_main')->result();

            // $ret = array();
            foreach( $lectureList as $row) {
                $this->db->where(array('lr_lec_mb_no' => $seq, 'lr_lec_cd' => $row->lm_lec_cd, 'lr_status' => 'F'));
                $this->db->from('TB_lec_reservation');
                $row->completeLecture = $this->db->count_all_results();
            }

            return $lectureList;
        }

        public function getNationList() {
            $query = "select * from TB_country where co_code<>'9999' order by 4";

            $stmt = $this->db->query($query);

            return $stmt->result();
        }

        private function _getCareer($mb_no) {
            $query = "select count(*) as cnt from g4_member_career where mb_no='{$mb_no}'";
            //echo $query;
            $current_cnt = $this->db->query($query)->row()->cnt;


            $max_lm_cnt = 5;
            if($max_lm_cnt > $current_cnt) {
                $max_lm_cnt = $current_cnt;
            }

            $query = "select mb_no, mb_job, mb_officename, mb_term, mb_country, mb_career from g4_member_career where mb_no='{$mb_no}' order by seq asc limit 0, {$max_lm_cnt}";

            $stmt = $this->db->query($query);

            $ret = array ();
            if ($stmt->num_rows () > 0) {
                foreach ( $stmt->result () as $row ) {
                    $row->mb_career = str_replace("\\", "", $row->mb_career);
                    array_push($ret, $row);
                }
            }

            return $ret;
        }

        private function _getAbility($mb_no) {
            /* 학력 */
            $query = "select count(*) as cnt from g4_member_ability where mb_no='{$mb_no}'";
            $current_cnt = $this->db->query($query)->row()->cnt;
            $query = "select mb_no, mb_major, mb_degree, mb_term, mb_school from g4_member_ability where mb_no='{$mb_no}' order by seq asc";
            $stmt = $this->db->query($query);

            $ret = array ();
            if ($stmt->num_rows () > 0) {
                foreach ( $stmt->result () as $row ) {
                    array_push($ret, $row);
                }
            }

            return $ret;
        }

        private function _getKoin($mb) {
            $query = "SELECT COALESCE(SUM(tp_in_koin),0) AS koin_in, COALESCE(SUM(tp_out_koin),0) AS koin_out, (COALESCE(SUM(tp_in_koin),0)-COALESCE(SUM(tp_out_koin),0)) AS koin_bal
            FROM   Tb_payment
            where tp_mb_no='{$mb->mb_no}'";
            //echo $query;
            $stmt = $this->db->query($query);

            $koin_in = 0;
            $koin_out = 0;
            $koin_bal = 0;

            if($stmt->num_rows() > 0) {
                $row = $stmt->row();
                if( $row->koin_in !== "" )  $koin_in = $row->koin_in;
                if( $row->koin_out !== "" ) $koin_out = $row->koin_out;
                if( $row->koin_bal !== "" ) $koin_bal = $row->koin_bal;
            }

            $query = "select COALESCE(SUM(tp_in_koin),0) AS koin_in, COALESCE(SUM(tp_out_koin),0) AS koin_out, (COALESCE(SUM(tp_in_koin),0)-COALESCE(SUM(tp_out_koin),0)) AS koin_bal from Tb_payment where (tp_payment_code is null and tp_mode='I' or tp_mode='O') and tp_mb_no={$mb->mb_no}";
            $stmt = $this->db->query($query);

            $result_koin_bal = 0;
            if($stmt->num_rows() > 0) {
                $row = $stmt->row();
                $result_koin_bal = $row->koin_bal;
                if($result_koin_bal < 0) {
                    $result_koin_bal = 0;
                }
            }
            return array(
                'in' => $koin_in,
                'out' => $koin_out,
                'bal' => $koin_bal,
                'result_bal' => (int)$result_koin_bal,
            );
        }

        public function getFindUser($index, $count, $nick_name) {
            if (!empty($nick_name)) {
                // error_log("nick_name == {$nick_name}");
                $this->db->like('mb_nick', $nick_name);
            }
            $totalCount = $this->db->get('g4_member')->num_rows();

            $this->db->select(
                'mb_id, mb_no, mb_nick, mb_name, mb_name_last, mb_profile, mb_signature, mb_skypeId, country.CO_NAME_ENG'
            );

            // 국가
            // $mbCountry = $this->db->get_where('TB_country', array('CO_CODE' => $mb->mb_nation))->row();
            // $userCountry = $mbCountry ? $mbCountry->CO_NAME_ENG : '';

            // 국가
            $this->db->join('TB_country country', 'country.CO_CODE = g4_member.mb_nation', 'LEFT');

            if (!empty($nick_name)) {
                // error_log("nick_name == {$nick_name}");
                $this->db->like('mb_nick', $nick_name);
            }
            $this->db->order_by('mb_today_login', 'DESC');
            $this->db->limit ( $count, $index * $count);
            $userList = $this->db->get('g4_member')->result();

            return array('userList' => $userList, 'totalCount' => $totalCount);
        }

        function insertUser($mb_email, $password, $mb_level, $mb_time) {
            // $mb_level 3 학원강사 email로 등록

            if (function_exists("date_default_timezone_set"))
            date_default_timezone_set("Asia/Seoul");

            $temp_date = date("Y-m-d H:i:s");

            $mb_password = $this->get_password($password);
            $mb_9 = $this->get_password($mb_email."tutorK");
            //
            $params = array(
                'mb_id' => $mb_email,
                'mb_password' => $mb_password,
                'mb_email' => $mb_email,
                'mb_datetime' => $temp_date,
                'mb_ip' => $_SERVER['REMOTE_ADDR'],
                'mb_level' => $mb_level,
                'mb_9' => $mb_9,
                'mb_10' => 'login_email',
                'mb_login_ip' => $_SERVER['REMOTE_ADDR'],
                'mb_time' => $mb_time
            );
            $this->db->insert('g4_member', $params);

            $temp = $this->db->insert_id();
            // error_log('new mb_no '.$temp);
            return $temp;
        }

        function insertFacebookUser($fb_user, $image_path, $mb_level, $mb_time) {
            // $mb_level 3 학원강사 email로 등록

            if (function_exists("date_default_timezone_set"))
            date_default_timezone_set("Asia/Seoul");

            $temp_date = date("Y-m-d H:i:s");

            // facebook 임시 비번설정?
            $mb_password = $this->get_password("fb_tutorK_1234");
            $mb_9 = $this->get_password($fb_user['email']."tutorK");

            if ($fb_user['gender'] == "male") { // 성별작업
                $gender = "M";
            }else{
                $gender = "F";
            }

            $params = array(
                'mb_id' => 'fb_'.$fb_user['id'],
                'mb_password' => $mb_password,
                'mb_name' => $fb_user['first_name'],
                'mb_name_last' => $fb_user['last_name'],
                'mb_sex' => $gender,
                // 'mb_birth' => '1900',
                'mb_nick' => $fb_user['name'],
                'mb_email' => $fb_user['email'],
                'mb_homepage' => $fb_user['link'],
                'mb_datetime' => $temp_date,
                'mb_ip' => $_SERVER['REMOTE_ADDR'],
                'mb_level' => $mb_level,
                'mb_profile' => $image_path,
                'mb_9' => $mb_9,
                'mb_10' => 'login_email',
                'mb_time' => $mb_time
            );
            $this->db->insert('g4_member', $params);
            $temp = $this->db->insert_id();
            // error_log('new mb_no '.$temp);
            return $temp;
        }

        function profileReply($lmn) {

            // old
            // $query = "where ta_lec_mb_no='{$lmn}' and ta_score > 0 and exists (select 1 from TB_lec_reservation where lr_status='F' and lr_seq=TB_after_comment.ta_lr_seq)";

            // 점수별로 sort 학생별로 group
            // $query = "SELECT * FROM";
            // $query = $query." ( SELECT `ta_mb_no`, `ta_reg_dt`, `ta_score`, `g4_member`.`mb_nick`, `g4_member`.`mb_profile`, `TB_after_comment`.`ta_commnet`";
            // $query = $query." FROM (`TB_after_comment`)";
            // $query = $query." LEFT JOIN `TB_lec_reservation` ON `lr_seq` = `ta_lr_seq`";
            // $query = $query." LEFT JOIN `g4_member` ON `mb_no` = `ta_mb_no`";
            // $query = $query." WHERE `ta_lec_mb_no` = {$lmn}";
            // $query = $query." AND `ta_score` > 0";
            // $query = $query." AND `TB_lec_reservation`.`lr_status` =  'F'";
            // $query = $query." ORDER BY ta_score DESC";
            // $query = $query.") AS sub";
            // $query = $query." GROUP BY `ta_mb_no`";
            // $stmt = $this->db->query($query);
            // $row = $stmt->result();

            // 완료된 강의의 학생 id 가저옴
            // SELECT max(ta_score), ta_mb_no, g4_member.mb_name, g4_member.mb_profile, TB_after_comment.ta_commnet
            // FROM tutork.TB_after_comment
            // join g4_member on g4_member.mb_no = TB_after_comment.ta_mb_no
            // join TB_lec_reservation on lr_seq = ta_lr_seq
            // where ta_lec_mb_no=120 and TB_lec_reservation.lr_status = 'F'
            // group by ta_mb_no;


            $this->db->select('ta_mb_no, ta_reg_dt, g4_member.mb_nick, g4_member.mb_profile, TB_after_comment.ta_commnet');
            $this->db->join('TB_lec_reservation', 'lr_seq = ta_lr_seq', 'LEFT');
            $this->db->join('g4_member', 'mb_no = ta_mb_no', 'LEFT');

            $this->db->where(array(
                'ta_lec_mb_no' => $lmn,
                'ta_score >' => 0,
                'TB_lec_reservation.lr_status' => 'F',
            ));

            $this->db->order_by('ta_reg_dt', 'desc');
            $row = $this->db->get('TB_after_comment')->result();

            // $sql = $this->db->last_query();
            // error_log('profileReply === '.$sql);


            return $row;
        }

        // 회원의 타임존 가저옴
        function get_member_by_mb_no($mb_no, $fields='*')
        {
            /*
            global $g4;
            //echo  "select $fields from $g4[member_table] where mb_id = TRIM('$mb_id') or mb_email = TRIM('$mb_id')";
            return sql_fetch(" select $fields from $g4[member_table] where mb_id = TRIM('$mb_id') or mb_email = TRIM('$mb_id') ");
            */

            if ($fields != '*') {
                $this->db->select($fields);
            }

            $this->db->where(array('mb_no' => trim($mb_no)));

            return $this->db->get('g4_member')->row();
        }

        function insertNoti($me_recv_mb_no, $me_strtime, $me_memo, $me_lr_seq=0, $me_tlp_id=0) {
            date_default_timezone_set('UTC');

            $item = array(
                'me_recv_mb_no' => $me_recv_mb_no,
                'me_send_datetime' => date("Y-m-d H:i:s"),
                'me_memo' => $me_memo,
                'me_lr_seq' => $me_lr_seq,
                'me_tlp_id' => $me_tlp_id
            );

            $this->db->insert('tb_memo_system', $item);
        }

        function get_userLanguage($mb_no){
            $this->db->where(array('mb_no' => $mb_no));
            return $this->db->get('g4_member')->row()->mb_lang;
        }

        function updatePassword($old, $new, $mb_no){
            $ret = 'fail';
            $this->db->where(array('mb_no' => $mb_no));
            $password = $this->db->get('g4_member')->row()->mb_password;

            $old_password = $this->get_password($old);
            $new_password = $this->get_password($new);

            if ($password != $old_password) {
                $ret = 'password not equal';
            } else {
                $this->db->update('g4_member', array('mb_password' => $new_password), array('mb_no' => $mb_no));
                $ret = 'success';
            }

            return $ret;
        }

        function initPassword($mb_id) {
            // error_log('init passwor begin : '.$mb_id);

            // 페이스북 유저는 제외하고 처리
            $query = "select count(*) as cnt from g4_member where mb_id = '{$mb_id}'";
            $current_cnt = $this->db->query($query)->row()->cnt;
            $passwd = rand(100001, 999999);
            $mb_password = $this->get_password($passwd);

            // error_log('find count : '.$current_cnt);

            $ret = '';
            if ($current_cnt > 0) {
                // error_log(' pass word reset :: '.$passwd);
                // error_log(' sql password reset :: '.$mb_password);
                $this->db->update('g4_member', array('mb_password' => $mb_password), array('mb_id' => $mb_id));

                // $ret = '등록된 이메일 주소로 새로운 패스워드가 발송 되었습니다.';
                $msg = 'pass_5';

                sendChangepwdMail($mb_id, $passwd);
                // sendChangepwdMail('ms08you@gmail.com', $passwd);
            } else {
                // $query = "select count(*) as cnt from g4_member where mb_email = '{$mb_id}'";
                // $current_cnt = $this->db->query($query)->row()->cnt;
                //
                // if ($current_cnt > 0) {
                //     $ret = '등록된 이메일 주소가 아닙니다. 페이스북으로 가입하신 회원은 페이스북으로 로그인해주세요.';
                // } else {
                //     $ret = '등록된 이메일 주소가 아닙니다.';
                // }
                $msg = 'pass_7';
            }

            return array('msg' => $msg);
        }

        // 사용자 차단
        function addBlockUser($mb_no, $block_mb_no)
        {
            date_default_timezone_set('UTC');
            $date = new DateTime();

            $block_params = array(
                'tbu_mb_no' => $mb_no,
                'tbu_block_mb_no' => $block_mb_no,
                'tbu_create_date' => $date->getTimestamp(),
            );

            $this->db->insert('tb2_block_user', $block_params);

            $new_id = $this->db->insert_id();
            return $this->db->get_where('tb2_block_user', array('tbu_id' => $new_id))->row();
        }

        function removeBlock($mb_no, $block_mb_no)
        {
            $this->db->where('tbu_mb_no', $mb_no);
            $this->db->where('tbu_block_mb_no', $block_mb_no);

            $this->db->delete('tb2_block_user');
        }

        function blockList($mb_no)
        {
            $this->db->where(array('tbu_mb_no' => $mb_no));
            $this->db->order_by('tbu_create_date');
            $blockList = $this->db->get('tb2_block_user')->result();

            // $sql = $this->db->last_query();
            // error_log('blockList === '.$sql);
            // error_log('blockList : '.json_encode($blockList));

            foreach ($blockList as $item) {
                // error_log('item : '. json_encode($item));
                // error_log('tbu_block_mb_no: '. $item->tbu_block_mb_no);
                $this->db->select(
                    'mb_id, mb_no, mb_nick, mb_name, mb_name_last, mb_profile'
                );
                $tempUser = $this->db->get_where('g4_member', array('mb_no' => $item->tbu_block_mb_no))->row();

                if ($tempUser) {
                    $item->mb_nick = $tempUser->mb_nick;
                    $item->mb_profile = $tempUser->mb_profile;
                    $item->mb_no = $tempUser->mb_no;
                }
            }

            return $blockList;
        }

        function twoWayBlockList($mb_no)
        {
            $this->db->where('tbu_mb_no', $mb_no);
            $this->db->or_where('tbu_block_mb_no', $mb_no);
            $this->db->order_by('tbu_create_date');
            $blockList = $this->db->get('tb2_block_user')->result();
            return $blockList;
        }

        // 신고
        function addDeclareReport($mb_no, $declare_mb_no, $tdr_memo)
        {
            date_default_timezone_set('UTC');
            $date = new DateTime();

            $new_params = array(
                'tdr_mb_no' => $mb_no,
                'tdr_declare_mb_no' => $declare_mb_no,
                'tdr_memo' => $tdr_memo,
                'tdr_create_date' => $date->getTimestamp(),
            );

            $this->db->insert('tb2_declare_report', $new_params);

            $new_id = $this->db->insert_id();
            return $this->db->get_where('tb2_declare_report', array('tdr_id' => $new_id))->row();
        }


        // 광고
        function getRandomAd()
        {

            date_default_timezone_set('UTC');
            $date = new DateTime();

            $params = array(
                'tta_start_date <' => $date->getTimestamp(),
                'tta_end_date >' => $date->getTimestamp(),
            );

            $randomNum = mt_rand(1, 10);
            if ($randomNum % 2) {
                $sort = 'ASC';
            } else {
                $sort = 'DESC';
            }

            $this->db->select('tta_id, tta_mb_no, tta_start_date, tta_end_date, tta_create_date, tta_img, FLOOR(1 + RAND() * tta_id) as rand_ind');
            $this->db->where($params);
            $this->db->order_by('rand_ind', $sort);
            $result = $this->db->get('tb2_teacher_advertising', 1, 0)->row();

            $sql = $this->db->last_query();
            // error_log('isTrialPossible === '.$sql);

            // find user
            if (!empty($result)) {
                $user_select = 'mb_no, mb_nick, mb_email, mb_nation, mb_time, mb_profile, mb_comm_tool, mb_skypeId';
                $user_select = $user_select.', mb_signature, mb_vod, mb_lang, mb_lang2, mb_engStudy';
                $this->db->select($user_select);
                $member = $this->db->get_where('g4_member', array('mb_no' => $result->tta_mb_no))->row();

                $result->mb_nick = $member->mb_nick;
                $result->mb_email = $member->mb_email;
            }
            return $result;
        }

        function inviteFriends($mb_no, $email) {
            date_default_timezone_set('UTC');
            $date = new DateTime();

            $this->db->where('tif_recv_email', $email);
            $this->db->from('tb2_invite_friends');
            $findEmail = $this->db->count_all_results();

            if ($findEmail > 0) {
                return array('msg' => 'fail');
            } else {
                $new_params = array(
                    'tif_mb_no' => $mb_no,
                    'tif_recv_email' => $email,
                    'tif_create_date' => $date->getTimestamp(),
                );

                $this->db->insert('tb2_invite_friends', $new_params);

                $new_id = $this->db->insert_id();
                $insertData = $this->db->get_where('tb2_invite_friends', array('tif_id' => $new_id))->row();
                return array('msg' => 'success', 'data' => $insertData);
            }
        }

        function myInviteList($mb_no) {
            return $this->db->get_where('tb2_invite_friends', array('tif_mb_no' => $mb_no))->result();
        }

        function checkInviteUser($mb_email) {
            // error_log('$mb_email'.$mb_email);
            $this->db->where('tif_recv_email', $mb_email);
            $this->db->where('tif_status', 0);
            $this->db->update('tb2_invite_friends', array('tif_status' => 1));
            // $sql = $this->db->last_query();
            // error_log('update === '.$sql);
        }

        // 초대된 회원인 경우 payment 추가, 초대한 회원도 추가 100koin (10$ =100koin)
        function checkInviteUserIncome($mb_email, $mb_no) {
            // 초대된 회원인지, 한번도 수강을 안했는지 조회
            // $findInvite = $this->db->get_where('tb2_invite_friends', array('tif_recv_email' => $mb_email, 'tif_status' => 1))->row();
            $findInvite = $this->db->get_where('tb2_invite_friends', array('tif_recv_email' => $mb_email))->row();

            $this->db->where('lr_mb_no' , $mb_no);
            $this->db->from('TB_lec_reservation');
            $reservationCount = $this->db->count_all_results();

            // error_log('$mb_email '.$mb_email);
            // error_log('$reservationCount '.$reservationCount);
            // error_log('$findInvite '.json_encode($findInvite));

            // 강의가 한개이상 일떄 (강의는 유료강의 여야 함)
            if ($findInvite && $findInvite->tif_status == 1 && $reservationCount > 1) {
                // error_log('insert invite fee '.$mb_no);
                $payment_params = array(
                    'tp_mb_no' => $findInvite->tif_mb_no,
                    // 'tp_lr_seq' => $reservation_id,
                    'tp_mode' => 'I',
                    'tp_in_koin' => 100,
                    'tp_reg_dt' => date("Y-m-d H:i:s"),
                    'tp_memo' => 'Invite a friend',
                );
                $this->db->insert('Tb_payment', $payment_params);

                $payment_params = array(
                    'tp_mb_no' => $mb_no,
                    // 'tp_lr_seq' => $reservation_id,
                    'tp_mode' => 'I',
                    'tp_in_koin' => 100,
                    'tp_reg_dt' => date("Y-m-d H:i:s"),
                    'tp_memo' => 'Invite a friend',
                );
                $this->db->insert('Tb_payment', $payment_params);

                $this->db->where('tif_recv_email', $mb_email);
                $this->db->where('tif_status', 1);
                $this->db->update('tb2_invite_friends', array('tif_status' => 2, 'tif_koin' => 100));

                // sendSystemMemo('30', $mb_no, '', $str_time, '', 0, 0);
                // sendSystemMemo('30', $mb_no, '', $str_time, '', 0, 0);
            }

            // $sql = $this->db->last_query();
            // error_log('update === '.$sql);
        }
        // convert tutor-k function begin================================================================================
        // 회원 정보를 얻는다.
        function get_member($mb_id, $fields='*')
        {
            /*
            global $g4;
            //echo  "select $fields from $g4[member_table] where mb_id = TRIM('$mb_id') or mb_email = TRIM('$mb_id')";
            return sql_fetch(" select $fields from $g4[member_table] where mb_id = TRIM('$mb_id') or mb_email = TRIM('$mb_id') ");
            */

            if ($fields != '*') {
                $this->db->select($fields);
            }

            $this->db->where(array('mb_id' => trim($mb_id)));
            $this->db->or_where(array('mb_email' => trim($mb_id)));

            return $this->db->get('g4_member')->row();
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
        // convert tutor-k function end================================================================================
    }
