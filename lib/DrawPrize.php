<?php
/**
 * Created by PhpStorm.
 * User: zhu.jingtao
 * Date: 2019/1/25
 * Time: 16:18
 */

namespace app\common\lib;


use app\common\enum\PrizeType;
use app\common\enum\RedisKeyPre;
use app\common\RedisClient;
use app\common\Time;
use phpDocumentor\Reflection\Types\Self_;

class DrawPrize
{
    protected static $prizeArr = array();
    protected static $unPrizeArr = array();
    protected static $userId = 0;
    protected static $activityId = 0;
    function __construct($userId,  $prizeArr, $activityId, $unPrizeArr)
    {
        self::$prizeArr = $prizeArr;
        self::$userId = $userId;
        self::$activityId = $activityId;
        self::$unPrizeArr = $unPrizeArr;

    }

    public function draw(){
        $today = Time::getNowDate();

        //获取奖品池
        $redis = RedisClient::getInstance();
        $redisTodayPirze = sprintf(RedisKeyPre::$TODAY_PRIZE_ID, self::$activityId, $today);
        $todayPrize =  $redis->hGetAll($redisTodayPirze);
        $prizeId = self::drawPrizeId($todayPrize);
        $unPrizeId = array_column(self::$unPrizeArr,'prize_id');
        if(in_array($prizeId, $unPrizeId)){
            return $prizeId;
        }

        $drawRes = self::drwaUserPrize($today, $prizeId);
        if(!$drawRes){
            $redis->hDel($redisTodayPirze, $prizeId);
            $unPrizeId = array_column(self::$unPrizeArr,'prize_id');
            $arrKey = array_rand($unPrizeId);
            $prizeId = intval($unPrizeId[$arrKey]);
        }

        return $prizeId;

    }

    static function drawPrizeId($prize){
        $maxNum = array_sum($prize);
        $start =1;
        foreach ($prize as &$num){
            $start += $num;
            $num = $start;
        }

        $lucnNum = mt_rand(1, $maxNum);
        foreach ($prize as $prizeId => $value) {
            if($lucnNum<=$value){
                return $prizeId;
            }
        }

    }

    static function drwaUserPrize($date,  $prizeId){
        $cacheKey = sprintf(RedisKeyPre::$ACTIVITY_PRIZE, self::$activityId, $date, $prizeId);
        $redis = RedisClient::getInstance();
        return $redis->lPop($cacheKey);
    }
}