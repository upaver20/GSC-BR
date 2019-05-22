<script src="https://code.highcharts.com/highcharts.js"></script>
<?php
    /**  This path should point to Composer's autoloader **/
    require 'vendor/autoload.php';

    function connectDB($attr){
        $mongo = new MongoDB\Client();
        $collection = $mongo->selectCollection('r6status', $attr);
        return $collection;
    }

    function id2uid($attr){
        $filter = ['id' => $attr];
        $options = [
            'projection' =>
            [
                    '_id' => 0,
                    'uid' => 1
            ],
            'sort' => [
                'date' => -1,
            ],
            'limit' => 1
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017');

        try {
            $cursor = $manager->executeQuery('r6status.id2uid', $query);
        } catch ( Exception $ex ) {
            var_dump($ex);
        }
        $doc = $cursor->toArray();
        return $doc[0]->uid;
    };
    add_shortcode('id2uid_new', 'id2uid_new');

    function get_recent_userdata($attr) {
        $uid = id2uid($attr);

        $filter = ['uid' => $uid];
        $options = [
            'projection' =>
            [
                    '_id' => 0
            ],
            'sort' => [
                'date' => -1,
            ],
            'limit' => 1
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017');

        try {
            $cursor = $manager->executeQuery('r6status.recent', $query);
        } catch ( Exception $ex ) {
            var_dump($ex);
        }
        $doc = $cursor->toArray();

        return $doc[0];
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

    function get_kdr_both($attr) {
        $uid = id2uid($attr);

        $filter = ['uid' => $uid];
        $options = [
            'projection' =>
            [
                    '_id' => 0,
                    'date' => 1,
                    'casual.kdr' => 1,
                    'ranked.kdr' => 1,
            ],
            'sort' => [
                'date' => -1,
            ]
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017');

        try {
            $cursor = $manager->executeQuery('r6status.old', $query);
        } catch ( Exception $ex ) {
            var_dump($ex);
        }

        foreach ($cursor as $userdata) {
            $date = $userdata->date->toDateTime()->format('U.u')*1000;
            $casual_kdr = $userdata->casual->kdr;
            $ranked_kdr = $userdata->ranked->kdr;
            $casual_kdr_array[] = '[' . implode(',',[$date,$casual_kdr]) . ']';
            $ranked_kdr_array[] = '[' . implode(',',[$date,$ranked_kdr]) . ']';
            # var_dump($date);
         };
            return  [implode(',',$casual_kdr_array),implode(',',$ranked_kdr_array)];
    }

    function get_wlr_both($attr) {
        $uid = id2uid($attr);

        $filter = ['uid' => $uid];
        $options = [
            'projection' =>
            [
                    '_id' => 0,
                    'date' => 1,
                    'casual.wlr' => 1,
                    'ranked.wlr' => 1,
            ],
            'sort' => [
                'date' => -1,
            ]
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017');

        try {
            $cursor = $manager->executeQuery('r6status.old', $query);
        } catch ( Exception $ex ) {
            var_dump($ex);
        }

        foreach ($cursor as $userdata) {
            $date = $userdata->date->toDateTime()->format('U.u')*1000;
            $casual_wlr = $userdata->casual->wlr;
            $ranked_wlr = $userdata->ranked->wlr;
            $casual_wlr_array[] = '[' . implode(',',[$date,$casual_wlr]) . ']';
            $ranked_wlr_array[] = '[' . implode(',',[$date,$ranked_wlr]) . ']';
            # var_dump($date);
         };
            return  [implode(',',$casual_wlr_array),implode(',',$ranked_wlr_array)];
    }

    function kdr_graph($attr){
        $ID = $attr[0];
        list($casual, $ranked) = get_kdr_both($ID);
        $div_id = $ID . ' kdr';
        $str  = '<div id="' . $div_id . '" style="width:100%; height:400px;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'spline'
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
                    plotOptions: {
                        spline: {
                            lineWidth: 4,
                            states: {
                                hover: {
                                    lineWidth: 5
                                }
                            },
                            marker: {
                                enabled: false
                            }
                        }
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
        list($casual, $ranked) = get_wlr_both($ID);
        $div_id = $ID . ' wlr';
        $str  = '<div id="' . $div_id . '" style="width:100%; height:400px;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'spline'
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
                    plotOptions: {
                        spline: {
                            lineWidth: 4,
                            states: {
                                hover: {
                                    lineWidth: 5
                                }
                            },
                            marker: {
                                enabled: false
                            }
                        }
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
