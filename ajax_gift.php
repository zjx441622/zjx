<?php
include('header_ajax.php');

$openid = $user['openid'];


$today = date("Ymd");
$time = time();
// 获取今天凌晨的时间戳
$day = strtotime(date('Y-m-d',time()));

$sql_info = "SELECT last_login FROM userinfo WHERE wecha_id='{$openid}'";
$res_info = mysql_fetch_assoc(mysql_query($sql_info));
if( !$res_info ){
	echo json_encode(array('code'=>1,'status'=>3,'message'=>'请清理微信缓存'));
	exit;
}

$sql_order = "SELECT count(1) as num FROM userorder WHERE wecha_id='{$openid}'";
$res_oder = mysql_fetch_assoc(mysql_query($sql_order));
if( $res_oder['num']>0 ){
	echo json_encode(array('code'=>3,'status'=>2,'message'=>'已经中过奖'));
	exit;
}

//抽奖
$giftid = getRand($gifts,$multiple);


//判断是否活动未开始或结束

if($site_config['STARTDATE']>$today OR $site_config['ENDDATE'] < $today){
	$gametime = 0;
	$giftid = 0;
}else{
	$gametime = 1;
}

useGold($user['openid'],$giftid,$giftid,0);
LottoryStat($giftid);





echo json_encode(array('code'=>0,'giftid'=>$giftid));

function getRand($json_gifts,$multiple) {
	if( $multiple==0 || $multiple % 10 != 0) {
		$multiple = 1;
	}
    $result = 0;
    $data=array();
    //概率数组的总概率精度
    $proSum = 100*$multiple;
    //概率数组循环 
    $randNum = mt_rand(1, $proSum)/(1*$multiple);
    for($i=1;$i<=4;$i++){
        $total_num=totalnumber($i);
        $hour_num=number($i);
        if($randNum<=$json_gifts[$i]['chance']&&$total_num<$json_gifts[$i]['num']&&$hour_num<$json_gifts[$i]['score']){
            $data[]=$i;
        }
		// echo $randNum.'----'.$json_gifts[$i]['chance'].'</br>';
    }
    // var_dump($data);exit;
    if(count($data)!=0){
        $g =rand(0,count($data)-1);
        $result=$data[$g];
    }
    return $result;
}

function totalnumber($giftid){
	
    $sql="SELECT count(*) as total from `userorder` where gift_id=$giftid";
	// $sql = "SELECT sum(M.usertm) from 
	// (
		// SELECT sum(DISTINCT `lotterynum{$giftid}`) as usertm,dateline,hours 
		// FROM `datecount` 
		// GROUP BY hours,`dateline`
	// ) M";
    $query=mysql_query($sql);
    $res = mysql_fetch_assoc($query);
    return $res['total'];
	 //return $res['sum(M.usertm)'];
}

function number($giftid){
    $date = date('Ymd',time());
    $hour = date('H',time());
	
    $sql = "select count(1) as num from userorder where date='{$date}' and hours={$hour} AND gift_id='{$giftid}'";
    $res = mysql_query($sql);
    $num = mysql_fetch_assoc($res);
return intval($num['num']);		
	
    // $sql = "select lotterynum{$giftid} from datecount where dateline='{$date}' and hours={$hour}";
    // $res = mysql_query($sql);
    // $num = mysql_fetch_assoc($res);
    // return intval($num['lotterynum'.$giftid]);
	
}

function LottoryStat($giftid){

	$hours = date('H');
	$dateline = date("Ymd");
	$check_sql = "select * from datecount where `dateline`='{$dateline}' and `hours`='{$hours}'";
	$check = mysql_fetch_assoc(mysql_query($check_sql));
	if($check){
		$query = "UPDATE `datecount` SET lotterynum".$giftid."=lotterynum".$giftid."+1 WHERE `dateline`='{$dateline}' and `hours`='{$hours}'";
	}else{
		$query = "insert into datecount (lotterynum".$giftid.",dateline,hours) values (1,{$dateline},{$hours}) ";
	}
	
	$res = mysql_query($query);
	return $res;
}

function useGold($openid,$score,$giftid,$exchange){

    global $conn;
    $now = time();
    $starttime = strtotime("today");
    $endtime = $starttime+"86400";

    if (empty($openid)){
        return false;
    }

	//mysql_query("BEGIN");
    $hours = date('H');
    $now = time();
	$order_sn = 'D'.strtoupper(dechex(date('m'))).date('d').substr(time(),-5).substr(microtime(),2,5).sprintf('d',rand(0,99));//订单号
    $query_order = "Insert into `userorder` (orderid,gift_id,dateline,status,wecha_id,exchange,date,hours) values('{$order_sn}',$giftid,'{$now}',1,'{$openid}',{$exchange},'".date('Ymd')."','{$hours}')";
    
 

    if($giftid>0){
        $res2 = mysql_query($query_order);
    }

	$query = "Insert into `usergold` (wecha_id,type,score,orderid,createtime,dateline,hours) values('{$openid}','1',{$score},'{$order_sn}','{$now}','".date('Ymd')."','{$hours}')";
    $res1 = mysql_query($query);


}
?>