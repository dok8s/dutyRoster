<?php
/**
 * 简单的mysql封装
 *
 */
 
namespace Smart;

class DB
{
    
    /**
     * 连接
     * @var resource
     */
    private $pdo        = null;
    
    /**
     * 数据库连接的配置文件
     * @var array
     */
    private $config     = array(
        'hostname'      => '',          //服务器地址
        'username'      => '',          //用户名
        'password'      => '',          //密码
        'hostport'      => '3306',      //端口
        'database'      => '',          //数据库
        'prefix'        => ''           //表前缀
    );
    
    /**
     * 初始化
     */
    public function __construct($config)
    {
        $config = array_merge($this->config, $config);
        try {
            $this->pdo = new \PDO(
                'mysql:host='.$config['hostname'].';dbname='.$config['database'], 
                $config['username'], 
                $config['password']
            );
            $this->pdo->query("SET NAMES utf8"); 
        } catch (PDOException $e) {
            exit('connection error!'. $e->getMessage());
        }
    }
    
    /**
     * 插入更新数据
     * @param string $sql 需要执行的sql语句
     * @return int
     */
    public function execute($sql)
    {
        $count = $this->pdo->exec($sql);
        if( $count && false !== strpos( $sql , 'insert' ) ){
            return $this->pdo->lastInsertId();
        }
        return $count;
    }
    
    public function select($sql)
    {
        $result = $this->pdo->query($sql);
        $result->setFetchMode(\PDO::FETCH_ASSOC);
        $data = $result->fetchAll();
        return $data;
    }
}