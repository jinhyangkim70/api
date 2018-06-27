<?php

// error_log('__DIR__'.__DIR__);
// require APPPATH . '../vendor/phpmailer/phpmailer/PHPMailerAutoload.php';

if (! defined ( 'BASEPATH' ))
exit ( 'No direct script access allowed' );

if (! function_exists ( 'sendMail' )) {
    function sendMail($mailto, $SUBJECT, $CONTENT) {
        // error_log('mailto : '.$mailto);
        // error_log('subject :'.$SUBJECT);
        $ROOT_URL = TUTORK_ROOT_URL;
        $mail_common = "<!DOCTYPE html><html lang='kr'><head><meta http-equiv='content-type' charset='EUC-KR'/><meta http-equiv='X-UA-Compatible' content='IE=edge'><title>tutorK: 튜터K닷컴</title><meta name='Description' content=''/><meta name='KEYWORDS' content=''/> </head><body> <div style='margin:0 auto;padding:9px 10px 14px 10px;width:680px;'> <div style='padding:0 20px;border:1px solid #c4c7cc;'> <div style='position:relative;border-bottom:1px solid #e1e2e6;height:81px;'> <h1 style='position:absolute;left:0;top:23px;margin:0;margin-top:15px;'><img src='http://www.tutor-k.com/images/common/maillogo.png' alt='tutorK' style='margin-top:5px;'/></h1> <p style='padding-top:0px;font-size:11px;line-height:15px;color:#979b9e;text-align:right;'>This is only outgoing mailing account. No reply mail can be processed</p></div><div style='padding:24px 0 45px;font-size:12px;line-height:16px;font-weight:bold;color:#5d6266;'>[content] <p style='margin-top:115px;'><a href='";
        $mail_common = $mail_common.$ROOT_URL;
        $mail_common = $mail_common."' style='color:#007afe;text-decoration:underline;' target='_blank'>Most efficient way of learning Korean! Visit tutorK right now</a></p></p></div></div></div></body></html>";

        $mail_content = str_replace("[content]", $CONTENT, $mail_common);
        // $mail_content = $CONTENT;

        // $smtp_mail_id = "yu.devit@gmail.com";
        // $smtp_mail_pw = "mb18noma";
        $smtp_mail_id = "tutor.kore@gmail.com";
    	$smtp_mail_pw = "kore1004";
        $to_email = $mailto;
        $to_name = "";
        $title = $SUBJECT;

        $from_name="tutork - Admin";
        // $from_email="yu.devit@gmail.com";
        $from_email="tutor.kore@gmail.com";
        // $content = $mail_content;

        $smtp_use = 'smtp.gmail.com'; //구글 메일 사용시 주석제거

        //메일러 로딩
        // require_once($_SERVER['DOCUMENT_ROOT']."/board/lib/class.phpmailer.php");
        // require_once($_SERVER['DOCUMENT_ROOT']."/board/lib/class.smtp.php");

/*
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = $smtp_use; // email 보낼때 사용할 서버를 지정
        $mail->SMTPAuth = true; // SMTP 인증을 사용함
        $mail->Port = 465; // email 보낼때 사용할 포트를 지정
        $mail->SMTPSecure = "ssl"; // SSL을 사용함
        $mail->Username = $smtp_mail_id; // 계정
        $mail->Password = $smtp_mail_pw; // 패스워드
        $mail->setFrom($from_email, $from_name); // 보내는 사람 email 주소와 표시될 이름 (표시될 이름은 생략가능)
        $mail->addAddress($to_email, $to_name); // 받을 사람 email 주소와 표시될 이름 (표시될 이름은 생략가능)
        $mail->Subject = $title; // 메일 제목
        // $mail->Subject = iconv("UTF-8", "EUC-KR", $title); // 메일 제목
        // $mail->MsgHTML($content); // 메일 내용 (HTML 형식도 되고 그냥 일반 텍스트도 사용 가능함)
        $mail->isHTML(true);
        $mail->Body = $mail_content; // 메일 내용 (HTML 형식도 되고 그냥 일반 텍스트도 사용 가능함)
        // $mail->Body = iconv("UTF-8", "EUC-KR", $mail_content); // 메일 내용 (HTML 형식도 되고 그냥 일반 텍스트도 사용 가능함)

        $mail->ContentType = "text/html";
        $mail->CharSet = "utf-8";

        $mail->Send(); // 실제로 메일을 보냄
        //echo "메일을 전송하였습니다.";
*/

        require_once($_SERVER['DOCUMENT_ROOT']."/board/lib/class.phpmailer.php");
        require_once($_SERVER['DOCUMENT_ROOT']."/board/lib/class.smtp.php");

    	$mail = new PHPMailer(true);
    	$mail->IsSMTP();
    	try {
    	$mail->Host = $smtp_use; // email 보낼때 사용할 서버를 지정
    	$mail->SMTPAuth = true; // SMTP 인증을 사용함
    	$mail->Port = 465; // email 보낼때 사용할 포트를 지정
    	$mail->SMTPSecure = "ssl"; // SSL을 사용함
    	$mail->Username = $smtp_mail_id; // 계정
    	$mail->Password = $smtp_mail_pw; // 패스워드
    	$mail->SetFrom($from_email, $from_name); // 보내는 사람 email 주소와 표시될 이름 (표시될 이름은 생략가능)
    	$mail->AddAddress($to_email, $to_name); // 받을 사람 email 주소와 표시될 이름 (표시될 이름은 생략가능)
    	$mail->Subject = $title; // 메일 제목
    	$mail->MsgHTML($mail_content); // 메일 내용 (HTML 형식도 되고 그냥 일반 텍스트도 사용 가능함)
    	$mail->Send(); // 실제로 메일을 보냄
    	//echo "메일을 전송하였습니다.";

    	} catch (phpmailerException $e) {
    	echo $e->errorMessage();
    	} catch (Exception $e) {
    	echo $e->getMessage();
    	}

    }
}

