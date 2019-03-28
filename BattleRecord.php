<script src="https://code.highcharts.com/highcharts.js"></script>
<?php
    // This path should point to Composer's autoloader
    require 'vendor/autoload.php';

    function connectDB($attr){
        $mongo = new MongoDB\Client();
        $collection = $mongo->selectCollection('r6status', $attr);
        return $collection;
    }

    function id2uid($attr){
        $id2uid_db = connectDB('id2uid');
        $uid = $id2uid_db->findOne(
            ['id' => $attr],
            [
                'projection' => ['_id' => 0,'uid' => 1]
            ]
        );
        return $uid->uid;
    }

    function get_recent_userdata($attr) {
        $recent_db = connectDB('recent');
        $recent_userdata = $recent_db->findOne(
            ['id' => $attr],
            [
                'projection' => ['_id' => 0]
            ]
        );
        return $recent_userdata;
    }

    function get_old_userdata($attr) {
        $old_db = connectDB('old');
        $old_userdata = $old_db->find(
            ['id' => $attr],
            [
                'projection' => ['_id' => 0],
                'sort' => ['date' => -1]
            ]
        );
        return $old_userdata;
    }

    function get_kdr($attr) {
        $userdata = get_recent_userdata($attr[0]);
        return round($userdata->general->kdr,2);
    }
    add_shortcode('get_kdr', 'get_kdr');

    function get_wlr($attr) {
        $userdata = get_recent_userdata($attr[0]);
        return round($userdata->general->wlr,2);
    }
    add_shortcode('get_wlr', 'get_wlr');

    function get_level($attr) {
        $userdata = get_recent_userdata($attr[0]);
        return $userdata->level;
    }
    add_shortcode('get_level', 'get_level');

    function get_rank($attr) {
        $userdata = get_recent_userdata($attr[0]);
        return $userdata->rank;
    }
    add_shortcode('get_rank', 'get_rank');

    function get_icon($attr) {
        $userdata = get_recent_userdata($attr[0]);
        return $userdata->icon;
    }
    add_shortcode('get_icon', 'get_icon');

    function get_kdr_casual($attr) {
         # $userdatas = get_old_userdata($attr);
         $userdata = $old_db->find(
            ['id' => $attr],
            [
                'projection' => ['_id' => 0,'date' => 1,'casual->kdr' => 1],
                'sort' => ['date' => -1]
            ]
        );
        $kdr_array = [];

        foreach ($userdatas as $userdata) {
            $date = $userdata->date->toDateTime()->format('U.u')*1000;
            $kdr = $userdata->casual->kdr;
            $kdr_array[] = '[' . implode(',',[$date,$kdr]) . ']';
        };
        return  implode(',',$kdr_array);
    }
    add_shortcode('get_kdr_casual', 'get_kdr_casual');


    function get_kdr_ranked($attr) {
        # $userdatas = get_old_userdata($attr);
        $old_db = connectDB('old');
        $userdata = $old_db->find(
            ['id' => $attr],
            [
                'projection' => ['_id' => 0,'date' => 1,'rank->kdr' => 1],
                'sort' => ['date' => -1]
            ]
        );
        $kdr_array = [];

        foreach ($userdatas as $userdata) {
            $date = $userdata->date->toDateTime()->format('U.u')*1000;
            $kdr = $userdata->ranked->kdr;
            $kdr_array[] = '[' . implode(',',[$date,$kdr]) . ']';
        };
        return  implode(',',$kdr_array);
    }

    function get_wlr_casual($attr) {
        # $userdatas = get_old_userdata($attr);
        $userdata = $old_db->find(
            ['id' => $attr],
            [
                'projection' => ['_id' => 0,'date' => 1,'casual.wlr' => 1],
                'sort' => ['date' => -1]
            ]
        );
        $wlr_array = [];

        foreach ($userdatas as $userdata) {
            $date = $userdata->date->toDateTime()->format('U.u')*1000;
            $wlr = $userdata->casual->wlr;
            $wlr_array[] = '[' . implode(',',[$date,$wlr]) . ']';
        };
        return  implode(',',$wlr_array);
    }

    function get_wlr_ranked($attr) {
        # $userdatas = get_old_userdata($attr);
        $userdata = $old_db->find(
            ['id' => $attr],
            [
                'projection' => ['_id' => 0,'date' => 1,'rank.wlr' => 1],
                'sort' => ['date' => -1]
            ]
        );
        $wlr_array = [];

        foreach ($userdatas as $userdata) {
            $date = $userdata->date->toDateTime()->format('U.u')*1000;
            $wlr = $userdata->ranked->wlr;
            $wlr_array[] = '[' . implode(',',[$date,$wlr]) . ']';
        };
        return  implode(',',$wlr_array);
    }

    function kdr_graph($attr){
        $ID = $attr[0];
        $casual = get_kdr_casual($ID);
        $ranked = get_kdr_ranked($ID);
        $div_id = $ID . ' kdr';
        $str  = '<div id="' . $div_id . '" style="width:100%; height:400px;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'line'
                    },
                    title: {";
        $str .= "       text: '" . $ID . " K/D Charts'";
        $str .= "   },
                    yAxis: {
                        title: {
                        text: 'Kills / Deaths Ratio'
                        }
                    },
                    xAxis: {
                        type: 'datetime',
                        labels : {
                            format: '{value:%m-%d}'
                        }
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'middle'
                    },
                    series: [{
                        name: 'Casual',";
        $str .= "       data:[". $casual ."]";
        $str .= "       }, {
                        name: 'Ranked',";
        $str .= "       data:[". $ranked ."]";
        $str .= "   }]
                })
                })
                </script>";
        return $str;
    }
    add_shortcode('kdr_graph', 'kdr_graph');

    function wlr_graph($attr){
        $ID = $attr[0];
        $casual = get_wlr_casual($ID);
        $ranked = get_wlr_ranked($ID);
        $div_id = $ID . ' wlr';
        $str  = '<div id="' . $div_id . '" style="width:100%; height:400px;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'line'
                    },
                    title: {";
        $str .= "       text: '" . $ID . " W/L Charts'";
        $str .= "   },
                    yAxis: {
                        title: {
                        text: 'Wins / Losts Ratio'
                        }
                    },
                    xAxis: {
                        type: 'datetime',
                        labels : {
                            format: '{value:%m-%d}'
                        }
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'middle'
                    },
                    series: [{
                        name: 'Casual',";
        $str .= "       data:[". $casual ."]";
        $str .= "       }, {
                        name: 'Ranked',";
        $str .= "       data:[". $ranked ."]";
        $str .= "   }]
                })
                })
                </script>";
        return $str;
    }
    add_shortcode('wlr_graph', 'wlr_graph');

    function get_attacker_pick($attr){
        $userdata = get_recent_userdata($attr);
        $data = [];
        $d_data = [];
        $tmp = [];
        foreach ($userdata->operator as $operator) {
            $name = "'".$operator->name."'";
            $pick = $operator->pick;
            if ($operator->type == 'Attack') {
                $tmp[$name] = $pick;
            }
        };
        arsort($tmp);
        $count = 0;
        $other = 0;
        foreach ($tmp as $key => $value) {
            if ($count<5) {
                $data[] = '[' . implode(',',[$key,$value]) . ']';
            }else{
                $d_data[] = '[' . implode(',',[$key,$value]) . ']';
                $other = $other + $value;
            }
            $count = $count + 1;
        };
        $data[] = "{ name: 'Other', y: " . $other . ", drilldown: 'Other' }";
        return array(implode(',',$data),implode(',',$d_data));
    }

    function get_defenser_pick($attr){
        $userdata = get_recent_userdata($attr);
        $data = [];
        $d_data =[];
        $tmp = [];
        foreach ($userdata->operator as $operator) {
            $name = "'".$operator->name."'";
            $pick = $operator->pick;
            if ($operator->type == 'Defense') {
                $tmp[$name] = $pick;
            }
        };
        arsort($tmp);
        $count = 0;
        $other = 0;
        foreach ($tmp as $key => $value) {
            if ($count<5) {
                $data[] = '[' . implode(',',[$key,$value]) . ']';
            }else{
                $d_data[] = '[' . implode(',',[$key,$value]) . ']';
                $other = $other + $value;
            }
            $count = $count + 1;
        };
        $data[] = "{ name: 'Other', y: " . $other . ", drilldown: 'Other' }";
        return array(implode(',',$data),implode(',',$d_data));
    }

    function attacker_pick_graph($attr){
        $ID = $attr[0];
        list($data, $d_data) = get_attacker_pick($ID);
        $div_id = $ID . ' atpg';
        $str  = '<div id="' . $div_id . '" style="width:50%; height:400px;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'pie'
                    },
                    title: {";
        $str .= "       text: '" . $ID . " Attacker Pick Ratio'";
        $str .= "   },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                    },
                    plotOptions: {
                      pie: {
                        cursor: 'pointer',
                        dataLabels: {
                          enabled: true,
                          format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                        }
                      }
                    },
                    series: [{
                        name: 'Pick Ratio',
                        colorByPoint: true,";
        $str .= "       data: [" . $data . "]";
        $str .= "   }],
                    drilldown: {
                        series: [{
                            name: 'Pick Ratio',
                            id: 'Other',";
        $str .= "           data: [" . $d_data . "]}]";
        $str .= "
                    }
                })
                })
                 </script>";
        return $str;
    }
    add_shortcode('attacker_pick_graph', 'attacker_pick_graph');

    function defenser_pick_graph($attr){
        $ID = $attr[0];
        list($data, $d_data) = get_defenser_pick($ID);
        $div_id = $ID . ' dfpg';
        $str  = '<div id="' . $div_id . '" style="width:50%; height:400px;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'pie'
                    },
                    title: {";
        $str .= "       text: '" . $ID . " Defenser Pick Ratio'";
        $str .= "   },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                    },
                    plotOptions: {
                      pie: {
                        cursor: 'pointer',
                        dataLabels: {
                          enabled: true,
                          format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                        }
                      }
                    },
                    series: [{
                        name: 'Pick Ratio',
                        colorByPoint: true,";
        $str .= "       data:[" . $data . "]";
        $str .= "   }],
                    drilldown: {
                        series: [{
                            name: 'Pick Ratio',
                            id: 'Other',";
        $str .= "           data: [" . $d_data . "]}]";
        $str .= "
                    }
                })
                })
                 </script>";
        return $str;
    }
    add_shortcode('defenser_pick_graph', 'defenser_pick_graph');
?>
