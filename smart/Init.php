<?php
/**
 * 初始化函数
 *
 */
 
namespace Smart;

define('DEFAULT_NAMESPACE', 'App');                 // 应用默认的命名空间
define('BASEPATH', realpath(getcwd().'/../') . DIRECTORY_SEPARATOR);     // 框架的基础路径
define('SMART_PATH', BASEPATH . 'smart/');          // 框架的路径
define('STORAGE_PATH', BASEPATH . 'storage/');      // 临时文件日志等的保存目录
define('VENDOR_PATH', BASEPATH . 'vendor/');        // 第三方包的保存目录
defined('DEBUG') or define('DEBUG', true);          // 是否开启调试模式
defined('APP_PATH') or define('APP_PATH', BASEPATH . 'app/');    // 应用的默认路径

class Smart
{
    private static $prefixLengthsPsr4 = array();
    private static $prefixDirsPsr4 = array();
    private static $prefixesPsr0 = array();
    private static $classMap = array();
    
    /**
     * 入口函数
     * @return void
     */
    public static function start()
    {
        if (true === DEBUG) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }
        
        // 导入框架的函数
        include SMART_PATH . 'Common/functions.php';
        
        // 导入用户的函数
        if (is_file(APP_PATH .'Common/function.php')) {
            include APP_PATH .'Common/function.php';
        }
        
        // 导入系统配置文件
        C(include BASEPATH .'config/config.php');
        // 导入用户配置文件
        C(include BASEPATH . 'config/app.php');
        
        // 加载第三方类库
        self::autoloadVendor();
        
        // 注册自动加载函数
        spl_autoload_register('Smart\Smart::autoload'); 
        
        // 设置自定义的错误处理类和自定义的异常处理类
        set_exception_handler("Smart\Smart::appException");
        set_error_handler("Smart\Smart::appError");
        
