<?php
/*
 * 后台审核提现
 */
namespace Admin\Controller;
use Admin\Controller\AdminController;
class PendingController extends AdminController {
	// 空操作
	public function _empty() {
		header ( "HTTP/1.0 404 Not Found" );
		$this->display ( 'Public:404' );
	}
	public function index() {
		$withdraw = M ( 'Withdraw' );
		$bank = M ( 'bank' );
		$name = I('cardname');
		$names = I('keyname');
		// I('status')--分页下标生成参数
		if(I('pend')!=0 || I ( 'status' )!=0){
			$where [C("DB_PREFIX")."withdraw.status"] = I ( 'status' ) ? I ( 'status' ) : I ( 'pend' );
		}
		if(!empty($name) || !empty($names)){
			// 如果传回的是post（keyname）就用post，否则用get（cardname）
			$cardname = I ( 'keyname' )? I ( 'keyname' ):I('cardname');
			//模糊
			$where [C("DB_PREFIX")."bank.cardname"] = array('like','%'.$cardname.'%');
		}
		// 查询满足要求的总记录数
		$count = $withdraw->join ( C("DB_PREFIX")."bank ON ".C("DB_PREFIX")."withdraw.bank_id = ".C("DB_PREFIX")."bank.id" )->where ( $where )->count (); 
		// 实例化分页类 传入总记录数和每页显示的记录数
		$Page = new \Think\Page ( $count, 20 ); 
		//将分页（点击下一页）需要的条件保存住，带在分页中
		$Page->parameter = array (
				C("DB_PREFIX")."withdraw.status" => $where [C("DB_PREFIX")."withdraw.status"],
				C("DB_PREFIX")."bank.cardname" =>  $cardname 
		);
		// 分页显示输出
		$show = $Page->show (); 
		//需要的数据
		$field = C("DB_PREFIX")."withdraw.*,".C("DB_PREFIX")."bank.cardname,".C("DB_PREFIX")."bank.cardnum,".C("DB_PREFIX")."bank.bankname,b.area_name as barea_name,a.area_name as aarea_name";
		$info = $bank->field ( $field )

				->join ( C("DB_PREFIX")."withdraw ON ".C("DB_PREFIX")."withdraw.bank_id = ".C("DB_PREFIX")."bank.id" )
				->join(C("DB_PREFIX")."areas as b ON b.area_id = ".C("DB_PREFIX")."bank.address")
				->join(C("DB_PREFIX")."areas as a ON a.area_id = b.parent_id ")

				->where ( $where )
				->order ( C("DB_PREFIX")."withdraw.status desc,".C("DB_PREFIX")."withdraw.add_time desc" )
				->limit ( $Page->firstRow . ',' . $Page->listRows )
				->select ();

		$this->assign ( 'info', $info ); // 赋值数据集
		$this->assign ( 'page', $show ); // 赋值分页输出
		$this->assign ( 'inquire', $cardname );
		$this->display ();
	}

	/**
	 * 通过提现请求
	 * @param unknown $id
	 */
	public function successByid(){		
		$id = intval ( I ( 'post.id' ) );
			//判断是否$id为空
			if (empty ( $id ) ) {
				$datas['status'] = 3;
			    $datas['info'] = "参数错误";
			    $this->ajaxReturn($datas);
			}
		//查询用户可用金额等信息
		$info = $this->getMoneyByid($id);
		if($info['status']!=3){
			$datas['status'] = -1;
			$datas['info'] = "请不要重复操作";
			$this->ajaxReturn($datas);
		}
		//通过状态为2
		$data ['status'] = 2;
		$data ['check_time'] = time();
		$data ['admin_uid'] =$_SESSION['admin_userid'];
		//更新数据库
		$re = M ( 'Withdraw' )->where ( "withdraw_id = '{$id}'" )->save ( $data );	
		$num= M ( 'Withdraw' )->where ( "withdraw_id = '{$id}'" )->find ();
		M('Member')->where("member_id={$num['uid']}")->setDec('forzen_rmb',$num['all_money']);	
		if($re == false){
			$datas['status'] = 0;
			$datas['info'] = "提现操作失败";
			$this->ajaxReturn($datas);
		}
		$this->addMessage_all($info['member_id'],-2,'CNY提现成功',"恭喜您提现{$info['all_money']}成功！");
		$this->addFinance($info['member_id'],23,"提现{$info['all_money']}",$info['all_money']-$info['withdraw_fee'],2,0);
		$datas['status'] = 1;
		$datas['info'] = "提现通过，操作成功";
		$this->ajaxReturn($datas);
	}

	/**
	 * 不通过提现请求
	 * @param unknown $id
	 */
	public function falseByid(){
		$id = intval ( I ( 'post.id' ) );
			//判断是否$id为空
			if (empty ( $id ) ) {
				$this->error ( "参数错误" );
				return;
			}
		//查询用户可用金额等信息
		$info = $this->getMoneyByid($id);
		if($info['status']!=3){
			$datas['status'] = -1;
			$datas['info'] = "请不要重复操作";
			$this->ajaxReturn($datas);
		}
		//将提现的钱加回可用金额
		$money['rmb'] = floatval($info['rmb']) + floatval($info['all_money']);
		//将冻结的钱减掉
		$money['forzen_rmb'] = floatval($info['forzen_rmb']) - floatval($info['all_money']);
		
		//不通过状态为1
		$data ['status'] = 1;
		$data ['check_time'] = time();
		$data ['admin_uid'] =$_SESSION['admin_userid'];
		//更新数据库,member修改金额
		$res = M( 'Member' )->where("member_id = {$info['member_id']}")->save($money);
		//withdraw修改状态
		$re = M ( 'Withdraw' )->where ( "withdraw_id = '{$id}'" )->save ( $data );
		if($res == false){
			$datas['status'] = 0;
			$datas['info'] = "提现不通过，操作失败";
			$this->ajaxReturn($datas);
		}
		if($re == false){
			$datas['status'] = 2;
			$datas['info'] = "提现不通过，操作失败";
			$this->ajaxReturn($datas);
		}
		$this->addMessage_all($info['member_id'],-2,'CNY提现失败','很抱歉您提现失败，请重新操作或联系客服！');
		$datas['status'] = 1;
		$datas['info'] = "提现不通过，操作成功";
		$this->ajaxReturn($datas);
	}
	
	/**
	 * 获取提现金额信息
	 * @param unknown $id
	 * @return boolean|unknown $rmb 会员号，可用金额，冻结金额，手续费，提现金额
	 */
	public function getMoneyByid($id){

		$field = C("DB_PREFIX")."member.member_id,".C("DB_PREFIX")."member.rmb,".C("DB_PREFIX")."member.forzen_rmb,".C("DB_PREFIX")."withdraw.status,".C("DB_PREFIX")."withdraw.all_money,".C("DB_PREFIX")."withdraw.withdraw_fee";
		$rmb = M('Withdraw')
				->field($field)
				->join(C("DB_PREFIX")."member ON ".C("DB_PREFIX")."withdraw.uid = ".C("DB_PREFIX")."member.member_id")
				->where("withdraw_id = '{$id}'")
				->find();
		if(empty($rmb)){
			return false;
		}
		return $rmb;
	}
}
?>