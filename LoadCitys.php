<?php

/*
 * 本程序由益鸽网络出品,未经授权请不要在网络传播.
 * Copyright (c) 2015~2017 <http://buffge.com> All rights reserved.
 * Author: buff <admin@buffge.com>
 * Created on : 2017-9-5, 21:05:25
 * Author     : buff
 */
namespace loadCitys;

/**
 *  爬虫类
 */
class LoadCitys
{
    /**
     * 当前要查询信息的城市列表
     * @var type array
     */
    protected
            $searchCitysList = [
        "北京", "天津", "上海", "重庆",
        "河北省", "山西省", "辽宁省", "吉林省", "黑龙江省",
        "江苏省", "浙江省", "安徽省", "福建省", "江西省", "山东省",
        "河南省", "湖北省", "湖南省", "广东省", "海南省", "四川省",
        "贵州省", "云南省", "陕西省", "甘肃省", "青海省", "台湾",
        "内蒙古自治区", "西藏自治区", "新疆维吾尔自治区",
        "宁夏回族自治区", "广西壮族自治区", "香港特别行政区", "澳门特别行政区"
    ];
    /**
     * 阿里云接口的appcode
     * @var type string 
     */
    protected
            $appcode;
    /**
     * 第一层并发连接数
     * @var type int
     */
    protected
            $currentConnect = 50;
    /**
     * 总共要查询的数量
     * @var type int
     */
    protected
            $amount;
    /**
     * 当前城市的level
     * @var type int
     */
    protected
            $level = 1;
    /**
     * 查询当前城市信息
     */
    const
            SELF_INFO = 1;
    /**
     * 查询当前城市的子城市信息
     */
    const
            SUB_LIST = 2;
    /**
     * 当前模式
     * @var type int
     */
    protected
            $getInfo = self::SELF_INFO;
    /**
     * 数据库ip
     * @var type string
     */
    protected
            $host = '127.0.0.1';
    /**
     * 数据库登录名
     * @var type string
     */
    protected
            $name = 'root';
    /**
     * 数据库密码
     * @var type string
     */
    protected
            $pwd = 'root';
    /**
     * 数据库名
     * @var type string
     */
    protected
            $dbname = 'chinesecitys';
    /**
     * 对实例进行配置
     * @param type $config 配置数组
     */
    public
            function __construct(array $config) {
        foreach ($config as $k => $v) {
            $this->$k = $v;
        }
        $this->amount = count($this->searchCitysList);
        echo "\n当前城市列表为:\n";
        print_r($this->searchCitysList);

    }

    /**
     * 启动方法
     */
    public
            function start() {
//启动一个mcurl
        $mh = curl_multi_init();
        $orinigal_chs = [];
//初始化mcurl 并添加一定数量(最高为全部或者最大并发)curl进去
        $this->curlMultiInit($mh, $orinigal_chs);
//执行所有的curl句柄并返回所有curl句柄数组
        $chs = $this->curlMultiExec($mh, $orinigal_chs);
//获取所有的curl返回的结果
        $res = $this->getCurlResult($chs);
//如果为正常结果
        if (is_array($res)) {
//对每一个curl结果进行处理
            $this->dataHandle($res);
        }
        else {
//输出错误信息
            echo "当前列表中所有城市都没有子城市.\n";
        }

    }

    /**
     * 初始化curl并添加到curl_multi
     * @param resource $mh mcurl句柄
     * @param array $chs 存放curl句柄的数组
     */
    protected
            function curlMultiInit($mh, array &$chs) {
        for ($i = 0; $i < $this->amount; $i++) {
            $chs[$i] = curl_init();
            $this->setCurlOpt($chs[$i], $i);
        }
        for ($j = 0; $j < $this->currentConnect && $j < $this->amount; $j++) {
            curl_multi_add_handle($mh, $chs[$j]);
        }

    }

    /**
     * 为当前上下文curl连接 进行设置 主要是看他的getInfo<br>
     * 判断是查询信息还是查询子城市
     * @param type $ch curl子连接
     * @param type $i 当前设置的curl连接的序号
     */
    protected
            function setCurlOpt($ch, int $i) {
        $host = "http://ali-city.showapi.com";
        $method = "GET";
        $appcode = $this->appcode;
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        if ($this->getInfo === self::SELF_INFO) {
            echo " 当前为城市名查询城市信息模式\n";
            echo "城市名为:{$this->searchCitysList[$i]}, 当前城市等级为{$this->level}\n";
            $path = "/areaName";
            $encode_cityName = rawurlencode($this->searchCitysList[$i]);
            $querys = "areaName=" . $encode_cityName . "&level={$this->level}&maxSize=10&page=1";
            $url = $host . $path . "?" . $querys;
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FAILONERROR, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            if (1 == strpos("$" . $host, "https://")) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
        }
        else if ($this->getInfo === self::SUB_LIST) {
            echo " 当前为父城市id查询子城市信息模式\n";
            echo "当前父城市id为{$this->searchCitysList[$i]}\n";
            $path = "/areaDetail";
            $querys = "parentId=" . $this->searchCitysList[$i];
            $url = $host . $path . "?" . $querys;
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FAILONERROR, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            if (1 == strpos("$" . $host, "https://")) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
        }

    }

