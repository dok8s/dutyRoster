<?php
/**
 *
 * 基本规则
 *------------------------------------------------
 * 支持变量替换, 支持变量点语法
 * 
 * {$name}
 * {$name.val}
 *------------------------------------------------
 * 支持if判断以及嵌套
 * 
 * <if condition="$val eq 1"> 
 *
 * <elseif condition="$val2 eq 2"/> 
 *
 * <else /> 
 *
 * </if>
 *------------------------------------------------
 * 支持volist循环以及嵌套
 *
 * <volist id="val" name="list">
 * {$val}
 * </volist>
 *------------------------------------------------
 * 支持include导入编译
 *
 * <include file="header"/>
 * <include file="public:header">
 *
 */

namespace Smart;

class View 
{
    /**
     * 保存变量的key-value
     * @var array
     */
    private $tVar = array();
    
    /**
     * 模板文件的路径
     * @var string
     */
    private $viewFolder = "";
    
    /**
     * 缓存文件的保存路径
     * @var string
     */
    private $tmpView = '';
    /**
     * 调试模式
     * @var bool
     */
    private $debug = true;
    
    /**
     * 用于替换模板的变量
     * @var array
     */
    private $replace =  array(
        
    );
    
    /**
     * 模板标签的属性列表
     * @var array
     */
    protected $tags = array(
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'php'       =>  array(),
        'volist'    =>  array('attr' => 'name,id,key', 'level' => 3),
        'if'        =>  array('attr' => 'condition', 'level' => 3),
        'elseif'    =>  array('attr' => 'condition', 'close' => 1),
        'else'      =>  array('attr' => '', 'close' => 1),
    );
    
    /**
     * 替换运算符的列表
     * @var array
     */
    protected $comparison = array(
        ' nheq ' =>  ' !== ',
        ' heq '  =>  ' === ',
        ' neq '  =>  ' != ',
        ' eq '   =>  ' == ',
        ' egt '  =>  ' >= ',
        ' gt '   =>  ' > ',
        ' elt '  =>  ' <= ',
        ' lt '   =>  ' < '
    );
    
    public function __construct($debug = false)
    {
        $this->viewFolder = APP_PATH . 'View/';
        $this->tmpView = STORAGE_PATH . 'app/';
        $this->debug = $debug;
    }
    
    /**
     * 设置变量
     * @param string $name 变量的名称
     * @param mixed $value 变量的值
     * @return void
     */
    public function assign($name, $value)
    {
        $this->tVar[$name] = $value;
    }
    
    /**
     * 展示, 获取页面的文件并解析生成临时的文件
     * @param string $tmpFile 模板文件名
     */
    public function display($tmpFile = '')
    {
        $content = $this->fetch($tmpFile);
        $this->render($content);
    }
    
    /**
     * 根据文件名定位目标，获取页面的内容并替换
     * @param string $tmpFile 模板文件名
     * @return string
     */
    private function fetch($tmpFile)
    {
        $tmpFile = $this->parseTemplate($tmpFile);
        
        // 页面缓存
        ob_start();
        ob_implicit_flush(0);
        // 此次解析模板文件并替换，然后输出
        // 视图解析标签
        $params = array(
            'var' => $this->tVar, 
            'file' => $tmpFile
        );
        $this->view_parse($params);

        $content = ob_get_clean();
        return $content;
    }
    
    /**
     * 输出页面的内容
     * @parm string $content 页面的内容
     */
    private function render($content)
    {
        // 网页字符编码
        header('Content-Type:text/html; charset=utf-8');
        header('X-Powered-By:xiaokakeji');
        // 输出模板文件
        echo $content;
    }
    
    /**
     * 模板名称解析
     * 
     */
    private function parseTemplate($tmpFile)
    {
        if ('' == $tmpFile ) {
            //如果为空,则根据控制器和操作名来获取
            $file = $this->viewFolder . CONTROLLER_NAME . '/' .ACTION_NAME . '.html';
        } elseif (strpos($tmpFile, '/')) {
            // 解析反斜杠
            $file = $this->viewFolder . $tmpFile . '.html';
        } elseif (strpos($tmpFile, ':')) {
            $_tmpFile = str_replace(':', '/', $tmpFile);
            $file = $this->viewFolder . $_tmpFile . '.html';
        } else {
            $file = $this->viewFolder . $tmpFile . '.html';
        }
        if (!is_file($file)) {
            exit('Template Not Found!');
        }
        return $file;
    }
    
