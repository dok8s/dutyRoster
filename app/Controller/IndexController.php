<?php
namespace App\Controller;

use Smart\Controller;
use Smart\DB;

class IndexController extends Controller
{
    public function index()
    {
        // 这里获取所有的用户列表
        $db = new DB(C('db'));
        $users = $db->select("select * from `user` order by id asc");
        
        $this->assign('users', $users);
        $this->display();
    }
    
    // 获取事件
    public function getEvents()
    {
        $start = $_GET['start'];
        $end = $_GET['end'];
        // 对数据进行初始化
        $db = new DB(C('db') );
        $re = $db->select("select a.*, b.name as title from `events` as a, `user` as b where a.uid = b.id and a.start > '$start' and a.end < '$end'");
        echo json_encode( $re );
        exit;
    }
    
    // 添加事件
    public function addEvent()
    {
        if($_POST){
            $uid = (int)$_POST['uid'];
            $start = preg_replace('#[^-0-9: ]#', '', $_POST['start']);
            $end = preg_replace('#[^-0-9: ]#', '', $_POST['end']);
                
            $db = new DB(C('db'));
            $re = $db->execute("insert into `events` (`uid`,`start`,`end`,`createtime`) values ($uid, '$start', '$end', now() )");
            if(0 !== $re){
                echo json_encode(array(
                        'success' => true,
                        'msg' => '添加成功',
                        'id' => $re
                    ));
                exit;
            } else {
                echo json_encode(array(
                        'success' => false,
                        'msg' => '添加失败'
                    ));
                exit;
            }
        }
    }
    
    // 删除事件
    public function delEvent()
    {
        if($_POST){
            $id = (int)$_POST['id'];
            $db = new DB(C('db'));
            $re = $db->execute("delete from `events` where id = $id");
            if(0 !== $re){
                echo json_encode(array(
                        'success' => true,
                        'msg' => '删除成功'
                    ));
                exit;
            } else {
                echo json_encode(array(
                        'success' => false,
                        'msg' => '删除失败'
                    ));
                exit;
            }
        }
    }
    
    // 更新事件
    public function updateEvent()
    {
        if($_POST) {
            $id = (int)$_POST['id'];
            $uid = (int)$_POST['uid'];
            $start = preg_replace('#[^-0-9: ]#', '', $_POST['start']);
            $end = preg_replace('#[^-0-9: ]#', '', $_POST['end']);
            $db = new DB(C('db'));
            $re = $db->execute("update `events` set `uid`=$uid, `start` = '$start', `end` = '$end' where id = $id");
            if(0 !== $re){
                echo json_encode(array(
                        'success' => true,
                        'msg' => '编辑成功'
                    ));
                exit;
            } else {
                echo json_encode(array(
                        'success' => false,
                        'msg' => '编辑失败'
                    ));
                exit;
            }
        }
    }
    
    // 添加用户
    public function addUser()
    {
        if($_POST){
            $name = $_POST['name'];
            $db = new DB(C('db'));
            $re = $db->execute("insert into `user` (`name`) values ('$name')");
            if(0 !== $re){
                echo json_encode(array(
                        'success' => true,
                        'msg' => '添加成功'
                    ));
                exit;
            } else {
                echo json_encode(array(
                        'success' => false,
                        'msg' => '添加失败'
                    ));
                exit;
            }
        }
    }
    
    // 删除用户
    public function delUser()
    {
        if($_POST){
            $id = (int)$_POST['id'];
            if(0 !== $id){
                $db = new DB(C('db'));
                $re = $db->execute("delete from `user` where id = $id");
                if(0 !== $re){
                    echo json_encode(array(
                            'success' => true,
                            'msg' => $re
                        ));
                    exit;
                } else {
                    echo json_encode(array(
                        'success' => false,
                        'msg' => $re
                    ));
                    exit;
                }
            } else {
                echo json_encode(array(
                    'success' => false,
                    'msg' => 'ID错误'
                ));
                exit;
            }
        }
    }
}
