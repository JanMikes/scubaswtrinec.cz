<?php

namespace App\Factories;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
interface IActualityPhotosListFactory
{
    /** @return \App\Components\ActualityPhotosList */
    function create($actualityId);
}
