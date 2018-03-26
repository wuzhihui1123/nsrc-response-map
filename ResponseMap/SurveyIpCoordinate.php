<?php


class SurveyIpCoordinate extends LSActiveRecord
{
    public $lang = 'auto';

    public static function model($class = __CLASS__)
    {
        return parent::model($class);
    }

    public function tableName()
    {
        $plugin_name = ResponseMap::getName();
        return '{{' . $plugin_name . '_ip_coordinate}}';
    }

    public function primaryKey()
    {
        return array('survey_id', 'response_id');
    }

    public static function insertRecord($survey_id, $response_id, $response_token, $ip,
                                        $city = null, $address = null, $map_content = null,
                                        $x_coor = null, $y_coor = null, $startdate = null, $submitdate = null)
    {
        $o = new self;
        $o->survey_id = $survey_id;
        $o->response_id = $response_id;
        $o->response_token = $response_token;
        $o->ip = $ip;
        $o->city = $city;
        $o->address = $address;
        $o->map_content = $map_content;
        $o->x_coor = $x_coor;
        $o->y_coor = $y_coor;
        $o->startdate = $startdate;
        $o->submitdate = $submitdate;
        return $o->save();
    }
}
