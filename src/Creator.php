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
     * @return array|bool 图片中写入文字所需的必要参数
     * - int image_width 图片的宽度
     * - int image_height 图片的高度
     * - int x 图片左侧底部的x大小
     * - int y 图片左侧底部的y大小
     * - array text 处理过后的文字，切换为数组形式
     */
    public function generateTextWriterParams()
    {
        $result = [
            'font_size' => $this->font_size,
            'font_file' => $this->font_filename,
            'image_width' => 0,
            'image_height' => 0,
        ];

        // 根据换行符拆分文字内容
        $text_array = explode("\n", $this->text);

        // 最终的文字内容
        $text_array_final = [];
        foreach ($text_array as $text_item) {
            // 对每行文字进行计算
            $text_item_params = $this->getTextWriterItemParams($text_item);

            // 获取图片宽度，取当前宽度与每行文字宽度中最大值即可
            $result['image_width'] = max($result['image_width'], $text_item_params['width']);

            // 获取图片高度
            $result['image_height'] += $text_item_params['height'];

            // 最终的文字内容
            $text_array_final = array_merge($text_array_final, $text_item_params['text_array']);
        }
        $result['text'] = $text_array_final;

        // 描边处理，图片尺寸增大，并留出8像素防止误差
        if ($this->outline_width) {
            $result['image_width'] += $this->outline_width * 2 + 4;
            $result['image_height'] += $this->outline_width * 2 + 4;
        }

        return $result;
    }

    /**
     * @param $text_item
     * @return array
     * - int width 该行文字（包括自动换行后的样式）占用的宽度
     * - int height 该行文字（包括自动换行的样式）占用的高度
     * - array text_array 进一步拆分后的文字（可能因自动换行产生新的文字组）
     */
    public function getTextWriterItemParams($text_item)
    {
        $line_width = $this->line_width;
        $line_height = $this->line_height;
        $line_length = $this->line_length;
        $font_size = $this->font_size;
        $font_file = $this->font_filename;

        $result = [
            'width' => $line_width? $line_width : 0,
            'height' => 0,
            'text_array' => [],
        ];

        $text_array = [];   // 文字组
        $prev_text = "";    // 当前遍历到的字符之前的文字
        $last_text = "";    // 遍历结束后剩下的最后一行文字

        // 是否规定行宽
        if ($line_width) {
            // 遍历每一个字符，检查是否超过了规定的行宽
            for ($i = 0; $i < mb_strlen($text_item); $i++) {
                // 获取当前字符
                $curr_char = mb_substr($text_item, $i, 1, 'utf-8');
                // 查看拼接后的字符是否超过最大宽度
                $temp_text = $prev_text . $curr_char;
                $temp_text_box = imagettfbbox($font_size, 0, $font_file, $temp_text);
                $temp_text_width = $this->getWidthFromBox($temp_text_box);
                // 需要换行
                if ($temp_text_width > $line_width) {
                    $prev_text_box = imagettfbbox($font_size, 0, $font_file, $prev_text);
                    $prev_text_height = $this->getHeightFromBox($prev_text_box);
                    $text_array_item = [
                        'content' => $prev_text,
                        'width' => $line_width,
                        // 行高
                        'height' => max($line_height, $prev_text_height),
                        // 文字的实际高度
                        'text_height' => $prev_text_height,
                    ];
                    $text_array[] = $text_array_item;
                    $prev_text = $curr_char;
                    // 所有文字高度
                    $result['height'] += $text_array_item['height'];
                // 不需要换行
                } else {
                    $prev_text = $temp_text;
                }

                // 最后一行文字内容
                $last_text = $prev_text;
            }

            // 获取最后一行的相关数据
            $last_text_box = imagettfbbox($font_size, 0, $font_file, $last_text);
            $last_text_height = $this->getHeightFromBox($last_text_box);
            // 最后一行的内容及设置
            $text_array_item = [
                'content' => $prev_text,
                'width' => $line_width,
                // 行高
                'height' => max($line_height, $last_text_height),
                // 文字实际高度
                'text_height' => $last_text_height,
            ];
            $text_array[] = $text_array_item;
            $result['height'] += $text_array_item['height'];
        // 没有规定行宽
        } else {
            // 是否规定了每行文字的数量
            if ($line_length) {
                $text_lines = $this->subTextWithLineLength($text_item, $line_length);
            } else {
                $text_lines = [$text_item];
            }

            foreach ($text_lines as $text_each_line) {
                // 获取当前行的宽度、高度
                $text_each_line_box = imagettfbbox($font_size, 0, $font_file, $text_each_line);
                $text_each_line_width = $this->getWidthFromBox($text_each_line_box);
                // 注意规定行高的处理
                $text_each_line_height = $this->getHeightFromBox($text_each_line_box);
                // text-item
                $text_array_item = [
                    'content' => $text_each_line,
                    'width' => $text_each_line_width,
                    // 行高
                    'height' => max($text_each_line_height, $line_height),
                    // 文字实际高度
                    'text_height' => $text_each_line_height,
                ];
                $text_array[] =$text_array_item;
                // 直接计算当前行的宽度
                $result['width'] = max($text_each_line_width, $result['width']);
                // 直接计算当前行的高度
                $result['height'] += $text_array_item['height'];
            }
        }

        $result['text_array'] = $text_array;
        return $result;
    }

    /**
     * 使用规定的行文字数量分割字符串
     * @param $text
     * @param $line_length
     * @return array
     */
    public function subTextWithLineLength($text, $line_length)
    {
        $text_length = mb_strlen($text);
        if ($text_length <= $line_length) {
            return [$text];
        }

        $result = [];
        $line_count = intval(ceil($text_length / $line_length));
        $current_text_length = $text_length;
        for ($index = 0; $index < $line_count; $index++) {
            if ($current_text_length > $line_length) {
                $sub_length = $line_length;
            } else {
                $sub_length = $current_text_length;
            }
            $result[] = mb_substr($text, $line_length * $index, $sub_length);
            $current_text_length -= $line_length;
        }
        return $result;
    }

    /**
     * 将文字写入图片
     * @param resource $image_resource
     * @param array $params 相关参数，见 $this->calculateTextWriterParams 的返回值说明
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
     * 从box数组中获取文字占用的宽度
     * @param $box
     * @return mixed
     */
    public function getWidthFromBox($box)
    {
        return max(abs($box[2] - $box[0]), abs($box[4] - $box[6]));
    }

    /**
     * 从box数组中获取文字占用的高度
     * @param $box
     * @return mixed
     */
    public function getHeightFromBox($box)
    {
        return max(abs($box[7] - $box[1]), abs($box[5] - $box[3]));
    }

    /**
     * 十六进制 转 RGB
     * @param $hexColor
     * @return array
     */
    public function hex2rgb($hexColor)
    {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = array(
                'red' => hexdec(substr($color, 0, 2)),
                'green' => hexdec(substr($color, 2, 2)),
                'blue' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'red' => hexdec($r),
                'green' => hexdec($g),
                'blue' => hexdec($b)
            );
        }
        return $rgb;
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