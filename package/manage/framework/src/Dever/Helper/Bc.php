<?php namespace Dever\Helper;
use \Exception;
use \ReflectionFunction;
#扩展的bcmatch，简单直接用Dever::Math('calc', '1*2/10')就行
/* 
echo Bc::evaluate('1*2/10');
echo Bc::evaluate('price * qty + tax', [
    'price' => '12.50',
    'qty' => '3',
    'tax' => '2.50'
]); // 输出 40.00

echo Bc::evaluate('max(10, 20) + abs(-3)'); // 输出 23.00

// 自定义函数注册
Bc::registerFunction('discount', fn($price) => bcmul($price, '0.9', 2));
echo Bc::evaluate('discount(100)'); // 输出 90.00

$expr = 'max(10, min(5, 2)) + a > 5 && b < 10';
$vars = ['a' => 3, 'b' => 9];
$result = Bc::evaluate($expr, $vars, 4);

*/
class Bc
{
    private static $scale = 2;
    private static $variables = [];
    private static $functions = [
        'abs' => 'abs',
        'max' => 'max',
        'min' => 'min',
    ];

    private static $operators = [
        '+' => ['precedence' => 1, 'assoc' => 'L', 'args' => 2],
        '-' => ['precedence' => 1, 'assoc' => 'L', 'args' => 2],
        '*' => ['precedence' => 2, 'assoc' => 'L', 'args' => 2],
        '/' => ['precedence' => 2, 'assoc' => 'L', 'args' => 2],

        '>'  => ['precedence' => 0, 'assoc' => 'L', 'args' => 2],
        '<'  => ['precedence' => 0, 'assoc' => 'L', 'args' => 2],
        '>=' => ['precedence' => 0, 'assoc' => 'L', 'args' => 2],
        '<=' => ['precedence' => 0, 'assoc' => 'L', 'args' => 2],
        '==' => ['precedence' => 0, 'assoc' => 'L', 'args' => 2],
        '!=' => ['precedence' => 0, 'assoc' => 'L', 'args' => 2],

        '&&' => ['precedence' => -1, 'assoc' => 'L', 'args' => 2],
        '||' => ['precedence' => -2, 'assoc' => 'L', 'args' => 2],

        'u-' => ['precedence' => 3, 'assoc' => 'R', 'args' => 1], // unary minus
    ];

    // 评估表达式
    public static function evaluate(string $expr, array $vars = [], int $scale = 2): string
    {
        self::$scale = $scale;
        self::$variables = $vars;

        try {
            $tokens = self::tokenize($expr);
            $tokens = self::replaceVariables($tokens);
            $tokens = self::handleUnaryOperators($tokens);
            $rpn = self::toRPN($tokens);
            $result = self::compute($rpn);
            return $result;
        } catch (Exception $e) {
            throw new Exception("Expression error: " . $e->getMessage());
        }
    }

    // 注册自定义函数
    public static function registerFunction(string $name, callable $callback): void
    {
        self::$functions[$name] = $callback;
    }

    // 分词
    private static function tokenize(string $expr): array
    {
        $expr = preg_replace('/\s+/', '', $expr); // 去除所有空格

        // 匹配运算符、数字、函数名、变量
        preg_match_all('/
            (>=|<=|==|!=|&&|\|\|)     # 多字符运算符
            |[+\-*\/(),<>]             # 单字符运算符和括号
            |\d*\.\d+|\d+              # 浮点数或整数
            |[a-zA-Z_]\w*              # 变量或函数名
        /x', $expr, $matches);

        if (empty($matches[0])) {
            throw new Exception("无法解析表达式");
        }
        return $matches[0];
    }

    // 替换变量为对应值（仅替换完整变量名，避免误替换函数名）
    private static function replaceVariables(array $tokens): array
    {
        return array_map(function ($token) {
            if (isset(self::$variables[$token])) {
                return (string)self::$variables[$token];
            }
            return $token;
        }, $tokens);
    }

    // 处理一元负号：例如 -5 转换成 u-
    private static function handleUnaryOperators(array $tokens): array
    {
        $result = [];
        $prev = null;
        foreach ($tokens as $token) {
            if ($token === '-' && ($prev === null || (isset(self::$operators[$prev]) && $prev !== ')'))) {
                $result[] = 'u-';
            } else {
                $result[] = $token;
            }
            $prev = end($result);
        }
        return $result;
    }

    // 转换为逆波兰表达式（RPN）
    private static function toRPN(array $tokens): array
    {
        $output = [];
        $stack = [];
        $functions = array_keys(self::$functions);
        $ops = self::$operators;

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = $token;
            } elseif (in_array($token, $functions)) {
                $stack[] = $token;
            } elseif ($token === ',') {
                while (!empty($stack) && end($stack) !== '(') {
                    $output[] = array_pop($stack);
                }
                if (empty($stack)) {
                    throw new Exception("函数参数分隔符错误");
                }
            } elseif (isset($ops[$token])) {
                while (!empty($stack)) {
                    $top = end($stack);
                    if ($top === '(') break;
                    if (!isset($ops[$top])) break;

                    $currOp = $ops[$token];
                    $topOp = $ops[$top];

                    if (
                        ($currOp['assoc'] === 'L' && $currOp['precedence'] <= $topOp['precedence']) ||
                        ($currOp['assoc'] === 'R' && $currOp['precedence'] < $topOp['precedence'])
                    ) {
                        $output[] = array_pop($stack);
                    } else {
                        break;
                    }
                }
                $stack[] = $token;
            } elseif ($token === '(') {
                $stack[] = $token;
            } elseif ($token === ')') {
                while (!empty($stack) && end($stack) !== '(') {
                    $output[] = array_pop($stack);
                }
                if (empty($stack)) {
                    throw new Exception("括号不匹配");
                }
                array_pop($stack); // 弹出 '('

                if (!empty($stack) && in_array(end($stack), $functions)) {
                    $output[] = array_pop($stack);
                }
            } else {
                // 非数字、运算符、函数名，直接输出（已经替换过变量）
                $output[] = $token;
            }
        }