    /**
     * 解析标签并输出
     * @parm array $_data 需要解析的参数，页面变量数组和文件路径
     * @return void
     */
    private function view_parse($_data)
    {
        //检查缓存是否存在或者有效，如果有效则返回,如果无效则编译模板
        if ($this->checkCache($_data['file']) && false === $this->debug) {
            if (!is_null($_data['var'])){
                extract($_data['var'], EXTR_OVERWRITE);
            }
            $tmplCacheFile = $this->tmpView . md5($_data['file']) . '.php';
            include $tmplCacheFile;
        } else {
            $tmplContent =  file_get_contents($_data['file']);
            $tmplCacheFile = $this->tmpView . md5($_data['file']) . '.php';
            $tmplContent = $this->compiler($tmplContent);
            $this->put($tmplCacheFile, $tmplContent);
            $this->load($tmplCacheFile, $_data['var']);
        }
    }
    
    /**
     * 检查缓存文件是否存在
     * @param string $file 缓存文件的路径
     * @return bool
     */
    private function checkCache($file)
    {
        $tmplCacheFile = $this->tmpView . md5($file) .'.php';
        if (!is_file($tmplCacheFile)) {
            return false;
        } elseif (filemtime($file) > filemtime($tmplCacheFile)) {
            return false;
        }
        return true;
    }
    
    /**
     * 输出文件内容
     * @param string $filename 文件名
     * @param string $content 文件内容
     * @return bool
     */
    private function put($filename, $content)
    {
        $dir         =  dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (false === file_put_contents($filename, $content)) {
            return false;
        }
        return true;
    }
    
    /**
     * 导入文件并分解变量数组
     * @param string $_filename 文件名
     * @param array $vars 页面变量数组
     * @return void
     */
    private function load($_filename, $vars=null)
    {
        if (!is_null($vars)) {
            extract($vars, EXTR_OVERWRITE);
        }
        include $_filename;
    }
    
    /**
     * 编译模板
     * @param string $content 页面的内容
     * @return string
     */
    private function compiler($content)
    {
        // 此处解析编译
        if (empty($content)) {
            // 内容为空不解析
            return '';
        }
        // 检查include语法
        $content = $this->parseInclude($content);
        // 检查php语法
        $content    =   $this->parsePhp($content);
        //去掉所有的注释 注释类型 <!--  -->, 不包括<!--[   ]-->
        $content = preg_replace('#<!\-\-([^\[])([\s\S]*?)([^\]])\-\->#is', '', $content);
            
        //此处遍历解析tag
        foreach ($this->tags as $key => $tag) {
            $closeTag = isset($tag['close']) ? true : false;
            $level = isset($tag['level']) ? $tag['level'] : false;
            $n1 = empty($tag['attr']) ? '(\s*?)' : '\s([^>]*)';
            $that = $this;
            
            if ($closeTag) {
                $patterns = '/<' . $key . $n1 . '\/(\s*?)>/is';
                $content = preg_replace_callback($patterns, function($matches) use($key, $that){
                    $parse = '_' . $key;
                    return $that->$parse($matches[1], $matches[2]);
                }, $content);
            } else {
                $patterns = '/<' . $key . $n1 . '>(.*?)<\/' . $key . '(\s*?)>/is';
                $content = preg_replace_callback($patterns, function($matches) use($key, $that){
                    $parse = '_' . $key;
                    return $that->$parse($matches[1], $matches[2]);
                }, $content);
                //处理标签嵌套的问题
                if ($level) {
                    for ($i = 0; $i < $level; $i++) {
                        $patterns = '/<' . $key . $n1 . '>(.*?)<\/' . $key . '(\s*?)>/is';
                        $content = preg_replace_callback($patterns, function($matches) use($key, $that){
                            $parse = '_' . $key;
                            return $that->$parse($matches[1], $matches[2]);
                        }, $content);
                    }
                }
            }
        }
        //替换掉比较符号
        $content = str_ireplace(array_keys($this->comparison),array_values($this->comparison), $content);
        
        $content = preg_replace_callback('/({)([^\d\w\s{}].+?)(})/is', array($this, 'parseTag'), $content);
        // 优化生成的php代码
        $content = preg_replace('/\?>(\s+)<\?php /i', '', $content);
        // 替换掉指定的标签
        // 允许用户自定义模板的字符串替换
        if (is_array(C('TMPL_PARSE_STRING'))) {
            $this->replace =  array_merge($this->replace, C('TMPL_PARSE_STRING'));
        }
        $content = str_replace(array_keys($this->replace), array_values($this->replace), $content);
        return $content;
    }
    