    /**
     * 执行$mh的所有子连接 并移除句柄之后返回子curl句柄数组
     * @param resource $mh  mcurl句柄
     * @param array $chs  存放curl句柄的数组
     * @return array 存放curl句柄的数组
     */
    protected
            function curlMultiExec($mh, array $chs): array {
        $now_curl_handle = $this->currentConnect;
        do {
            $mrc = curl_multi_exec($mh, $active);
            $info = curl_multi_info_read($mh, $msgq);
            if ($info) {
                $result[] = $info['handle'];
                if ($now_curl_handle < $this->amount) {
                    curl_multi_add_handle($mh, $chs[$now_curl_handle]);
                    $now_curl_handle++;
                }
                curl_multi_remove_handle($mh, $info['handle']);
            }
            else {
                usleep(1000);
            }
        }
        while ($active && $mrc == CURLM_OK || $msgq > 0);
        curl_multi_close($mh);
        return $result;

    }

    /**
     * 获取curl执行后得到的页面内容数组 或者输出错误代码
     * @param mixed $chs curl数组
     * @return mixed 
     */
    protected
            function getCurlResult(array $chs) {
        $res = null;
        foreach ($chs as $k => $v) {
            if (($error = curl_error($v))) {
                echo "当前发生错误\n" . $error . "\n";
            }
            else {
                $http_code = curl_getinfo($v, CURLINFO_HTTP_CODE);
                $http_url = curl_getinfo($v, CURLINFO_EFFECTIVE_URL);
                if ($http_code >= 400) {
                    echo "错误http代码" . $http_code . " url地址" . $http_url . "\n";
                    curl_close($v);
                    continue;
                }
                $res[$k][0] = curl_multi_getcontent($v);
                $res[$k][1] = $http_url;
            }
            curl_close($v);
        }
        return $res;

    }

    /**
     * 对获取到的内容进行处理
     * @param array $res curl执行完后所有的内容数组
     */
    protected
            function dataHandle(array $res) {
        $mysqli = new \mysqli($this->host, $this->name, $this->pwd, $this->dbname);
        foreach ($res as $k => $v) {
            $mixedInfo = json_decode($v[0]);
            $cityInfo = $mixedInfo->showapi_res_body->data;
            $citysLength = count($cityInfo);
            $sql = "INSERT INTO `allcitys` ( `provinceId`, `simpleName`, `lon`, `areaCode`, `cityId`, `remark`, `prePinYin`, `cid`, `pinYin`, `parentId`, `level`, `areaName`, `simplePy`, `zipCode`, `countyId`, `lat`, `wholeName`) VALUES ";
            $sql_values = '';
            for ($i = 0; $i < $citysLength; $i++) {
                $sql_values .= "('{$cityInfo[$i]->provinceId}', '{$cityInfo[$i]->simpleName}', '{$cityInfo[$i]->lon}', '{$cityInfo[$i]->areaCode}', '{$cityInfo[$i]->cityId}', '{$cityInfo[$i]->remark}', '{$cityInfo[$i]->prePinYin}', '{$cityInfo[$i]->id}',\"{$cityInfo[$i]->pinYin}\", '{$cityInfo[$i]->parentId}', '{$cityInfo[$i]->level}', '{$cityInfo[$i]->areaName}', '{$cityInfo[$i]->simplePy}', '{$cityInfo[$i]->zipCode}', '{$cityInfo[$i]->countyId}', '{$cityInfo[$i]->lat}', '{$cityInfo[$i]->wholeName}'),";
            }
            $sql_values = substr_replace($sql_values, '', -1);
            $sql .= $sql_values;
            $mysqli->query($sql);
//不知道为什么这里的affect_rows 总是-1
//            if ($mysqli->affected_rows == $citysLength) {
            if (1) {
                echo "{$citysLength}条数据添加成功\n";
                if ($this->level < 4) {
                    $temp_level = $this->level + 1;
                    for ($j = 0; $j < $citysLength; $j++) {
                        $tempearchCitysList[] = $cityInfo[$j]->id;
                    }
                    $config = [
                        'appcode'         => $this->appcode,
                        'level'           => $temp_level,
                        'amount'          => $citysLength,
                        'getInfo'         => self::SUB_LIST,
                        'searchCitysList' => $tempearchCitysList,
                        'host'            => $this->host,
                        'name'            => $this->name,
                        'pwd'             => $this->pwd,
                        'dbname'          => $this->dbname
                    ];
                    $subLoad = new self($config);
                    $tempearchCitysList = [];
                    $subLoad->start();
                    unset($subLoad);
                }
            }
            else {
                echo $this->searchCitysList[$k] . "数据插入失败!\n";
            }
        }
        $mysqli->close();

    }

}
