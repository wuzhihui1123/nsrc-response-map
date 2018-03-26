<?php header("Content-Type: text/html;charset=utf-8"); ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>IP地理位置数据收集</title>
</head>
<body>

<?php
function get_ip_coor($ip)
{
    $url = "http://api.map.baidu.com/location/ip?ak=PUjAesOERDan4gtfh8KKlnhf0iMwlo4f&ip=$ip&coor=bd09ll";
    $ret = file_get_contents($url);
    return json_decode($ret);
}

function get_max_response_id_from_ip_coor($survey_id)
{

    $sql = " select max(response_id) as rid from " . SurveyIpCoordinate::model()->tableSchema->rawName . " where survey_id = " . (int)$survey_id;
    $r = Yii::app()->db->createCommand($sql)->query(array())->read();
    return $r['rid'] == null ? 0 : (int)$r['rid'];
}

$survey_id = @$_GET['survey_id'];
$survey = Survey::model()->findByPk($survey_id);
if ($survey_id == null) {
    echo "<h3>请选择一个调查问卷</h3>";
    exit;
}
$survey_table_name = SurveyDynamic::model($survey_id)->tableSchema->rawName;
$max_response_id = get_max_response_id_from_ip_coor($survey_id);
//echo "select id, ipaddr,token,startdate, submitdate from $survey_table_name where id > $max_response_id order by id asc";
//exit;
$responses = $dataReader = Yii::app()->db->createCommand("select id, ipaddr,token, submitdate from $survey_table_name where id > $max_response_id order by id asc")->query(array())->readAll();
$index = 0;
foreach ($responses as $item) {
    $response_id = $item['id'];
    $response_token = $item['token'];
    $ipaddr = $item['ipaddr'];
    $token = $item['token'];
//    $startdate = $item['startdate'];
    $startdate = null;
    $submitdate = $item['submitdate'];
    $ip_coor = @get_ip_coor($ipaddr);
    if ($ip_coor != null) {
        $address = @$ip_coor->address;
        $map_content = @$ip_coor->content;
        $x_coor = @$map_content->point->x;
        $y_coor = @$map_content->point->y;
        $city = @$map_content->address_detail->city;
        $o = SurveyIpCoordinate::model();
        SurveyIpCoordinate::insertRecord($survey_id, $response_id, $response_token, $ipaddr, $city, $address, json_encode($map_content),
            $x_coor, $y_coor, $startdate, $submitdate);
        if ($index++ / 500 == 0) sleep(2);  //控制对百度api调用的频率
    }
}

echo "<h3>IP地理位置信息同步成功，共更新 {$index} 个IP的地址</h3>";
?>

</body>
</html>