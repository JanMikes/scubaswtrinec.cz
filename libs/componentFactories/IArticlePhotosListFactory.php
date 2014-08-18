<?php

namespace App\Factories;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
interface IArticlePhotosListFactory
{
    /** @return \App\Components\ArticlePhotosList */
    function create($articleId);
}
