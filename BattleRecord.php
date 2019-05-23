<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/data.js"></script>
<script src="https://code.highcharts.com/modules/drilldown.js"></script>
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

    function get_recent_id($attr) {
        if (gettype($attr) == 'string') {
            $id = $attr;
        } else {
            $id = $attr[0];
        }
        $userdata = get_recent_userdata($id);
        return $userdata->id;
    }
    add_shortcode('get_recent_id', 'get_recent_id');

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
                'date' => 1,
            ]
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017');

        try {
            $cursor = $manager->executeQuery('r6status.old', $query)->toArray();
        } catch ( Exception $ex ) {
            var_dump($ex);
        }

        $con = count($cursor);
        $index = 0;
        $samples = 1000;
        if ($con <= $samples) {
            $step = 1;
        } else {
            $step = round($con / $samples);
        }

        for ($i = 0; $i <= $samples; $i++) {
            $index = $step * $i;
            if ($index >= $con) break;
            $userdata = $cursor[$index];
            $date = $userdata->date->toDateTime()->format('U.u')*1000;
            $casual_kdr = $userdata->casual->kdr;
            $ranked_kdr = $userdata->ranked->kdr;
            $casual_kdr_array[] = '[' . implode(',',[$date,$casual_kdr]) . ']';
            $ranked_kdr_array[] = '[' . implode(',',[$date,$ranked_kdr]) . ']';
        }
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
                'date' => 1,
            ]
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017');

        try {
            $cursor = $manager->executeQuery('r6status.old', $query)->toArray();
        } catch ( Exception $ex ) {
            var_dump($ex);
        }

        $con = count($cursor);
        $index = 0;
        $samples = 1000;
        if ($con <= $samples) {
            $step = 1;
        } else {
            $step = round($con / $samples);
        }

        for ($i = 0; $i <= $samples; $i++) {
            $index = $step * $i;
            if ($index >= $con) break;
            $userdata = $cursor[$index];
            $date = $userdata->date->toDateTime()->format('U.u')*1000;
            $casual_wlr = $userdata->casual->wlr;
            $ranked_wlr = $userdata->ranked->wlr;
            $casual_wlr_array[] = '[' . implode(',',[$date,$casual_wlr]) . ']';
            $ranked_wlr_array[] = '[' . implode(',',[$date,$ranked_wlr]) . ']';
        }
        return  [implode(',',$casual_wlr_array),implode(',',$ranked_wlr_array)];
    }

    function kdr_graph($attr){
        list($casual, $ranked) = get_kdr_both($attr[0]);
        $recent_id = get_recent_id($attr[0]);
        $div_id = $recent_id . ' kdr';
        $str  = '<div id="' . $div_id . '" style="width:100%; height:400px;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'spline'
                    },
                    title: {";
        $str .= "       text: '" . $recent_id . " K/D Charts'";
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
        $str .= "   }],
                    responsive: {
                        rules: [{
                            condition: {
                                maxWidth: 500
                            },
                            chartOptions: {
                                legend: {
                                    align: 'center',
                                    verticalAlign: 'bottom',
                                    layout: 'horizontal'
                                },
                                yAxis: {
                                    labels: {
                                        align: 'left'
                                    },
                                    title: {
                                        text: null
                                    }
                                },
                                subtitle: {
                                    text: null
                                },
                                credits: {
                                    enabled: false
                                }
                            }
                        }]
                    }
                })
                })
                </script>";
        return $str;
    }
    add_shortcode('kdr_graph', 'kdr_graph');

    function wlr_graph($attr){
        list($casual, $ranked) = get_wlr_both($attr[0]);
        $recent_id = get_recent_id($attr[0]);
        $div_id = $recent_id . ' wlr';
        $str  = '<div id="' . $div_id . '" style="width:100%; height:100%;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'spline'
                    },
                    title: {";
        $str .= "       text: '" . $recent_id . " W/L Charts'";
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
        $str .= "   }],
                    responsive: {
                        rules: [{
                            condition: {
                                maxWidth: 500
                            },
                            chartOptions: {
                                legend: {
                                    align: 'center',
                                    verticalAlign: 'bottom',
                                    layout: 'horizontal'
                                },
                                yAxis: {
                                    labels: {
                                        align: 'left'
                                    },
                                    title: {
                                        text: null
                                    }
                                },
                                subtitle: {
                                    text: null
                                },
                                credits: {
                                    enabled: false
                                }
                            }
                        }]
                    }
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
        $recent_id = $userdata->id;
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
                $data[] = '{ name:' . $key .', y:' . $value . ', drilldown: null }';
            }else{
                $d_data[] = '{ name:' . $key .', y:' . $value . '}';
                $other = $other + $value;
            }
            $count = $count + 1;
        };
        $data[] = "{ name: 'Other', y: " . $other . ", drilldown: 'Other' }";
        return array(implode(',',$data), implode(',',$d_data), $recent_id);
    }

    function get_defenser_pick($attr){
        $userdata = get_recent_userdata($attr);
        $data = [];
        $d_data =[];
        $tmp = [];
        $recent_id = $userdata->id;
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
                $data[] = '{ name:' . $key .', y:' . $value . ', drilldown: null }';
            }else{
                $d_data[] = '[' . implode(',',[$key,$value]) . ']';
                $other = $other + $value;
            }
            $count = $count + 1;
        };
        $data[] = "{ name: 'Other', y: " . $other . ", drilldown: 'Other' }";
        return array(implode(',',$data), implode(',',$d_data), $recent_id);
    }

    function attacker_pick_graph($attr){
        list($data, $d_data, $recent_id) = get_attacker_pick($attr[0]);
        $div_id = $recent_id . ' atpg';
        $str  = '<div id="' . $div_id . '" style="width:100%; height:100%;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'pie'
                    },
                    title: {";
        $str .= "       text: '" . $recent_id . " Attacker Pick Ratio'";
        $str .= "   },
                    tooltip: {
                        pointFormat: '{series.name}: {point.percentage:.1f}%'
                    },
                    plotOptions: {
                        series: {
                        dataLabels: {
                          enabled: true,
                          format: '{point.name}: {point.percentage:.1f} %'
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
                            id: 'Other',";
        $str .= "           data: [" . $d_data . "]}]";
        $str .= "   },
                    responsive: {
                        rules: [{
                            condition: {
                                maxWidth: 500
                            },
                            chartOptions: {
                                title: {
                                    text: 'Attacker Pick Ratio'
                                },
                                plotOptions: {
                                    series: {
                                    dataLabels: {
                                      enabled: false
                                    },
                                    showInLegend: true
                                  }
                                },
                                credits: {
                                    enabled: false
                                }
                            }
                        }]
                    }
                })
                })
                </script>";
        return $str;
    }
    add_shortcode('attacker_pick_graph', 'attacker_pick_graph');

    function defenser_pick_graph($attr){
        list($data, $d_data, $recent_id) = get_defenser_pick($attr[0]);
        $div_id = $recent_id . ' dfpg';
        $str  = '<div id="' . $div_id . '" style="width:100%; height:100%;"></div>';
        $str .= '<script>jQuery(function($) {';
        $str .= "var myChart = Highcharts.chart('" . $div_id . "', {";
        $str .="    chart: {
                        type: 'pie'
                    },
                    title: {";
        $str .= "       text: '" . $recent_id . " Defenser Pick Ratio'";
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
        $str .= "   },
                    responsive: {
                        rules: [{
                            condition: {
                                maxWidth: 500
                            },
                            chartOptions: {
                                title: {
                                    text: 'Defenser Pick Ratio'
                                },
                                plotOptions: {
                                    series: {
                                    dataLabels: {
                                      enabled: false
                                    },
                                    showInLegend: true
                                  }
                                },
                                credits: {
                                    enabled: false
                                }
                            }
                        }]
                    }
                })
                })
                </script>";
        return $str;
    }
    add_shortcode('defenser_pick_graph', 'defenser_pick_graph');
?>