if (! function_exists ( 'sendConfirmMail' )) {
    // 이메일로 회원 가입시 메일 보냄
    function sendConfirmMail($mb_email, $mb_level="2"){
        $CI =& get_instance();
        $CI->load->model ( 'user' );

        $fnick="tutork - Admin";
        $fmail="yu.devit@gmail.com";
        $to=$mb_email;

        if($mb_level=="3"){
            $mail_subject="[TutorK] Tutor-K 선생님 가입 인증 안내입니다.";
        }else{
            $mail_subject="[TutorK] Sign up confirmation from Tutor-K";
        }

        if($mb_level=="3"){
        }else{
            $mail_content_r="<p>Dear ". $mb_email ."</p>";
        }

        // $login_path = "http://www.tutor-k.com/profile/signup_email_ok.php?ch=";
        // $login_path = "http://localhost:3000/shadowLogin/";
        // $login_path = "http://localhost:3000/#/shadowLogin/";
        // $root_url = "http://www.tutor-k.com/bt2_1/#";
        $root_url = TUTORK_ROOT_URL;
        $login_path = $root_url."/shadowLogin/";
        $profile_path = $root_url."/setting/profile";
        $key = $CI->user->get_password($mb_email."tutorK");

        if($mb_level=="3"){
            $mail_content_r = "<p style=\"margin-top:12px;\">". $mb_email ."님,<br/>환영합니다!!<br/>";
            $mail_content_r = $mail_content_r."Tutor-K에 강사로 가입해주셔서 감사합니다.<br/>";
            $mail_content_r = $mail_content_r."아래의 링크를 클릭하셔서 가입을 완료해주시길 바랍니다.<br/><a href=\"".$login_path.$key."\" style=\"color:#007afe;text-decoration:underline;\">tutor-K, 가입신청합니다.</a><br/><br/>";
            $mail_content_r = $mail_content_r."<span style=\"text-decoration:underline;\">유튜브 소개 동영상 및 프로필은 강사 승인 필수 요건입니다.</span><br/>";
            $mail_content_r = $mail_content_r."(유튜브 소개 동영상이나 프로필을 공백으로 남겨 두시면 강사 승인이 되지 않습니다.)<br/>";
            $mail_content_r = $mail_content_r."유튜브 소개 동영상과 프로필을 보고 학생들이 강의를 신청하게 되니<br/>";
            $mail_content_r = $mail_content_r."성의 있게 올려 주시면 더 많은 강의 신청을 받을 수 있습니다.<br/>";
            $mail_content_r = $mail_content_r."링크가 활성화 되지 않으면 링크를 복사하여 웹사이트로 바로 이동해 주시기 바랍니다.<br/><br/>감사합니다.<br>Tutor-K팀 드림</p>
            <p style=\"margin-top:15px;\">".$login_path.$key."</p>";
        }else{
            $mail_content_r=$mail_content_r."<p style=\"margin-top:12px;\">Thanks for signing up for Tutor-K! <br/>Soon you'll be able to start learning Korean online. <br/>All you need to do to complete your registration is follow the link below:<br/><a href=\"".$login_path.$key."\" style=\"color:#007afe;text-decoration:underline;\">tutor-K, Sign up if you agree, click the message.</a><br/>
            If the above link does not work, please copy the address to your web browser and enter our website from there.<br/>
            If you did not register for Tutor-K, or believe you have received this email in error, please disregard this message.
            <br/>Sincerely,<br/>The Tutor-K Team</p><p style=\"margin-top:15px;\">환영합니다!!<br/>Tutor-K에 가입해주셔서 감사합니다.<br/><a href=\"".$profile_path."\" style=\"color:#007afe;text-decoration:underline;\">Tutor-K, 프로필 작성하기</a> <br/>아래의 링크를 클릭하셔서 가입을 완료해주시길 바랍니다.<br/><a href=\"".$login_path.$key."\" style=\"color:#007afe;text-decoration:underline;\">tutor-K, 가입신청합니다.</a><br/>링크가 활성화 되지 않으면 링크를 복사하여 웹사이트로 바로 이동해 주시기 바랍니다.<br/>".$login_path.$key."<br/>감사합니다.<br>Tutor-K팀 드림</p>";
        }

        // $myfile = fopen($_SERVER['DOCUMENT_ROOT']."/mail_form/mail_common.html", "r") or die("Unable to open file!");
        // $mail_content=fread($myfile,filesize($_SERVER['DOCUMENT_ROOT']."/mail_form/mail_common.html"));
        // fclose($myfile);
        // $mail_content=str_replace("[content]",$mail_content_r,$mail_content);
        //
        // $this->sendMail($fmail, $fnick, $to, $mail_subject, $mail_content);
        sendMail($to, $mail_subject, $mail_content_r);
    }

}
if (! function_exists ( 'sendChangepwdMail' )) {
    function sendChangepwdMail($mb_email, $passwd){
        $CI =& get_instance();
        $CI->load->model ( 'user' );

        // $ROOT_URL = "http://www.tutor-k.com/bt2_1/#/";
        $ROOT_URL = TUTORK_ROOT_URL;
        $user_link = $ROOT_URL."setting/profile";
        $to=$mb_email;

        $mail_subject="[tutorK] Password reset [{$passwd}]";
		$mail_content_r="<p>[tutorK] Password reset</p>";
		$mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\">
        The temporary password has been reset. <br/>
        The temporary passwords is  [{$passwd}].<br/>
        You can change your password on your profile edit once you login with the temporary password.<br/>
        </p>";
        $mail_content_r=$mail_content_r.$user_link."<br/><br/>";

        $mail_content_r=$mail_content_r."임시 비밀 번호는 [{$passwd}]입니다.<br/>";
        $mail_content_r=$mail_content_r."임시 비밀 번호로 로그인 하셔서 프로필 편집에서 비밀 번호를 다시 수정하시길 바랍니다. <br/>";
        $mail_content_r=$mail_content_r."<br/>";
        $mail_content_r=$mail_content_r.$user_link."<br/><br/>";

        sendMail($to, $mail_subject, $mail_content_r);
    }
}

