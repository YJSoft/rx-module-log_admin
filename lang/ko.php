<?php

$lang->log_admin = '개인정보 접속기록';
$lang->log_admin_list = '접속기록 조회';
$lang->log_admin_detail = '접속기록 상세';
$lang->log_admin_verify = '무결성 검증';
$lang->log_admin_review = '점검 기록';
$lang->log_admin_config = '설정';
$lang->log_admin_about = '「개인정보의 안전성 확보조치 기준」 제8조에 따라 개인정보취급자(최고관리자, 모듈 관리자, 지정 그룹)의 접속기록을 저장합니다. 관리자 화면뿐 아니라 게시판 등에서의 조회·수정·삭제·다운로드도 기록합니다.';

$lang->log_admin_regdate = '접속일시';
$lang->log_admin_user = '접속자(식별자)';
$lang->log_admin_user_role = '구분';
$lang->log_admin_ipaddress = '접속지(IP)';
$lang->log_admin_task_type = '수행업무';
$lang->log_admin_act = '액션';
$lang->log_admin_subject = '처리한 정보주체';
$lang->log_admin_target = '처리 대상';
$lang->log_admin_result = '결과';
$lang->log_admin_chain = '기록 구분';
$lang->log_admin_request_url = '요청 URL';
$lang->log_admin_request_method = '요청 방식';
$lang->log_admin_user_agent = 'User-Agent';
$lang->log_admin_request_vars = '요청 변수 (민감정보 마스킹)';
$lang->log_admin_signature = '위·변조 확인';
$lang->log_admin_signature_valid = '정상 (서명 일치)';
$lang->log_admin_signature_invalid = '서명 불일치 — 기록이 변경되었을 수 있습니다';
$lang->log_admin_subject_member_srl = '정보주체 회원번호';
$lang->log_admin_start_date = '시작일';
$lang->log_admin_end_date = '종료일';
$lang->log_admin_all = '전체';
$lang->log_admin_export = 'CSV 내보내기';
$lang->log_admin_export_help = '내보내기도 다운로드 기록으로 남습니다.';
$lang->log_admin_no_records = '기록이 없습니다.';
$lang->log_admin_total = '총 %s건';

$lang->log_admin_roles = [
	'root' => '최고관리자',
	'manager' => '모듈 관리자',
	'handler' => '지정 그룹',
	'system' => '시스템',
];
$lang->log_admin_task_types = [
	'login' => '로그인',
	'logout' => '로그아웃',
	'list' => '목록 조회',
	'view' => '열람',
	'create' => '등록',
	'update' => '수정',
	'delete' => '삭제',
	'download' => '다운로드',
	'permission' => '권한 변경',
	'config' => '설정 변경',
	'purge' => '보관기간 경과 삭제',
	'other' => '기타',
];
$lang->log_admin_chains = [
	'access' => '접속기록',
	'permission' => '권한 부여·변경·말소',
];
$lang->log_admin_results = [
	'success' => '성공',
	'fail' => '실패',
];

$lang->log_admin_verify_help = '모든 기록의 서명과 앞뒤 연결(해시 체인)을 확인하여 기록의 수정·삭제·중간 누락·끝부분 삭제 여부를 검사합니다. (제8조제3항)';
$lang->log_admin_verify_run = '검증 실행';
$lang->log_admin_verify_checked = '검사한 기록';
$lang->log_admin_verify_range = '순번 범위';
$lang->log_admin_verify_ok = '이상 없음';
$lang->log_admin_verify_error = '이상 발견';
$lang->log_admin_verify_not_run = '아직 검증하지 않았습니다.';
$lang->log_admin_verify_head = '마지막 기록';
$lang->log_admin_verify_error_types = [
	'missing' => '기록 누락(삭제)',
	'broken_link' => '연결 불일치',
	'tampered' => '내용 변경',
	'truncated' => '끝부분 삭제',
	'head_mismatch' => '마지막 기록 불일치',
	'head_missing' => '체인 정보 없음',
	'query_error' => '조회 오류',
];

