<?php

namespace Xuchen\TextDrawer;


/**
 * Class Demo
 * @package Xuchen\TextDrawer
 */
class Demo
{
    public function execute()
    {
        $creator = new Creator();
        $creator->text = "那么人呐就都不知道，自己就不可以预料。\n"
            . "一个人的命运啊，当然要靠自我奋斗，但是也要考虑到历史的行程。\n"
            . "我绝对不知道，我作为一个上海市委书记怎么把我选到北京去了。\n"
            . "所以邓小平同志跟我讲话，说“中央都决定啦，你来当总书记”，我说另请高明吧。\n"
            . "我实在我也不是谦虚，我一个上海市委书记怎么到北京来了呢？";
        $creator->font_size = 16;
        $creator->font_size_unit = 'pixel';
        $creator->align = 'center';
        $creator->line_width = 400;
        $creator->line_height = 30;
        $creator->font_color = new Color(59, 89, 152);
        $creator->execute();
        imagepng($creator->image, time() . '.png');
        $creator->freeResources();
    }
}
