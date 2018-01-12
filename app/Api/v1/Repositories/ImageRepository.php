<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2017/12/23
 * Time: 上午10:08
 */

namespace App\Api\V1\Repositories;


class ImageRepository
{
    public function uptoken()
    {
        $auth = new \App\Services\Qiniu\Auth();
        $timeout = 3600;
        $uptoken = $auth->uploadToken(null, $timeout, [
            'returnBody' => '{
                "w": "$(imageInfo.width)",
                "h": "$(imageInfo.height)",
                "key": "$(key)"
            }'
        ]);

        return [
            'upToken' => $uptoken,
            'expiredAt' => time() + $timeout
        ];
    }
}