        self::dispatch();
        self::exec();
    }
    
    /**
     * 自动加载第三方类库
     * @return void
     */
    private static function autoloadVendor(){
        if (is_file(VENDOR_PATH . 'composer/autoload_namespaces.php')) {
            $map = include VENDOR_PATH . 'composer/autoload_namespaces.php';
            foreach ($map as $namespace => $path) {
                self::$prefixesPsr0[$namespace[0]][$namespace] = (array) $path;
            }
        }
        
        if (is_file(VENDOR_PATH . 'composer/autoload_psr4.php')) {
            $map = include VENDOR_PATH . 'composer/autoload_psr4.php';
            foreach ($map as $namespace => $path) {
                $length = strlen($namespace);
                if ('\\' !== $namespace[$length - 1]) {
                    throw new \InvalidArgumentException('A non-empty PSR-4 prefix must end with a namespace separator.');
                }
                self::$prefixLengthsPsr4[$namespace[0]][$namespace] = $length;
                self::$prefixDirsPsr4[$namespace] = (array) $path;
            }
        }
        
        if (is_file(VENDOR_PATH . 'composer/autoload_classmap.php')) {
            $classMap = include VENDOR_PATH . 'composer/autoload_classmap.php';
            if ($classMap) {
                self::addClassMap($classMap);
            }
        }
        
        if (is_file(VENDOR_PATH . 'composer/autoload_files.php')){
            $includeFiles = include VENDOR_PATH . 'composer/autoload_files.php';
            foreach ($includeFiles as $fileIdentifier => $file) {
                self::composerInclude($fileIdentifier, $file);
            }
        }
    }
    
    /**
     * 添加类的地图
     * @return void
     */
    public static function addClassMap(array $classMap)
    {
        if (self::$classMap) {
            self::$classMap = array_merge(self::$classMap, $classMap);
        } else {
            self::$classMap = $classMap;
        }
    }
    
    /**
     * 自动导入文件
     * @return void
     */
    private static function composerInclude($fileIdentifier, $file)
    {
        if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
            include $file;
            $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
        }
    }
    
    /**
     * 路由解析
     * @return void
     */
    private static function dispatch()
    {
        if (!empty($_SERVER['PATH_INFO'])) {
            $path = trim($_SERVER['PATH_INFO'], '/');
            define('__EXT__', strtolower(pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION)));	//获取后缀名
            $paths = explode('/', $path, 3);
            $paths[0] = isset($paths[0]) ? $paths[0] : '';
            $paths[1] = isset($paths[1]) ? $paths[1] : '';
            $mod = preg_replace('/\.'.__EXT__.'$/i', '',$paths[0]);
            $_GET['m'] = empty($mod) ? 'index' : $mod;	// 设置模块
            $act = preg_replace('/\.'.__EXT__.'$/i', '',$paths[1]);
            $_GET['c'] = empty($act) ? 'index' : $act;
            $_SERVER['PATH_INFO'] = isset($paths[2]) ? $paths[2] : '';
            // 解析剩余的参数
            $var = array();
            preg_replace_callback('/(\w+)\/([^\/]+)/', function($match) use(&$var){$var[$match[1]]=strip_tags($match[2]);}, $_SERVER['PATH_INFO']);
            $_GET = array_merge($var, $_GET);
        }
    }
    
    /**
     * 类的自动加载函数
     * @param string $class
     * @return false
     */
    private static function autoload($class)
    {
        if ($file = self::findFile($class)) {
            include $file;
            return true;
        }
    }
    
    /**
     * 根据类名自动查找文件
     * 参考vendor的ClassLoader文件
     * @return string | bool
     */
    private static function findFile($class)
    {
        $name           =   strstr($class, '\\', true);
        if ('Smart' == $name) {
            $path = SMART_PATH;
            $class = ltrim(strstr($class, '\\'), '\\');
            $file       =   $path . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            if (is_file($file)) {
                return $file;
            }
        }
        
        if (DEFAULT_NAMESPACE == $name) {
            $path = APP_PATH ;
            $class = ltrim(strstr($class, '\\'), '\\');
            $file       =   $path . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            if (is_file($file)) {
                return $file;
            }
        }
        // 引入第三方库，后缀名不包含class
        $path = SMART_PATH . '/Lib/';
        $file       =   $path . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        if (is_file($file)) {
            return $file;
        }
        
        // 遍历查找classmap
        if (isset(self::$classMap[$class])) {
            return self::$classMap[$class];
        }
        
        // 遍历psr-4
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';

        $first = $class[0];
        if (isset(self::$prefixLengthsPsr4[$first])) {
            foreach (self::$prefixLengthsPsr4[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach (self::$prefixDirsPsr4[$prefix] as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }
        
        // PSR-0 lookup
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1) . strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR);
        } else {
            // PEAR-like class name
            $logicalPathPsr0 = strtr($class, '_', DIRECTORY_SEPARATOR) . '.php';
        }

        if (isset(self::$prefixesPsr0[$first])) {
            foreach (self::$prefixesPsr0[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                            return $file;
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * 自定义的异常处理类
     * @return void
     */
    private static function appException($exception)
    {
        $msg = $exception->getMessage();
        exit;
    }
    
    /**
     * 自定义的错误处理类
     * @return void
     */
    private static function appError()
    {
        
        exit;
    }
    
    /**
     * 执行控制器的方法
     * 控制器首字母自动转换为大写
     * @return void | false
     */
    private static function exec()
    {
        // 模块默认首字母大写
        $mod = isset($_GET['m']) ? $_GET['m'] : C('DEFAULT_CONTROLLER');
        $act = isset($_GET['c']) ? $_GET['c'] : C('DEFAULT_ACTION');
        if (!preg_match('/[a-zA-Z]{1,20}/', $act)) {
            exit('act error!');
        }
        if (!preg_match('/[a-zA-Z]{1,20}/', $mod)) {
            exit('mod error!');
        }
        
        // 定义控制器和操作名
        define('CONTROLLER_NAME', ucfirst($mod));
        define('ACTION_NAME', $act);
        
        $class = '\\' .DEFAULT_NAMESPACE . '\\Controller\\' . ucfirst($mod) . 'Controller';
        if (class_exists($class)) {
            $controller = new $class();
            $controller->$act();
        } else {
            return false;
        }
    }
}

\Smart\Smart::start();