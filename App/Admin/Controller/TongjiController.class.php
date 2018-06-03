<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
use Think\Page;

class TongjiController extends AdminController {
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	
     //人工充值审核页面
    public function tuiguang(){
    	$where['pid']=array('neq','');
       	$list=$this->getMemberTuijianPaiming($where);
		//获取本月第一天/最后一天的时间戳
       	$year = date("Y");
       	$month = date("m");
       	$allday = date("t");
       	$strat_time = strtotime($year."-".$month."-1");
       	$end_time = strtotime($year."-".$month."-".$allday);
       	
       	$monthWhere['reg_time']=array('between',$strat_time.','.$end_time);
    	$monthWhere['pid']=array('neq','');
       	$toMonth=$this->getMemberTuijianPaiming($monthWhere);
       	
       	//获取本周第一天/最后一天的时间戳
       	$year = date("Y");
       	$month = date("m");
       	$day = date('w');
       	$nowMonthDay = date("t");
       	$firstday = date('d') - $day;
       	if(substr($firstday,0,1) == "-"){
       		$firstMonth = $month - 1;
       		$lastMonthDay = date("t",$firstMonth);
       		$firstday = $lastMonthDay - substr($firstday,1);
       		$time_1 = strtotime($year."-".$firstMonth."-".$firstday);
       	}else{
       		$time_1 = strtotime($year."-".$month."-".$firstday);
       	}
       	
       	$lastday = date('d') + (7 - $day);
       	if($lastday > $nowMonthDay){
       		$lastday = $lastday - $nowMonthDay;
       		$lastMonth = $month + 1;
       		$time_2 = strtotime($year."-".$lastMonth."-".$lastday);
       	}else{
       		$time_2 = strtotime($year."-".$month."-".$lastday);
       	}
       	$weekWhere['reg_time']=array('between',$time_1.','.$time_2);
    	$weekWhere['pid']=array('neq','');
       	$week=$this->getMemberTuijianPaiming($weekWhere);
       	//dump($list);
       	//dump(toMonth);
       	//dump($week);
     	$this->display();
     }
    public function shuju(){
    	$list=M('Currency')->select();
    	foreach ($list as $k=>$v){
    		$alltj[$k]['currency_name']=$v['currency_name'];
    		$alltj[$k]['currency_id']=$v['currency_id'];
    		$alltj[$k]['paytj']=$this->getCurrencyPayTongjiByCurrencyId($v['currency_id']);
    		$alltj[$k]['tibitj']=$this->getCurrencyTibiTongjiByCurrencyId($v['currency_id']);
//     		$alltj[$k]['tibitj']=$this->getCurrencyTibiTongjiByCurrencyId($v['currency_id']);
    	}
    	$order[]=$this->getCurrencyOrderTongji();
    	$trade[]=$this->getCurrencyTradeTongji();
     	$this->display();
     }
    public function xiangxi(){
    	$list=M('Currency')->select();
    	foreach ($list as $k=>$v){
    		$alltj[$k]['paytj']=$this->getCurrencyNumByCurrencyId($v['currency_id']);
    		$alltj[$k]['membertj']=$this->getCurrencyMemberNumByCurrencyId($v['currency_id']);
    		$alltj[$k]['cztxtj']=$this->getCurrencyTibiByCurrencyId($v['currency_id']);;
    		$alltj[$k]['Trade']=$this->getCurrencyTradeByCurrencyId($v['currency_id']);
    	}
    	//dump($alltj);
     	$this->display();
     }
    public function yue(){
    	$currency=I('currency_id')?I('currency_id'):'26';
    	$list=$this->getMemberCurrencyPmByCurrencyId($currency);
     	$this->display();
     }
   private function getMemberTuijianPaiming($where){
	   	$list=M('Member')
	   	->field('count(member_id) as num,pid')
	   	->order('num desc')
	   	->where($where)
	   	->group('pid')
	   	->select();
	   	return $list;
   }
   private function getMemberCurrencyPmByCurrencyId($currency,$where){
   		$where['currency_id']=$currency;
	   	$list=M('Currency_user')
	   	->field('')
	   	->order('num desc')
	   	->where($where)
	   	->select();
	   	return $list;
   }
   private function getCurrencyTradeByCurrencyId($currency,$where=''){
    	$where['currency_id']=$currency;
	   	$list=M('Trade')
	   	->field('sum(money) as jiaoyimoney')
	   	->group('currency_id')
	   	->where($where)
	   	->find();
	   	return $list;
   }
   private function getCurrencyPayTongjiByCurrencyId($currency,$where=''){
    	$where['currency_id']=$currency;
	   	$list=M('Pay')
	   	->field('count(pay_id) as allnum,sum(money) as allmoney')
	   	->group('currency_id')
	   	->where($where)
	   	->find();
	   	return $list;
   }
   private function getCurrencyTibiByCurrencyId($currency,$where=''){
    	$where['currency_id']=$currency;
    	$where['status']=1;
	   	$list[]=M('Tibi')
	   	->field('sum(num) as txmoney')
	   	->group('currency_id')
	   	->where($where)
	   	->find();
	   	$where['status']=3;
	   	$list[]=M('Tibi')
	   	->field('sum(num) as czmoney')
	   	->group('currency_id')
	   	->where($where)
	   	->find();
	   	return $list;
   }
   private function getCurrencyNumByCurrencyId($currency,$where=''){
    	$where['currency_id']=$currency;
	   	$list=M('Currency_user')
	   	->field('sum(num) as allnum,sum(forzen_num) as allcoldnum')
	   	->group('currency_id')
	   	->where($where)
	   	->find();
	   	return $list;
   }
   private function getCurrencyMemberNumByCurrencyId($currency,$where=''){
    	$where['currency_id']=$currency;
	   	$list=M('Currency_user')
	   	->field('sum(num) as allnum,sum(forzen_num) as allcoldnum')
	   	->group('currency_id')
	   	->where($where)
	   	->find();
	   	return $list;
   }
   private function getCurrencyTibiTongjiByCurrencyId($currency,$where=''){
    	$where['currency_id']=$currency;
	   	$list=M('tibi')
	   	->field('count(id),sum(num)')
	   	->group('currency_id')
	   	->where($where)
	   	->find();
	   	return $list;
   }
   private function getCurrencyOrderTongji($where=''){
	   	$list=M('orders')
	   	->field('count(orders_id),sum(num*price) as jiaoyiprice,sum(num) as jiaoyinum,currency_trade_id,currency_id')
	   	->group('currency_id')
	   	->where($where)
	   	->select();
	   	foreach ($list as $k=>$v){
	   		$list['allmoney']+=$v['jiaoyiprice'];
	   	}
	   	return $list;
   }
   private function getCurrencyTradeTongji($where=''){
   		$where['type']='sell';
	   	$list=M('Trade')
	   	->field('count(trade_id),sum(num*price) as jiaoyiprice,sum(num) as jiaoyinum,currency_trade_id,currency_id')
	   	->group('currency_id')
	   	->where($where)
	   	->select();
	   	foreach ($list as $k=>$v){
	   		$list['allmoney']+=$v['jiaoyiprice'];
	   	}
	   	return $list;
   }
}