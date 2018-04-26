<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2017/12/23
 * Time: 上午10:08
 */

namespace App\Api\V1\Repositories;

use App\Models\Banner;
use App\Models\Image;
use App\Models\ImageLike;
use App\Models\ImageTag;
use App\Models\Tag;

class ImageRepository extends Repository
{
    public function item($id)
    {
        $result = $this->RedisHash('user_image_' . $id, function () use ($id)
        {
            $image = Image::where('id', $id)->first();
            if (is_null($image))
            {
                return null;
            }

            $image = $image->toArray();
            $image['image_count'] = $image['image_count'] ? ($image['image_count'] - 1) : 0;
            $image['width'] = $image['width'] ? $image['width'] : 200;
            $image['height'] = $image['height'] ? $image['height'] : 200;

            return $image;
        }, 'm');

        $meta = $this->Cache('user_image_' . $id . '_meta', function () use ($result)
        {
            $tagIds = ImageTag::where('image_id', $result['id'])->pluck('tag_id');

            $bangumiRepository = new BangumiRepository();
            $cartoonRoleRepository = new CartoonRoleRepository();
            $userRepository = new UserRepository();

            return [
                'user' => $userRepository->item($result['user_id']),
                'role' => $result['role_id'] ? $cartoonRoleRepository->item($result['role_id']) : null,
                'bangumi' => $result['bangumi_id'] ? $bangumiRepository->item($result['bangumi_id']) : null,
                'tags' => Tag::whereIn('id', $tagIds)->select('id', 'name')->get(),
                'size' => $result['image_count'] ? null : Tag::where('id', $result['size_id'])->select('id', 'name')->first()
            ];
        });

        return array_merge($result, $meta);
    }

    public function list($ids)
    {
        $result = [];
        foreach ($ids as $id)
        {
            $item = $this->item($id);
            if ($item) {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function checkLiked($imageId, $userId, $authorId)
    {
        if ($userId == $authorId)
        {
            return false;
        }
        return $userId
            ? (boolean)ImageLike::whereRaw('image_id = ? and user_id = ?', [$imageId, $userId])->count()
            : false;
    }

    public function uptoken()
    {
        $auth = new \App\Services\Qiniu\Auth();
        $timeout = 3600;
        $uptoken = $auth->uploadToken(null, $timeout, [
            'returnBody' => '{
                "code": 0,
                "data": {
                    "height": $(imageInfo.height),
                    "width": $(imageInfo.width),
                    "type": "$(mimeType)",
                    "size": $(fsize),
                    "key": "$(key)"
                }
            }'
        ]);

        return [
            'upToken' => $uptoken,
            'expiredAt' => time() + $timeout
        ];
    }

    public function banners()
    {
        $list = $this->RedisList('loop_banners', function ()
        {
            $list =  Banner::select('id', 'url', 'user_id', 'bangumi_id', 'gray')->get()->toArray();

            $userRepository = new UserRepository();
            $bangumiRepository = new BangumiRepository();

            foreach ($list as $i => $image)
            {
                if ($image['user_id'])
                {
                    $user = $userRepository->item($image['user_id']);
                    $list[$i]['user_nickname'] = $user['nickname'];
                    $list[$i]['user_avatar'] = $user['avatar'];
                    $list[$i]['user_zone'] = $user['zone'];
                }
                else
                {
                    $list[$i]['user_nickname'] = '';
                    $list[$i]['user_avatar'] = '';
                    $list[$i]['user_zone'] = '';
                }

                if ($image['bangumi_id'])
                {
                    $bangumi = $bangumiRepository->item($image['bangumi_id']);
                    $list[$i]['bangumi_name'] = $bangumi['name'];
                }
                else
                {
                    $list[$i]['bangumi_name'] = '';
                }

                $list[$i] = json_encode($list[$i]);
            }

            return $list;
        });

        $result = [];
        foreach ($list as $item)
        {
            $result[] = json_decode($item, true);
        }

        return $result;
    }

    public function uploadImageTypes()
    {
        return $this->Cache('upload-image-types', function ()
        {
            $size = Tag::where('model', '2')->select('name', 'id')->get();
            $tags = Tag::where('model', '1')->select('name', 'id')->get();

            return [
                'size' => $size,
                'tags' => $tags
            ];
        }, 'm');
    }
}