<?php
/**
 * 基础数据过滤
 * @param  string $name 需要获取的字段名
 * @param  string $default 默认的返回值
 * @param  string $filter 需要格式化的类型
 * @return mixed
 */
function I($name, $default = '', $filter = '')
{
    if (strpos($name, '.')) {
        list($method, $name) = explode('.', $name, 2);
    } else {
        return '';
    }
    $method = strtoupper($method);
    if ('GET' == $method) {
        $_arr = array_change_key_case($_GET);
    } elseif('POST' == $method){
        $_arr = array_change_key_case($_POST);
    }
    if (isset($_arr[$name])) {
        switch (strtolower($filter)) {
            case 'int':
                $data = (int)$_arr[$name]; 
                break;
            case 'float':
                $data = (float)$_arr[$name];
                break;
            case 'string':
                $data = preg_replace('/[^0-9a-zA-Z]/', '', $_arr[$name]);
                break;
            case 'utf8':
                $data = preg_replace('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', '', $_arr[$name]);
                break;
            case 'num':
                $data = preg_replace('/[^0-9]/', '', $_arr[$name]);
                break;
            default:
                $data = (string)$_arr[$name];
                break;
        }
        return $data;
    } else {
        return $_arr;
    }
}

/**
 * 获取和设置配置
 * $name 格式为 C('arr') 或 C('arr.name') , C() 和 C("") 都将返回所有的参数
 * @param string $name 需要获取的配置名称
 * @param string $value 需要设置的值
 * @param mixed $default 当没有结果时候的默认返回值
 * @return mixed
 */
function C($name = null, $value = null,$default = null) 
{
    static $_config = array();
    // 无参数时获取所有
    if (empty($name)) {
        return $_config;
    }
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtoupper($name);
            if (is_null($value)) {
                return isset($_config[$name]) ? $_config[$name] : $default;
            }
            $_config[$name] = $value;
            return null;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0]   =  strtoupper($name[0]);
        if (is_null($value)) {
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
        }
        $_config[$name[0]][$name[1]] = $value;
        return null;
    }
    // 批量设置
    if (is_array($name)){
        $_config = array_merge($_config, array_change_key_case($name, CASE_UPPER));
        return null;
    }
    return null; // 避免非法参数
}
