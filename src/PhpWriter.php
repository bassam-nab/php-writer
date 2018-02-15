<?php

namespace Src;

use Closure;

class PhpWriter
{
    /**
     * @var array
     */
    var $out = [];
    /**
     * @var null string
     */
    var $className = null;
    /**
     * @var int
     */
    var $numberOfSpaces = 4;

    /**
     * Doc constructor.
     *
     * @param string $start
     */
    public function __construct($start = '<?php')
    {
        $this->out[] = $start;
    }

    /**
     * @param $var mixed
     *
     * @return mixed|string
     */
    private static function valueToText($var)
    {
        if (is_null($var)) {
            return 'null';
        } elseif (is_array($var)) {
            $json = json_encode($var);
            $arrayText = str_replace(['{', '}', ':'], ['[', ']', '=>'], $json);

            return $arrayText;
        } elseif (is_string($var)) {
            return "'$var'";
        }

        return $var;

    }

    /**
     * @param $str string
     *
     * @return mixed
     */
    private static function snakeCase($str)
    {
        return str_replace([' ', '-'], '_', $str);
    }

    /**
     * add <?php to your output code
     * @param string $start
     *
     * @return $this
     */
    public function start($start = '<?php')
    {
        $this->out[] = $start;

        return $this;
    }

    /**
     * add ?> to your output code
     * @param string $start
     *
     * @return $this
     */
    public function end($start = '?>')
    {
        $this->out[] = $start;

        return $this;
    }

    /**
     * add use statement
     * @param $use
     *
     * @return $this
     */
    public function addUse($use)
    {
        $this->out[] = 'use '.$use.';';

        return $this;
    }

    /**
     * @param $traitName
     * @param $callback
     *
     * @return PhpWriter
     */
    public function openTrait($traitName, $callback)
    {
        return $this->openOop('trait', $traitName, $callback);
    }

    /**
     * @param $nameSpace
     *
     * @return $this
     */
    public function putNameSpace($nameSpace)
    {
        $this->out[] = 'namespace '.$nameSpace.';';

        return $this;
    }

    /**
     * @param        $interfaceName
     * @param        $callback
     * @param string $extends
     *
     * @return PhpWriter
     */
    public function openInterface($interfaceName, $callback, $extends = '')
    {
        return $this->openOop('interface', $interfaceName, $callback, $extends);
    }

    /**
     * @param       $className
     * @param       $callback
     * @param null  $extends
     * @param array $implements
     *
     * @return PhpWriter
     */
    public function openClass($className, $callback, $extends = null, $implements = [])
    {
        return $this->openOop('class', $className, $callback, $extends, $implements);
    }

    /**
     * @param      $constName
     * @param null $constValue
     * @param bool $autoCorrect
     *
     * @return $this
     */
    public function addConstant($constName, $constValue = null, $autoCorrect = false)
    {
        $constValue = static::valueToText($constValue ?: $constName);
        $constKey = $autoCorrect ? strtoupper(self::snakeCase($constName)) : $constName;
        $this->out[] = 'const '.$constKey.' = '.$constValue.';';

        return $this;
    }

    /**
     * @param        $varName
     * @param null   $default
     * @param string $scope
     * @param bool   $static
     *
     * @return $this
     */
    public function addVar($varName, $default = null, $scope = 'public', $static = false)
    {
        $varName = self::snakeCase($varName);
        $this->out[] = $scope.' '.($static ? 'static ' : '').'$'.$varName
            .(is_null($default) ? '' : ' = '.self::valueToText($default)).';';

        return $this;
    }

    /**
     * @param        $methodName
     * @param array  $params
     * @param string $scope
     * @param bool   $static
     *
     * @return $this
     */
    public function addMethod($methodName, $params = [], $scope = 'public', $static = false)
    {
        array_walk($params, function (&$v, $k) {
            if ( ! is_int($k)) {
                $v = "$k = ".static::valueToText($v);
            }
        });
        $params = implode(', ', $params);

        $this->out[] = $scope.' '.($static ? 'static ' : '').'function '.$methodName."($params)";
        $this->out[] = '{';
        $this->out[] = [''];
        $this->out[] = '}';

        return $this;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function addLine($text = '')
    {
        $this->out[] = $text;

        return $this;

    }

    /**
     * @param null $filename
     *
     * @return bool|int
     */
    public function exportFile($filename = null)
    {
        $filename = $filename ?: $this->className.'.php';

        return $filename ? file_put_contents($filename, $this->getText()) : false;

    }

    /**
     * @param        $type
     * @param        $name
     * @param        $callback
     * @param string $extends
     * @param array  $implements
     *
     * @return $this
     */
    private function openOop($type, $name, $callback, $extends = '', $implements = [])
    {
        $name = $this->splitClass($name);
        $this->out[] = $type.' '.$name
            .($extends ? ' extends '.$extends : '')
            .($implements ? ' implements '.implode(', ', $implements) : '');
        $this->out[] = '{';
        $this->out[] = $this->getCallbackResult($callback);
        $this->out[] = '}';

        return $this;

    }

    /**
     * @param $class
     *
     * @return bool|string
     */
    private function splitClass($class)
    {
        if (strpos($class, '\\') !== false) {
            $nameSpace = substr($class, 0, strrpos($class, '\\'));
            $this->putNameSpace($nameSpace);
            $class = substr(strrchr($class, '\\'), 1);
            $this->className = $class;
        }

        return $class;

    }

    /**
     * @param $callback
     *
     * @return array
     */
    private function getCallbackResult($callback)
    {
        if ($callback instanceof Closure) {
            $returned = $callback->__invoke(new PhpWriter(''));

            return (array)($returned instanceof static ? // is object
                $returned->out // return out
                : $returned); // return \Closure returned
        }

        return (array)$callback;   // return parameter itself
    }

    /**
     * @return string
     */
    private function getText()
    {
        return implode("\r\n", $this->getLinedArray());
    }

    /**
     * @param null $arr
     * @param int  $tabs
     *
     * @return array
     */
    private function getLinedArray($arr = null, $tabs = 0)
    {
        static $f = [];
        $arr = is_null($arr) ? $this->out : $arr;
        foreach ($arr as $line) {
            if (is_string($line)) {
                $f[] = str_repeat(str_repeat(' ', $this->numberOfSpaces), $tabs).$line;
            } elseif (is_array($line)) {
                $this->getLinedArray($line, $tabs + 1);
            }

        }

        return $f;
    }


}


