<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/1/5
 * Time: 上午11:36
 */

namespace App\Api\V1\Transformers;


class VideoTransformer extends Transformer
{
    public function show($video)
    {
        return $this->transformer($video, function ($video)
        {
            return [
                'id' => (int)$video['id'],
                'user_id' => (int)$video['user_id'],
                'bangumi_id' => (int)$video['bangumi_id'],
                'src' => $video['src'],
                'name' => $video['name'],
                'part' => (int)$video['part'],
                'poster' => $video['poster'],
                'other_site' => (boolean)$video['other_site']
            ];
        });
    }

    public function search($video)
    {
        return $this->transformer($video, function ($video)
        {
            return [
                'id' => (int)$video['id'],
                'name' => $video['name'],
                'poster' => $video['poster']
            ];
        });
    }
}