    /** 
     * 解析include标签
     * @param string $content 需要解析的内容
     * @return string
     */
    private function parseInclude($content)
    {
        $find       =   preg_match_all('/<include\s(.+?)\s*?\/>/is', $content, $matches);
        if ($find) {
            for ($i = 0; $i < $find; $i++) {
                $include    =   $matches[1][$i];
                $reg = '/(\w+)=(\'[^\']+\'|"[^"]+")\s?/';
                preg_match_all($reg, $include, $array);
                $new_arr = array_combine($array[1], $array[2]);
                $file       =   trim($new_arr['file'], '"\'');
                $content    =   str_replace($matches[0][$i], $this->parseIncludeItem($file), $content);
            }
        }
        return trim($content);
    }
    
    /**
     * 获取需要导入的文件的内容
     * @param string $file 文件的路径
     * @return string
     */
    private function parseIncludeItem($file)
    {
        // 这里解析$file文件
        // 判断是否存在冒号
        if (false !== strpos($file, ':')) {
            $file = $this->viewFolder . '/' . str_replace(':', '/', $file) . '.html';
        } else {
            $file = $this->viewFolder . '/' . $file . '.html';
        }
        if (!is_file($file)) {
            exit('Include Template Not Found!');
        }
        return file_get_contents($file);
    }
    
    /**
     * 解析php短标签
     * @param string $content 需要解析的文件内容
     * @return string
     */
    private function parsePhp($content)
    {
        if (ini_get('short_open_tag')) {
            // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>'."\n", $content );
        }
        return $content;
    }
    
    /**
     * 变量{} 解析
     * @param mixed $tagStr 需要解析的变量
     * @return string
     */
    private function parseTag($tagStr)
    {
        if (is_array($tagStr)) {
            $tagStr = $tagStr[2];
        }
        $tagStr = stripslashes($tagStr);
        $flag   =  substr($tagStr,0,1);
        $flag2  =  substr($tagStr,1,1);
        $name   = substr($tagStr,1);
        if ('$' == $flag && '.' != $flag2 && '(' != $flag2) { //解析模板变量 格式 {$varName}
            // 支持点语法
            if (false !== strpos($name, '.')) {
                $vars = explode('.', $name);
                $var  =  array_shift($vars);
                $name = $var;
                foreach ($vars as $key => $val){
                    $name .= '["' . $val . '"]';
                }
                return '<?php echo ' . $flag . $name . ';?>';
            } else {
                return  '<?php echo ' . $flag . $name . ';?>';
            }
        } elseif ('//' == substr($tagStr, 0, 2) || '/*' == (substr($tagStr, 0, 2) && '*/' == substr(rtrim($tagStr), -2))) {
            //注释标签
            return '';
        }
    }
    
    /**
     * php标签解析
     * @param string $content 内容
     * @return string
     */
     
    private function _php($content) 
    {
        $parseStr = '<?php ' . $content . ' ?>';
        return $parseStr;
    }
    
    /**
     * 解析if标签
     * @param array $match1
     * @param array $match2
     * @return string
     */
    private function _if($match1, $match2)
    {
        $reg = '/(\w+)=(\'[^\']+\'|"[^"]+")(\s?)/';
        preg_match_all($reg, $match1, $match);
        $new_arr = array_combine($match[1], $match[2]);
        $parseStr = '<?php if(' . substr($new_arr['condition'], 1, -1) . '){ ?>' . $match2 . '<?php }?>';
        return $parseStr;
    }
    
    /**
     * 解析elseif标签
     * @param array $match1
     * @param array $match2
     * @return string
     */
    private function _elseif($match1, $match2)
    {
        $reg = '/(\w+)=(\'[^\']+\'|"[^"]+")(\s?)/';
        preg_match_all($reg, $match1, $match);
        $new_arr = array_combine($match[1], $match[2]);
        $parseStr = '<?php }elseif(' . trim($new_arr['condition'], '"') . '){ ?>';
        return $parseStr;
    }
    
    /**
     * 解析else标签
     * @return string
     */
    private function _else()
    {
        $parseStr = '<?php }else{ ?>';
        return $parseStr;
    }
    
    /**
     * 解析volist标签
     * @param array $match1
     * @param array $match2
     * @return string
     */
    private function _volist($match1, $match2)
    {
        $reg = '/(\w+)=(\'[^\']+\'|"[^"]+")(\s?)/';
        preg_match_all($reg, $match1, $match);
        $new_arr = array_combine($match[1], $match[2]);
        
        $name  =    '$' . trim($new_arr['name'], '"');
        $id    =    '$' . trim($new_arr['id'], '"');
        
        $parseStr = '<?php if(is_array(' . $name . ')){foreach(' . $name . ' as $key => ' . $id . '){ ?>' . $match2 . '<?php }}?>';
        return $parseStr;
    }
}