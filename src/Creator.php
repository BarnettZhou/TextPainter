<?php

namespace Xuchen\TextDrawer;


/**
 * 写文字方法
 * 增加文字描边支持
 *
 * Class Creator
 * @package frontend\components\DrawText
 * @author zhouxuchen
 */
class Creator
{
    /**
     * @var string 错误信息
     */
    protected $error_message = '';

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }

    /**
     * @var string 临时文件所在目录，默认使用./
     */
    public $temp_dir = './';

    /**
     * @var string 字体文件
     */
    public $font_filename;

    /**
     * @var int 字体大小，默认18
     */
    public $font_size = 18;

    /**
     * @var Color 字体颜色，默认黑色
     */
    public $font_color;

    /**
     * @var array 默认字体颜色
     */
    private $default_font_color = ['red' => 0, 'green' => 0, 'blue' => '0', 'alpha' => 0];

    /**
     * @var int 描边宽度，默认为0，表示无边框
     */
    private $outline_width = 0;

    /**
     * @var array 背景颜色
     */
    public $background_color = ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 127];

    /**
     * @var Color 描边颜色，默认白色
     */
    public $outline_color;

    /**
     * @var array 默认描边颜色
     */
    private $default_outline_color = ['red' => 254, 'green' => 254, 'blue' => 254, 'alpha' => 0];

    /**
     * @var int 行宽，默认为0，表示不规定行宽
     */
    public $line_width = 0;

    /**
     * @var int 行高，默认为0，表示不规定行高
     */
    public $line_height = 0;

    /**
     * @var int 每行字数，默认为0， 表示不规定每行字数
     */
    public $line_length = 0;

    /**
     * @var string 文字内容
     */
    public $text;

    /**
     * @var string 生成的图片名称
     */
    public $local_temp_filename;

    /**
     * @var resource 最终生成的图片资源
     */
    public $image = null;

    /**
     * Creator constructor.
     */
    public function __construct()
    {
        // 设置一个默认字体
//        var_dump(dirname(__FILE__) . '/resources/PingFang.ttf');die;
        $this->font_filename = dirname(__FILE__) . '/resources/PingFang.ttf';
    }

    /**
     * 设置字体颜色
     * @param array $rgb
     * @return Creator
     */
    public function setFontColor($rgb = [])
    {
        $red = isset($rgb['red']) ? $rgb['red'] : $this->default_font_color['red'];
        $green = isset($rgb['green']) ? $rgb['green'] : $this->default_font_color['green'];
        $blue = isset($rgb['blue']) ? $rgb['blue'] : $this->default_font_color['blue'];
        $this->font_color = new Color($red, $green, $blue);
        return $this;
    }

    /**
     * 设置描边参数
     * @param $outline_width
     * @param $rgb
     * @return Creator
     */
    public function setOutline($outline_width, $rgb = null)
    {
        $this->outline_width = $outline_width;
        if ($this->outline_width == 0) {
            return $this;
        }

        $red = isset($rgb['red']) ? $rgb['red'] : $this->default_outline_color['red'];
        $green = isset($rgb['green']) ? $rgb['green'] : $this->default_outline_color['green'];
        $blue = isset($rgb['blue']) ? $rgb['blue'] : $this->default_outline_color['blue'];
        $this->outline_color = new Color($red, $green, $blue);
        return $this;
    }

    /**
     * execute
     * @return bool
     */
    public function execute()
    {
        if (!$this->text) {
            $this->error_message = '请填写文字内容';
            return false;
        }

        if (!$this->font_color) {
            $this->font_color = Color::initWithArray($this->default_font_color);
        }

        $calculator = new Calculator($this);
        $params = $calculator->generateTextWriterParams();

        // 创建空白图片
        $image = $this->createTransparentImage($params['image_width'], $params['image_height']);

        // 写文字
        $this->drawTextToImage($image, $params);

        // 描边
        // todo: 文字描边

        $this->image = $image;

        return true;
    }

    /**
     * 将文字写入图片
     * @param resource $image_resource
     * @param array $params 相关参数，见 Calculator::calculateTextWriterParams 的返回值说明
     * @return resource
     */
    public function drawTextToImage($image_resource, $params)
    {
        // 字体拾色
        $font_color_allocate = imagecolorallocate(
            $image_resource,
            $this->font_color->red,
            $this->font_color->green,
            $this->font_color->blue
        );

        // 计算出基础的 x y
        $box = imagettfbbox($this->font_size, 0, $this->font_filename, $params['text'][0]['content']);
        $x = 0 - $box[0];
        $y = 0 - $box[1];

        // 上方基线
        $top = $y;

        foreach ($params['text'] as $item) {
            // 文字上下居中处理
            if ($item['height'] <= $item['text_height']) {
                $y = $top + $item['text_height'];
            } else {
                $y = ($item['height'] + $item['text_height']) / 2 + $top;
            }
            $top += $item['height'];
            // 写文字
            imagettftext($image_resource, $params['font_size'], 0, $x, $y, $font_color_allocate, $this->font_filename, $item['content']);
        }

        return $image_resource;
    }

    /**
     * 生成描边
     * fixme
     * @param $image
     */
    public function generateOutline($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // 描边拾色
        $outline_color_allocate = imagecolorallocate(
            $image,
            $this->outline_color->red,
            $this->outline_color->green,
            $this->outline_color->blue
        );

        $around_config = [
            'north-west'    => ['x' => -1, 'y' => 1],
            'north'         => ['x' => 0, 'y' => 1],
            'north-east'    => ['x' => 1, 'y' => 1],
            'west'          => ['x' => -1, 'y' => 0],
            'east'          => ['x' => 1, 'y' => 0],
            'south-west'    => ['x' => -1, 'y' => -1],
            'south'         => ['x' => 0, 'y' => -1],
            'south-east'    => ['x' => 1, 'y' => -1],
        ];

        $steps = 1;


        while ($steps <= $this->outline_width) {

            $handled_pixels = [];

            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $color_index = imagecolorat($image, $x, $y);
                    $pixel_color = Color::initWithArray(imagecolorsforindex($image, $color_index));

                    // 像素点是否已经处理过
                    if (in_array($x.':'.$y, $handled_pixels)) {
                        continue;
                    }

                    // 是否为透明区域，是则跳过
                    if ($pixel_color->isTransparent(Color::ERROR_LEVEL_MEDIUM)) {
                        continue;
                    }

                    // 是否为文字区域或已描边区域，是则添加描边
                    if ($pixel_color->isEqualTo($this->font_color, Color::ERROR_LEVEL_LOW)
                        || $pixel_color->isEqualTo($this->outline_color, Color::ERROR_LEVEL_LOW)) {

                        // 添加描边
                        // ---------------------------------
                        // north-west    north    north-east
                        //       west    center   east
                        // south-west    south    south-east
                        // ---------------------------------
                        foreach ($around_config as $item) {
                            $handled_pixels[] = ($x + $item['x']) . ':' . ($y + $item['y']);
                            $around_color = Color::initWithArray(imagecolorsforindex($image, imagecolorat($image, $x + $item['x'], $y + $item['y'])));
                            if ($around_color->isTransparent(Color::ERROR_LEVEL_MEDIUM)) {
                                imagesetpixel($image, $x + $item['x'], $y + $item['y'], $outline_color_allocate);
                            }
                        }

                        $handled_pixels[] = $x . ':' . $y;

                    }
                }
            }

            $steps++;
        }
    }

    /**
     * 生成一张透明图片
     * @param $width
     * @param $height
     * @return resource
     */
    public function createTransparentImage($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        $color = imagecolorallocatealpha(
            $image,
            $this->background_color['red'],
            $this->background_color['green'],
            $this->background_color['blue'],
            127
        );
        imagefill($image, 0, 0, $color);
        return $image;
    }

    /**
     * 清空图片资源
     */
    public function freeResources()
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }
}