<?php

class ResponseMap extends PluginBase
{

    protected $storage = 'DbStorage';
    static protected $name = 'ResponseMap';
    static protected $description = 'Display the geographical location of  responses\' IP address in the map';

    public function __construct(PluginManager $manager, $id)
    {
        parent::__construct($manager, $id);
    }

    public function init()
    {
        $this->subscribe('beforeActivate');
        $this->subscribe('beforeDeactivate');
        $this->subscribe('beforeControllerAction');
    }

    /**
     * 添加静态资源
     * @return void
     */
    public function beforeControllerAction()
    {
        $event = $this->getEvent();
        $controller = $event->get('controller');
        $action = $event->get('action');
        MyLog::log("contro ==> $controller, action ==> $action", "view");
        $plugin_name = isset($_GET["plugin"]) ? $_GET["plugin"] : "";
        if ($controller === 'admin' && $action === 'pluginhelper' && $plugin_name == $this->getName()) {
            $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__));
            App()->clientScript->registerScriptFile("$assetsUrl/js/jquery.datetimepicker.full.min.js");
            App()->clientScript->registerCssFile("$assetsUrl/css/jquery.datetimepicker.css");
            App()->clientScript->registerScriptFile("$assetsUrl/js/datatables.min.js");
            App()->clientScript->registerCssFile("$assetsUrl/css/datatables.css");
        }
    }

    public function actionHeatMap()
    {
        $_REQUEST['ip2LocUrl'] = $this->get_ip2loc_url(@$_GET['survey_id']);
        return $this->renderPartial('heatmap', array(), true);
    }

    public function actionIpList()
    {
        return $this->renderPartial('ip_list', array(), true);
    }

    public function actionIp2Loc()
    {
        return $this->renderPartial('ip_to_loc', array(), true);
    }

    public function beforeActivate()
    {
        //创建数据库
        if (!$this->api->tableExists($this, 'ip_coordinate')) {
            $this->api->createTable($this, 'ip_coordinate', array(
                'survey_id' => 'int(11)',
                'response_id' => 'int(11)',
                'response_token' => 'string',
                'ip' => 'string',
                'city' => 'string',
                'address' => 'string',
                'map_content' => 'string',
                'x_coor' => 'string',
                'y_coor' => 'string',
                'startdate' => 'datetime',
                'submitdate' => 'datetime',
                'created' => 'datetime',
                'PRIMARY KEY (`survey_id`, `response_id`)'));
        }

        //添加插件页面代码
        $rootdir = Yii::app()->getConfig('rootdir');
        $button_file = "$rootdir/application/views/admin/responses/browsemenubar_view.php";
        $file_content = file_get_contents($button_file);
        if (strpos($file_content, $this->get_plugin_html()) == false) {
            $contents = explode("<?php else: ?>", $file_content, 2);
            $part1 = $contents[0];
            $part2 = $contents[1];
            $part1 = $this->str_lreplace("</div>", "", $part1);

            $new_file_content = $part1
                . $this->get_plugin_html()          //添加html
                . "</div>" . "\n<?php else: ?>"  //添加前面去掉的html元素
                . $part2;

            file_put_contents($button_file, $new_file_content);
        }
    }

    public function beforeDeactivate()
    {
        //移除按钮
        $rootdir = Yii::app()->getConfig('rootdir');
        $button_file = "$rootdir/application/views/admin/responses/browsemenubar_view.php";
        $file_content = file_get_contents($button_file);
        $new_file_content = str_replace($this->get_plugin_html(), "", $file_content);
        file_put_contents($button_file, $new_file_content);
        return true;
    }

    function get_plugin_html()
    {
        $ret = "\n<!-- heatmap-start -->"
            . '<a class="btn btn-default" href=\'<?php echo $this->createUrl("admin/pluginhelper?sa=fullpagewrapper&plugin=' . $this->getName() . '&method=actionHeatMap&survey_id=$surveyid"); ?>\' role="button">'
            . '<span class="fa fa-area-chart text-success"></span>'
            . '&nbsp;&nbsp;热力图'
            . '</a>'
            . "<!-- heatmap-end -->\n";
        return $ret;
    }

    function get_ip2loc_url($survey_id) {
        $url = $this->api->createUrl(
            'admin/pluginhelper',
            array(
                'sa' => 'fullpagewrapper',
                'plugin' => $this->getName(),
                'method' => 'actionIp2Loc',
                'survey_id' => $survey_id
            )
        );
        return $url;
    }

    /**
     * 替换最后的匹配字符串
     * @param $search
     * @param $replace
     * @param $subject
     * @return mixed
     */
    function str_lreplace($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);

        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }
}
