<?php
/**
 * 控制器的基类
 */
 
 
namespace Smart;

use Smart\View;

abstract class Controller
{
    /**
     * 模板解析类的实例
     * @var \Smart\View
     */
    private $view = '';
    
    public function __construct()
    {
        // 初始化模板
        $this->view = new View(DEBUG);
    }
    
    /**
     * 输出json格式的内容
     * @param array $data 内容
     * @return void
     */
    public function ajaxReturn($data)
    {
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
    
    /**
     * 页面变量赋值函数
     * @param string $name 变量名
     * @param mixed $value 变量的值
     * @return void
     */
    public function assign($name, $value)
    {
        $this->view->assign($name, $value);
    }
    
    /**
     * 页面输出显示
     * @param string $view 模板名
     * @return void
     */
    public function display($view = '')
    {
        $this->view->display($view);
    }
}
