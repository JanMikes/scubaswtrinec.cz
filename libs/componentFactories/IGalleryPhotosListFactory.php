<?php

namespace App\Factories;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
interface IGalleryPhotosListFactory
{
    /** @return \App\Components\GalleryPhotosList */
    function create($galleryId);
}
