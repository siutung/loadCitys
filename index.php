<?php

/*
 * 本程序由益鸽网络出品,未经授权请不要在网络传播.
 * Copyright (c) 2015~2017 <http://buffge.com> All rights reserved.
 * Author: buff <admin@buffge.com>
 * Created on : 2017-9-5, 20:23:01
 * Author     : buff
 */

require './LoadCitys.php';
use loadCitys\LoadCitys;

$config = [
    'appcode' => 'yourappcode',
    'host'    => '127.0.0.1',
    'name'    => 'mysql_username',
    'pwd'     => 'mysql_pwd',
    'dbname'  => 'mysql_dbname'
];
$now = date('Y\年n\月j\日H:i:s');
$n_time = microtime(1);
echo "开始时间 --- {$now}\n";
$load = new LoadCitys($config);
$load->start();
$e_time = microtime(1);
$end = date('Y\年n\月j\日H:i:s');
echo "结束时间 --- {$end}\n";
$all_time = $e_time - $n_time;
echo "共用时 {$all_time}秒\n";
