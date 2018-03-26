<?php
header("Content-Type: text/html;charset=utf-8");
$survey_id = @$_GET['survey_id'];
$survey = Survey::model()->findByPk($survey_id);
if ($survey_id == null) {
    echo "<h3>请选择一个调查问卷</h3>";
    exit;
}

$startdate = @$_GET['startdate'];
$enddate = @$_GET['enddate'];
$date_type = @$_GET['date_type'];
$date_type = empty($date_type) ? 'startdate': $date_type;
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>IP数据列表</title>

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
    </style>
</head>
<body style="overflow: scroll;">
<div class="container">
    <div class="page-header">
        <h3><?= SurveyLanguageSetting::model()->findByAttributes(array("surveyls_survey_id" => $survey_id))->surveyls_title ?>
            <small>-- IP数据列表</small>
        </h3>
    </div>
    <div style="border: 3px solid #aaa;">
        <table id="ip-table" class="table table-striped">
            <thead>
            <tr>
                <th width="60px;">#</th>
                <th>IP</th>
                <th>Token</th>
                <th>问卷反馈Id</th>
                <th>问卷填写时间</th>
                <th>问卷完成时间</th>
                <th>所属城市</th>
                <th>经度 , 纬度</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $sql = " select ip,response_id,response_token,city, x_coor, y_coor,startdate, submitdate from " . SurveyIpCoordinate::model()->tableSchema->rawName . " where survey_id = '{$survey_id}' ";

            if (!empty($startdate)) {
                $sql .= " and  $date_type >= '$startdate' ";
            }
            if (!empty($enddate)) {
                $sql .= " and  $date_type <= '$enddate' ";
            }
            $datas = Yii::app()->db->createCommand($sql)->query(array())->readAll();
            foreach ($datas as $index => $data) {
                ?>
                <tr>
                    <td></td>
                    <td><?= $data['ip'] ?></td>
                    <td><?= $data['response_token'] ?></td>
                    <td><a href="<?= App()->createUrl("/admin/responses/sa/view/surveyid/{$survey_id}/id/{$data['response_id']}") ?>"><?= $data['response_id'] ?></a></td>
                    <td><?= $data['startdate'] ?></td>
                    <td><?= empty($data['submitdate']) ? '-' : $data['submitdate'] ?></td>
                    <td><?= empty($data['city']) ? '-' : $data['city'] ?></td>
                    <td><?= empty($data['x_coor']) ? '-' : ($data['x_coor'] . " , " . $data['y_coor']) ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    var table = $('#ip-table').DataTable({
        "columnDefs": [
            {"orderable": false, "targets": 0},
            {"sType": "chinese-string", "targets": 4}
        ],
        "language": {
            "emptyTable": "还没有任何数据"
        },
        "autoWidth": false,
        "paging": false,
        "searching": false,
        "info": false,
        "order": [[3, "desc"]],
        "initComplete": function (settings, json) {
            fn_build_row_num();
        }
    });
    $('#ip-table').on('order.dt', function () {
        fn_build_row_num();
    });

    function fn_build_row_num() {
        $("#ip-table tbody tr").each(function (i) {
            $(this).find("td").first().not(".dataTables_empty").html("<span class='badge'>" + (i + 1) + "</span>");
        });
    }
    $.datetimepicker.setLocale('zh');
    jQuery('.my-datetime').datetimepicker({
        format: 'Y-m-d H:i',
        defaultTime: "00:00"
    });
</script>
</body>
</html>