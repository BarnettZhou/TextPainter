<?php

namespace Xuchen\TextDrawer;


/**
 * 一些辅助方法
 * Class Helper
 * @package Xuchen\TextDrawer
 */
class Helper
{
    /**
     * 获取数组中的值
     * @param $array
     * @param $key
     * @param null $default
     * @param string $trans_method
     * @return mixed
     */
    public static function getArrayItem($array, $key, $default = null, $trans_method = 'nullval')
    {
        $value = isset($array[$key]) ? $array[$key] : $default;
        if (in_array($trans_method, ['intval', 'strval', 'floatval'])) {
            $value = $trans_method($value);
        }
        if (!$value && $default === null && $trans_method == 'nullval') {
            $value = null;
        }
        return $value;
    }

    /**
     * 递归地获取数组中的值
     * @param $array
     * @param $key
     * @param null $default
     * @param string $trans_method
     * @return mixed|null
     */
    public static function getArrayItemRecursively($array, $key, $default = null, $trans_method = '')
    {
        $key_arr = explode('.', $key);
        if (count($key_arr) > 1) {
            $parent_key = $key_arr[0];
            if (isset($array[$parent_key])) {
                unset($key_arr[0]);
                $child_key = implode('.', $key_arr);
                return self::getArrayItemRecursively($array[$parent_key], $child_key, $default, $trans_method);
            } else {
                return $default;
            }
        } else {
            return self::getArrayItem($array, $key, $default, $trans_method);
        }
    }

    /**
     * 十六进制 转 RGB
     * @param $hexColor
     * @return array
     */
    public static function hex2rgb($hexColor)
    {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = [
                'red' => hexdec(substr($color, 0, 2)),
                'green' => hexdec(substr($color, 2, 2)),
                'blue' => hexdec(substr($color, 4, 2))
            ];
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = [
                'red' => hexdec($r),
                'green' => hexdec($g),
                'blue' => hexdec($b)
            ];
        }
        return $rgb;
    }
}