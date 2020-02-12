<?php
error_reporting(E_ALL); //php.ini  display_errors 开启
// error_reporting(0);
ini_set("display_errors","On");
date_default_timezone_set('PRC');
header("Content-Type:text/html;chartset=uft-8");
if(isset($_POST['mailinMsg'])){
	$mailinMsg_org = $_POST['mailinMsg'];
	// file_put_contents(date('Y-m-d-H-i-s').'.mail', $_POST['mailinMsg']);	
	try {
		$expire = 3600;
		$now = date('Y-m-d-H-i-s');
		$mailinMsg = json_decode($mailinMsg_org, true);
		print_r($mailinMsg);
		$key = "mailin:{$mailinMsg['to'][0]['address']}";
        $redis = new Redis();
        $redis->connect('redis', 6379);

        $redis->lpush($key, $mailinMsg_org);
        $redis->ltrim($key, 0, 5);
        $redis->expire($key, $expire);

    } catch (Exception $e) {
        echo $e->getMessage();
    }
    // echo 'ok';
}elseif(isset($_GET['email'])){
	try {
		$email = trim($_GET['email']);
		if(!$email){
			throw new Exception("暂无数据", 1);
		}

		$key = "mailin:$email";
		$redis = new Redis();
	    $redis->connect('redis', 6379);
	    $is_exists = $redis->exists($key);
	    $arr_data = $arr_echo = [];
		if(!$is_exists){
			throw new Exception("暂无数据", 1);
		}
		$items = $redis->lrange($key, 0 ,5);
		// print_r($items);
		foreach ($items as $k=>$v) {
			$v = json_decode($v, true);

			$arr_data[] = [
				'From'=> "{$v['from'][0]['name']} ({$v['from'][0]['address']})",
				'To'=> "{$v['to'][0]['name']} ({$v['to'][0]['address']})",
				'时间'=> date('Y-m-d H:i:s',strtotime($v['date'])),
				'主题'=> $v['subject'],
				'内容'=> $v['html'],
			];
		}
		foreach ($arr_data as $k=>$v) {
			$k++;
			$arr_echo[] = <<<STR
			<div>
				<div class="from" style="font-weight:bold;">{$k}、From: {$v['From']}</div>
				<!--div class="to" style="">To: {$v['To']}</div-->
				<div class="date" style="color:#ccc;">时间: {$v['时间']}</div>
				<div class="subject" style="">主题: {$v['主题']}</div>
				<div class="content" style="">内容: <br />{$v['内容']}</div>
			</div>

STR;
		}
		echo join('<hr /><br />', $arr_echo);

	} catch (Exception $e) {
        echo $e->getMessage();
    }

}else{
	echo '参数错误';
}