// noti를 남긴다
if (! function_exists ( 'sendSystemMemo' )) {

    function sendSystemMemo($str_msg_id, $m_to, $teacher_name, $str_time, $str_etc, $me_lr_seq, $me_tlp_id){
        $CI =& get_instance();
        $CI->load->model ( 'user' );

        $mb_lang = $CI->user->get_userLanguage($m_to);

        $message_map;
        if ($mb_lang == "261"){
            // 한국
            $message_map = array(
                'ik-notice_2' => '선생님 강의를 예약하였습니다',
                'ik-notice_3' => '강의가 성사되었습니다.',
                'ik-notice_4' => '강의가 취소되었습니다.',
                'ik-notice_5' => '강의가 시작될 예정입니다.',
                'ik-notice_6' => '학생으로 부터 평가 대기중인 강의가 있습니다. 7일이 지나면 자동으로 강사료가 이체됩니다.',
                'ik-notice_7' => '강의신청이 있습니다. 빠른 시간 내에 강의 수락을 해 주세요.',
                'ik-notice_8' => '강사 승인이 보류 되었습니다. 보류 이유는 이메일을 확인해 주세요.',
                'ik-notice_9' => '선생님 패키지강의를 예약하였습니다.',
                'ik-notice_10' => '패키지강의가 성사되었습니다.',
                'ik-notice_11' => '패키지강의가 취소되었습니다.',
                'ik-notice_12' => '패키지신청이 있습니다. 빠른 시간 내에 패키지 수락을 해 주세요.',
                'ik-notice_13' => '패키지가 취소되었습니다.',
                'ik-additional_44' => '선생님이 강의평가를 남겼습니다. 강의 완료 페이지에 들어가셔서 확인해보세요.',
            );
        } else if ($mb_lang == "280"){
            // 러시아
            $message_map = array(
                'ik-notice_2' => 'Произведена запись на урок к преподавателю',
                'ik-notice_3' => 'Занятие завершено',
                'ik-notice_4' => 'Занятие отменено',
                'ik-notice_5' => 'Планируется начало занятия',
                'ik-notice_6' => 'Есть урок, ожидающий оценки ученика. Оплата за урок произойдет через 7 дней автоматически.',
                'ik-notice_7' => 'Поступила заявка на занятие. Пожалуйста, примите заявку в ближайшее время. ',
                'ik-notice_8' => 'Разрешение на преподавание приостановлено. Причину приостановления проверьте по электронной почте.',
                'ik-notice_9' => 'Пакет занятий преподавателя забронирован.',
                'ik-notice_10' => 'Пакет занятий завершен.',
                'ik-notice_11' => 'Пакет занятий отменен.',
                'ik-notice_12' => 'Поступила заявка на пакет занятий. Пожалуйста, примите пакет в ближайшее время. ',
                'ik-notice_13' => 'Пакет занятий отменен.',
                'ik-additional_44' => 'The teacher left the lecture evaluation. Please check the course completion page.',
            );
        } else if ($mb_lang == "282" || $mb_lang == "295" || $mb_lang == "296"){
            // 중국
            $message_map = array(
                'ik-notice_2' => '预约老师课程',
                'ik-notice_3' => '课程成功',
                'ik-notice_4' => '课程已被取消',
                'ik-notice_5' => '课程马上要开始',
                'ik-notice_6' => '有学生在进行教学评价。7日后将自动转讲师费。',
                'ik-notice_7' => '存在课程申请，请尽快接收。',
                'ik-notice_8' => '讲师承认暂时被保留。保留原因请确认邮件。',
                'ik-notice_9' => '已预约老师套餐课程。',
                'ik-notice_10' => '套餐课程已成功',
                'ik-notice_11' => '套餐课程已被取消',
                'ik-notice_12' => '存在套餐申请，请尽快接收。',
                'ik-notice_13' => '套餐已被取消',
                'ik-additional_44' => '老师已经离开了教学评价。看看进入河里完整的页面。',
            );
        } else if ($mb_lang == "256" || $mb_lang == "257"){
            // 일본
            $message_map = array(
                'ik-notice_2' => '先生の講義を予約しました。',
                'ik-notice_3' => '講義が成立しました。',
                'ik-notice_4' => '講義がキャンセルされました。',
                'ik-notice_5' => '講義が開始される予定です。',
                'ik-notice_6' => '学生からの評価待ちの講義があります。7日が経過すると、自動的に講師料が引落されますます。',
                'ik-notice_7' => '講義の申し込みがあります。早い内に講義の承認をしてください。',
                'ik-notice_8' => '講師の承認が保留されました。保留の理由は、電子メールを確認してください。',
                'ik-notice_9' => '先生のパッケージ講義を予約しました。',
                'ik-notice_10' => 'パッケージの講義が成立しました。',
                'ik-notice_11' => 'パッケージの講義がキャンセルされました。',
                'ik-notice_12' => 'パッケージの申し込みがあります。早い内にパッケージの承認をしてください。',
                'ik-notice_13' => 'パッケージがキャンセルされました。',
                'ik-additional_44' => '先生が講義の評価を残しました。講義終了ページに含まれて行き、確認してください。',
            );
        } else if ($mb_lang == "301"){
            // 베트남
            $message_map = array(
                'ik-notice_2' => 'Bạn đã đăng ký lớp học của giáo viên.',
                'ik-notice_3' => 'Lớp học đã được mở.',
                'ik-notice_4' => 'Lớp học đã bị hủy.',
                'ik-notice_5' => 'Lớp học dự định bắt đầu.',
                'ik-notice_6' => 'Có lớp học đang chờ đánh giá của học sinh. Sau 7 ngày, học phí trả cho giáo viên sẽ được chuyển tự động.',
                'ik-notice_7' => 'Có học sinh đã đăng ký lớp học. Bạn hãy chấp thuận lớp học trong thời gian gần nhất.',
                'ik-notice_8' => 'Chấp thuận của giáo viên đã bị hoãn lại. Bạn hãy kiểm tra email để biết lý do.',
                'ik-notice_9' => 'Bạn đã đăng ký lớp học trọn gói với giáo viên.',
                'ik-notice_10' => 'Lớp học trọn gói đã được thành lập.',
                'ik-notice_11' => 'Lớp học trọn gói đã bị hủy.',
                'ik-notice_12' => 'Có học sinh đăng ký trọn gói. Bạn hãy chấp thuận trọn gói trong thời gian gần nhất.',
                'ik-notice_13' => 'Trọn gói đã bị hủy.',
                'ik-additional_44' => 'Các giáo viên đã để lại đánh giá giảng dạy. Kiểm tra Go vào trang sông hoàn thành.',
            );
        } else {
            // 영어
            $message_map = array(
                'ik-notice_2' => "Your lesson request has been sent to the teacher. ",
                'ik-notice_3' => "Your lesson has been scheduled.",
                'ik-notice_4' => "Your lesson has been cancelled",
                'ik-notice_5' => "Your lesson will start soon.",
                'ik-notice_6' => "You have a completed lesson for your student to evaluate. After 7 days, your lesson fee will be automatically paid.",
                'ik-notice_7' => "You have a lesson request. Please accept it as soon as possible.",
                'ik-notice_8' => "Your teacher approval has been put on hold. Please check your email to see the reason.",
                'ik-notice_9' => "A student booked a lesson package with you.",
                'ik-notice_10' => "A lesson package has been scheduled.",
                'ik-notice_11' => "A lesson package has been cancelled.",
                'ik-notice_12' => "You've received a request for a lesson package. Please accept the package as soon as possible.",
                'ik-notice_13' => "Lesson package has been cancelled.",
                'ik-additional_44' => 'The teacher left the lecture evaluation. Please check the course completion page.',
            );
        }

        switch ($str_msg_id) {
            case "01":
            $memo_content="[{$teacher_name}]".$message_map['ik-notice_2']." [Session ID{$me_lr_seq}] ";break;
            // $memo_content="[{$teacher_name}] 선생님 강의를 예약하였습니다.[{$me_lr_seq}] ";break;
            case "02":
            $memo_content="[{$teacher_name}]".$message_map['ik-notice_3']." [{$me_lr_seq}]";break;
            // $memo_content="[{$teacher_name}] 강의가 성사되었습니다. [{$me_lr_seq}]";break;
            case "03":
            $memo_content="[{$teacher_name}]".$message_map['ik-notice_4']." [{$me_lr_seq}] ";break;
            // $memo_content="[{$teacher_name}] 강의가 취소되었습니다. [{$me_lr_seq}] ";break;
            // 기존의 어떤 batch파일이 하는것 같음..
            case "05":
            $memo_content="[{$me_lr_seq}]".$message_map['ik-notice_5'];break;
            // $memo_content="[{$me_lr_seq}] 강의가 시작될 예정입니다.";break;
            case "06":
            $memo_content=$message_map['ik-notice_6'];break;
            // $memo_content="평가 대기중인 강의가 있습니다. 10일이 지나면 자동으로 평가 완료됩니다.";break;

            case "21":
            $memo_content=$message_map['ik-notice_7']." [{$me_lr_seq}] ";break;
            // $memo_content="강의신청이 있습니다. 빠른 시간 내에 강의 수락을 해 주세요.[{$me_lr_seq}] ";break;
            case "22":
            $memo_content="[{$me_lr_seq}]".$message_map['ik-notice_4'];break;
            // $memo_content="[{$me_lr_seq}] 강의가 취소되었습니다. ";break;


            // case "23":
            // 	$memo_content="[그룹] 강의가 예약되었습니다.";break;
            // case "24":
            // 	$memo_content="[그룹] 강의에 학생이 <span>추가</span>되었습니다. ({$str_etc})";break;
            // case "25":
            // 	$memo_content="[그룹] 학생이 강의를 <span>취소</span>하였습니다. ({$str_etc})";break;
            // case "26":
            // 	$memo_content="[그룹] 강의가 최소 인원이 모이지 않아 <span>취소/span>되었습니다.";break;
            case "27":
            $memo_content=$message_map['ik-notice_5'];break;
            // $memo_content="강의가 시작될 예정입니다.";break;
            case "28":
            $memo_content="강의 평가가 등록되었습니다. 수수료를 제외한 수강료가 지급됩니다.";break;


            case "30":
            $memo_content="강사 신청이 완료 되었습니다. 곧 결과를 알려 드리겠습니다.";break;
            case "31":
            $memo_content="({$str_etc})강사로 승급 되었습니다.";break;
            case "32":
            $memo_content=$message_map['ik-notice_8'];break;
            // $memo_content="강사 승인이 보류 되었습니다. 보류 이유는 이메일을 확인해 주세요.";break;

            // 학생 pacakge 알림
            case "41":
            $memo_content="[{$teacher_name}] ".$message_map['ik-notice_9']." [{$me_tlp_id}]";break;
            // $memo_content="[{$teacher_name}] 선생님 패키지강의를 예약하였습니다. [{$me_tlp_id}]";break;
            case "42":
            $memo_content="[{$teacher_name}] ".$message_map['ik-notice_10']." [{$me_tlp_id}]";break;
            // $memo_content="[{$teacher_name}] 패키지강의가 성사되었습니다. [{$me_tlp_id}]";break;
            case "43":
            $memo_content="[{$teacher_name}]  ".$message_map['ik-notice_11']." [{$me_tlp_id}]";break;
            // $memo_content="[{$teacher_name}] 패키지강의가 취소되었습니다. [{$me_tlp_id}]";break;

            //강사 package 알림
            case "51":
            $memo_content= $message_map['ik-notice_12']." [{$me_tlp_id}] ";break;
            // $memo_content="패키지신청이 있습니다. 빠른 시간 내에 패키지 수락을 해 주세요. [{$me_tlp_id}] ";break;
            case "52":
            $memo_content=$message_map['ik-notice_13']." [{$me_tlp_id}] ";break;
            // $memo_content="패키지가 취소되었습니다. [{$me_tlp_id}] ";break;

            case "101":
            $memo_content="해당강의를 수강한 학생이 환불요청을 했습니다."." [{$me_lr_seq}] ";break;

            case "102":
            // $memo_content="선생님이 강의평가를 남겼습니다. 강의 완료 페이지에 들어가셔서 확인해보세요."." [{$me_lr_seq}] ";break;
            $memo_content=$message_map['ik-additional_44']." [{$me_lr_seq}] ";break;
        }


        // insert DB
        $CI->user->insertNoti($m_to, $str_time, $memo_content, $me_lr_seq, $me_tlp_id);

    }
}
