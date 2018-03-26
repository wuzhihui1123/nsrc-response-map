<?php header("Content-Type: text/html;charset=utf-8"); ?>
<?php
function get_ip_coor($survey_id, $startdate = null, $enddate = null)
{

    $sql = " select ip, x_coor, y_coor from " . SurveyIpCoordinate::model()->tableSchema->rawName . " where survey_id = '{$survey_id}' ";
    if (!empty($startdate)) {
        $sql .= " and  startdate >= '$startdate' ";
    }
    if (!empty($enddate)) {
        $sql .= " and  startdate <= '$enddate' ";
    }
    $datas = Yii::app()->db->createCommand($sql)->query(array())->readAll();
    return $datas;
}

$survey_id = @$_GET['survey_id'];
$startdate = @$_GET['startdate'];
$enddate = @$_GET['enddate'];
$survey = Survey::model()->findByPk($survey_id);
if ($survey_id == null) {
    echo "<h3>请选择一个调查问卷</h3>";
    exit;
}
$points = array();
$datas = get_ip_coor($survey_id, $startdate, $enddate);
foreach ($datas as $data) {
    array_push($points, array('lng' => $data['x_coor'], 'lat' => $data['y_coor'], 'count' => 10));
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
    <title>IP热力图</title>
    <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=PUjAesOERDan4gtfh8KKlnhf0iMwlo4f"></script>
    <script type="text/javascript" src="http://api.map.baidu.com/library/Heatmap/2.0/src/Heatmap_min.js"></script>
    <style>
        .page-header {
            margin-top: 15px;
        }

        @media (min-width: 1600px) {
            .container {
                width: 1570px;
            }
        }

        @media (min-width: 1800px) {
            .container {
                width: 1700px;
            }
        }

        #sync_button {
            position: absolute;
            right: 0px;
            bottom: 0px;
            color: #328637;
            cursor: pointer;
            padding: 5px 10px;
            font-size: 14px;
            margin: 0;
            border-width: 0;
            background-color: white;
        }

        #sync_button:hover {
            background-color: #a2c374b5;
        }
    </style>
</head>
<body style="overflow: scroll;">
<div class="container">
    <div class="page-header" style="position: relative;">
        <h3 style="margin: 0;"><?= SurveyLanguageSetting::model()->findByAttributes(array("surveyls_survey_id" => $survey_id))->surveyls_title ?>
            <small>-- 反馈IP地址位置热力图</small>
            <button id="sync_button" title="同步IP数据" onclick="fn_ip_addr_sync();"><span class="glyphicon glyphicon-refresh"></span></button>
        </h3>
    </div>
    <?php
    $a = SurveyDynamic::model($survey_id)->tableSchema->columns;
    if (!array_key_exists("ipaddr", $a)) {
        ?>
        <div class="alert alert-warning" role="alert">
            <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
            当前问卷没有开启IP地址记录功能，如需要使用热力图，请先开启问卷的IP地址记录功能。
        </div>
    <?php } ?>
    <div style="border: 3px solid #aaa;">
        <div id="container" style="height: 750px;"></div>
    </div>
</div>
</body>
</html>
<script type="text/javascript">
    var map = new BMap.Map("container");          // 创建地图实例

    var point = new BMap.Point(105, 36);
    map.centerAndZoom(point, 5.5);             // 初始化地图，设置中心点坐标和地图级别
    map.enableScrollWheelZoom(); // 允许滚轮缩放

    var points =<?= json_encode($points)?>;

    if (!isSupportCanvas()) {
        alert('热力图目前只支持有canvas支持的浏览器,您所使用的浏览器不能使用热力图功能~')
    }
    //详细的参数,可以查看heatmap.js的文档 https://github.com/pa7/heatmap.js/blob/master/README.md
    //参数说明如下:
    /* visible 热力图是否显示,默认为true
     * opacity 热力的透明度,1-100
     * radius 势力图的每个点的半径大小
     * gradient  {JSON} 热力图的渐变区间 . gradient如下所示
     *	{
     .2:'rgb(0, 255, 255)',
     .5:'rgb(0, 110, 255)',
     .8:'rgb(100, 0, 255)'
     }
     其中 key 表示插值的位置, 0~1.
     value 为颜色值.
     */
    heatmapOverlay = new BMapLib.HeatmapOverlay({"radius": 20, 'visible': true});
    map.addOverlay(heatmapOverlay);
    heatmapOverlay.setDataSet({data: points, max: 100});
    //    //是否显示热力图
    //    function openHeatmap(){
    //        heatmapOverlay.show();
    //    }
    //    function closeHeatmap(){
    //        heatmapOverlay.hide();
    //    }
    //    closeHeatmap();
    function setGradient() {
        /*格式如下所示:
         {
         0:'rgb(102, 255, 0)',
         .5:'rgb(255, 170, 0)',
         1:'rgb(255, 0, 0)'
         }*/
        var gradient = {};
        var colors = document.querySelectorAll("input[type='color']");
        colors = [].slice.call(colors, 0);
        colors.forEach(function (ele) {
            gradient[ele.getAttribute("data-key")] = ele.value;
        });
        heatmapOverlay.setOptions({"gradient": gradient});
    }
    //判断浏览区是否支持canvas
    function isSupportCanvas() {
        var elem = document.createElement('canvas');
        return !!(elem.getContext && elem.getContext('2d'));
    }

    $.datetimepicker.setLocale('zh');
    jQuery('.my-datetime').datetimepicker({
        format: 'Y-m-d H:i',
        defaultTime: "00:00"
    });

    function fn_ip_addr_sync() {
        var $sync_btn = $("#sync_button");
        var $sync_message = $("<span id='sync_message'>&nbsp;&nbsp;数据同步中...</span>");
        $.ajax({
            url: "<?=@$_REQUEST['ip2LocUrl'] ?>",
            beforeSend: function (xhr) {
                $sync_btn.append($sync_message).prop('disabled', true);
            },
            complete: function (xhr, ts) {
                $sync_btn.prop('disabled', false).find("span#sync_message").remove();
            }
        });
    }
</script>