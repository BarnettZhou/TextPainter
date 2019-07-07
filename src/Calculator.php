<?php

namespace Xuchen\TextDrawer;


/**
 * 计算相关参数
 * Class Calculator
 * @package Xuchen\TextDrawer
 */
class Calculator
{
    /**
     * @var Creator;
     */
    private $creator;

    /**
     * Calculator constructor.
     * @param Creator $creator
     */
    public function __construct($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @param Creator $creator
     * @return Calculator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return array|bool 图片中写入文字所需的必要参数
     * - int image_width 图片的宽度
     * - int image_height 图片的高度
     * - array text 处理过后的文字，切换为数组形式
     */
    public function generateTextWriterParams()
    {
        $result = [
            'font_size' => $this->creator->font_size,
            'font_file' => $this->creator->font_filename,
            'image_width' => 0,
            'image_height' => 0,
        ];

        // 根据换行符拆分文字内容
        $text = str_replace('\n', "\n", $this->creator->text);
        $text_array = explode("\n", $text);

        // 根据 line_length 拆分文字内容
        if ($this->creator->line_length) {
            $temp_text_array = [];
            foreach ($text_array as $text) {
                $temp_text_array = array_merge(
                    $temp_text_array,
                    $this->subTextWithLineLength($text, $this->creator->line_length)
                );
            }

            $text_array = $temp_text_array;
        }

        // 最终的文字内容
        $text_array_final = [];
        foreach ($text_array as $text_item) {
            // 对每行文字进行计算
            $text_item_params = $this->getTextWriterItemParams($text_item);

            // 获取图片宽度，取当前宽度与每行文字宽度中最大值即可
            $result['image_width'] = max($result['image_width'], $text_item_params['line_width']);

            // 获取图片高度
            $result['image_height'] += $text_item_params['line_height'];

            // 最终的文字内容
            $text_array_final = array_merge($text_array_final, $text_item_params['text_array']);
        }
        $result['text'] = $text_array_final;

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
        $line_width     = $this->creator->line_width;
        $line_height    = $this->creator->line_height;
        $font_size      = $this->creator->font_size;
        $font_file      = $this->creator->font_filename;

        $result = [
            'line_width' => $line_width ? $line_width : 0,
            'line_height' => 0,
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
                        'width' => $this->getWidthFromBox($prev_text_box),
                        // 行高
                        'height' => max($line_height, $prev_text_height),
                        // 文字的实际高度
                        'text_height' => $prev_text_height,
                    ];
                    $text_array[] = $text_array_item;
                    $prev_text = $curr_char;

                    // 所有文字高度增加
                    $result['line_height'] += $text_array_item['height'];
                } else {
                    // 不需要换行
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
                'width' => $this->getWidthFromBox($last_text_box),
                // 行高
                'height' => max($line_height, $last_text_height),
                // 文字实际高度
                'text_height' => $last_text_height,
            ];
            $text_array[] = $text_array_item;
            $result['line_height'] += $text_array_item['height'];
        } else {
            // 没有规定行宽
            // 获取当前行的宽度、高度
            $text_each_line_box = imagettfbbox($font_size, 0, $font_file, $text_item);
            $text_each_line_width = $this->getWidthFromBox($text_each_line_box);
            // 注意规定行高的处理
            $text_each_line_height = $this->getHeightFromBox($text_each_line_box);
            // text-item
            $text_array_item = [
                'content' => $text_item,
                'width' => $text_each_line_width,
                // 行高
                'height' => max($text_each_line_height, $line_height),
                // 文字实际高度
                'text_height' => $text_each_line_height,
            ];
            $text_array[] = $text_array_item;
            // 直接计算当前行的宽度
            $result['line_width'] = max($text_each_line_width, $result['line_width']);
            // 直接计算当前行的高度
            $result['line_height'] += $text_array_item['height'];
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
}