        while (!empty($stack)) {
            $top = array_pop($stack);
            if ($top === '(' || $top === ')') {
                throw new Exception("括号不匹配");
            }
            $output[] = $top;
        }

        return $output;
    }

    // 计算逆波兰表达式
    private static function compute(array $rpn): string
    {
        $stack = [];
        $useBc = extension_loaded('bcmath');

        foreach ($rpn as $token) {
            if (is_numeric($token)) {
                $stack[] = $token;
            } elseif (isset(self::$functions[$token])) {
                // 取出所有栈顶元素作为参数（用可变参数调用）
                $fn = self::$functions[$token];

                // 由于无法准确知道参数个数，尝试把栈中所有元素都传过去（自定义函数需自行处理）
                // 这里我们只取最后一个参数，或者修改接口更合理
                // 简化：只支持单参数函数（或 max/min等变参），调用方式如下：
                if (is_string($fn) && in_array($fn, ['max', 'min'])) {
                    // max,min 取所有栈顶元素直到遇到非数字
                    $args = [];
                    while (!empty($stack) && is_numeric(end($stack))) {
                        $args[] = array_pop($stack);
                    }
                    $args = array_reverse($args);
                    $result = call_user_func_array($fn, $args);
                } else {
                    // 取一个参数
                    if (empty($stack)) {
                        throw new Exception("函数参数不足");
                    }
                    $arg = array_pop($stack);
                    $result = call_user_func($fn, $arg);
                }
                $stack[] = $result;
            } elseif (isset(self::$operators[$token])) {
                $op = self::$operators[$token];
                $args = [];
                for ($i = 0; $i < $op['args']; $i++) {
                    if (empty($stack)) {
                        throw new Exception("操作数不足");
                    }
                    array_unshift($args, array_pop($stack));
                }

                if ($useBc) {
                    switch ($token) {
                        case '+': $res = bcadd($args[0], $args[1], self::$scale); break;
                        case '-': $res = bcsub($args[0], $args[1], self::$scale); break;
                        case '*': $res = bcmul($args[0], $args[1], self::$scale); break;
                        case '/': 
                            if (bccomp($args[1], '0', self::$scale) == 0) {
                                throw new Exception("除数不能为零");
                            }
                            $res = bcdiv($args[0], $args[1], self::$scale);
                            break;
                        case '>':  $res = bccomp($args[0], $args[1], self::$scale) === 1 ? '1' : '0'; break;
                        case '<':  $res = bccomp($args[0], $args[1], self::$scale) === -1 ? '1' : '0'; break;
                        case '>=': $res = bccomp($args[0], $args[1], self::$scale) >= 0 ? '1' : '0'; break;
                        case '<=': $res = bccomp($args[0], $args[1], self::$scale) <= 0 ? '1' : '0'; break;
                        case '==': $res = bccomp($args[0], $args[1], self::$scale) === 0 ? '1' : '0'; break;
                        case '!=': $res = bccomp($args[0], $args[1], self::$scale) !== 0 ? '1' : '0'; break;
                        case '&&':
                            $res = ((bccomp($args[0], '0', self::$scale) !== 0) && (bccomp($args[1], '0', self::$scale) !== 0)) ? '1' : '0';
                            break;
                        case '||':
                            $res = ((bccomp($args[0], '0', self::$scale) !== 0) || (bccomp($args[1], '0', self::$scale) !== 0)) ? '1' : '0';
                            break;
                        case 'u-':
                            $res = bcmul('-1', $args[0], self::$scale);
                            break;
                        default:
                            throw new Exception("未知操作符：$token");
                    }
                } else {
                    // 不支持 bcmath 时，用浮点数运算
                    switch ($token) {
                        case '+': $res = $args[0] + $args[1]; break;
                        case '-': $res = $args[0] - $args[1]; break;
                        case '*': $res = $args[0] * $args[1]; break;
                        case '/':
                            if ($args[1] == 0) {
                                throw new Exception("除数不能为零");
                            }
                            $res = $args[0] / $args[1];
                            break;
                        case '>':  $res = ($args[0] > $args[1]) ? '1' : '0'; break;
                        case '<':  $res = ($args[0] < $args[1]) ? '1' : '0'; break;
                        case '>=': $res = ($args[0] >= $args[1]) ? '1' : '0'; break;
                        case '<=': $res = ($args[0] <= $args[1]) ? '1' : '0'; break;
                        case '==': $res = ($args[0] == $args[1]) ? '1' : '0'; break;
                        case '!=': $res = ($args[0] != $args[1]) ? '1' : '0'; break;
                        case '&&':
                            $res = ($args[0] && $args[1]) ? '1' : '0';
                            break;
                        case '||':
                            $res = ($args[0] || $args[1]) ? '1' : '0';
                            break;
                        case 'u-':
                            $res = -$args[0];
                            break;
                        default:
                            throw new Exception("未知操作符：$token");
                    }
                }

                $stack[] = (string)$res;
            } else {
                throw new Exception("未知标记：$token");
            }
        }

        if (count($stack) !== 1) {
            throw new Exception("表达式解析错误，结果堆栈异常");
        }

        return $stack[0];
    }
}