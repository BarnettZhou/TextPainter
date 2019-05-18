<?php

namespace Xuchen\TextDrawer;


class Color
{
    /**
     * @var int red
     */
    public $red = 0;

    /**
     * @var int green
     */
    public $green = 0;

    /**
     * @var int blue
     */
    public $blue = 0;

    /**
     * @var int 不透明度，0-100
     */
    public $opacity = 100;

    /**
     * 误差等级
     */
    const ERROR_LEVEL_TOP       = 0;
    const ERROR_LEVEL_HIGH      = 25;
    const ERROR_LEVEL_MEDIUM    = 50;
    const ERROR_LEVEL_LOW       = 75;
    const ERROR_LEVEL_OFF       = 100;

    /**
     * 从数组初始化 Color 对象
     * @param $array
     * @return Color
     */
    public static function initWithArray($array)
    {
        $red = isset($array['red'])? intval($array['red']) : 0;
        $green = isset($array['green'])? intval($array['green']) : 0;
        $blue = isset($array['blue'])? intval($array['blue']) : 0;

        // 透明度兼容性处理
        $opacity = isset($array['opacity'])? $array['opacity'] : 100;
        if (isset($array['alpha'])) {
            $opacity = intval((1 - ($array['alpha'] / 127)) * 100);
        }

        return new self($red, $green, $blue, $opacity);
    }

    /**
     * Color constructor.
     * @param $red
     * @param $green
     * @param $blue
     * @param int $opacity
     */
    public function __construct($red, $green, $blue, $opacity = 100)
    {
        $this->red      = intval($red);
        $this->green    = intval($green);
        $this->blue     = intval($blue);
        $this->opacity  = $opacity;
    }

    /**
     * 两种颜色是否相等
     * @param Color $another_color
     * @param int $error_level
     * @return bool
     */
    public function isEqualTo($another_color, $error_level = self::ERROR_LEVEL_TOP)
    {
        // red
        if ($this->red !== $another_color->red) {
            return false;
        }

        // green
        if ($this->green !== $another_color->green) {
            return false;
        }

        // blue
        if ($this->blue !== $another_color->blue) {
            return false;
        }

        // 不计算透明度，直接返回true
        if ($error_level == self::ERROR_LEVEL_OFF) {
            return true;
        }

        // 计算两者 opacity 误差
        if (abs($this->opacity - $another_color->opacity) > $error_level) {
            return false;
        }

        return true;
    }

    /**
     * 是否为透明颜色
     * @param int $error_level
     * @return bool
     */
    public function isTransparent($error_level = self::ERROR_LEVEL_TOP)
    {
        if ($error_level == self::ERROR_LEVEL_TOP && $this->opacity != 0) {
            return false;
        }

        return $this->opacity < $error_level;
    }
}