$lang->log_admin_review_help = '접속기록 및 개인정보 다운로드 상황을 정기적으로 점검하고 그 결과를 기록합니다. (제8조제2항, 점검 주기·방법·사후조치는 내부 관리계획에 정하세요.)';
$lang->log_admin_review_stats = '점검 보조 통계';
$lang->log_admin_review_by_task = '수행업무별 건수';
$lang->log_admin_review_by_user = '접속자별 건수';
$lang->log_admin_review_by_hour = '시간대별 건수';
$lang->log_admin_review_night = '야간(22시~06시) 접속';
$lang->log_admin_review_ip_count = 'IP 수';
$lang->log_admin_count = '건수';
$lang->log_admin_review_insert = '점검 결과 등록';
$lang->log_admin_period = '점검 대상 기간';
$lang->log_admin_review_method = '점검 방법';
$lang->log_admin_findings = '점검 결과';
$lang->log_admin_actions_taken = '사후 조치';
$lang->log_admin_reviewer = '점검자';
$lang->log_admin_review_list = '점검 이력';

$lang->log_admin_enabled = '기록 사용';
$lang->log_admin_include_managers = '모듈 관리자 포함';
$lang->log_admin_include_managers_help = '게시판 등의 모듈 관리자(부운영자)가 관리 권한을 가진 모듈에서 수행한 업무도 기록합니다.';
$lang->log_admin_target_groups = '개인정보취급자 그룹';
$lang->log_admin_target_groups_help = '최고관리자 외에 개인정보를 처리하는 그룹(운영진 등)을 지정하면 해당 그룹 회원의 모든 접속을 기록합니다.';
$lang->log_admin_log_scope = '기록 범위';
$lang->log_admin_log_scope_all = '모든 요청 (게시물 열람 포함, 권장)';
$lang->log_admin_log_scope_privacy = '관리자 화면 + 등록·수정·삭제·다운로드·권한·설정 + 회원정보 조회';
$lang->log_admin_log_scope_help = '게시물 열람(닉네임·IP 등 노출)이 개인정보 처리에 해당하는지는 운영 환경과 해석에 따라 달라질 수 있어, 기본값은 모두 기록입니다.';
$lang->log_admin_exclude_acts = '제외할 액션';
$lang->log_admin_exclude_acts_help = '한 줄에 하나씩 입력합니다. 개인정보 처리와 무관한 반복 요청(예: 알림 확인)만 제외하세요.';
$lang->log_admin_store_request_vars = '요청 변수 저장';
$lang->log_admin_max_value_length = '값 최대 길이';
$lang->log_admin_mask_keys = '마스킹할 변수명';
$lang->log_admin_mask_keys_help = '이름에 다음 문자열이 포함된 요청 변수는 ******** 로 저장합니다. 비밀번호 등 인증정보가 평문으로 남지 않도록 합니다.';
$lang->log_admin_access_retention = '접속기록 보관기간';
$lang->log_admin_access_retention_help = '1년 이상 보관해야 합니다. 5만명 이상의 정보주체 개인정보를 처리하거나, 고유식별정보·민감정보를 처리하거나, 기간통신사업자인 경우 2년 이상입니다. (제8조제1항)';
$lang->log_admin_permission_retention = '권한 변경 기록 보관기간';
$lang->log_admin_permission_retention_help = '권한 부여·변경·말소 내역은 최소 3년간 보관해야 합니다. (제5조제3항)';
$lang->log_admin_years = '%d년';
$lang->log_admin_forever = '자동 삭제하지 않음';
$lang->log_admin_purge_task = '보관기간 정리 예약 작업';
$lang->log_admin_purge_task_ok = '등록됨 (매일 03:30, 코어 예약 작업)';
$lang->log_admin_purge_task_missing = '등록되지 않음 — 설정을 저장하면 등록됩니다.';
$lang->log_admin_purge_task_no_queue = '비동기 큐가 꺼져 있어, 하루 한 번 첫 기록 시점에 정리합니다.';
