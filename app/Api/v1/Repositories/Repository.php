<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2017/12/28
 * Time: 上午7:52
 */

namespace App\Api\V1\Repositories;


use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class Repository
{
    public function list($ids, $isShow = false)
    {
        if (empty($ids))
        {
            return [];
        }

        $result = [];
        foreach ($ids as $id)
        {
            $item = $this->item($id, $isShow);
            if ($item)
            {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function applyAddComment($userId, $post, $images, $newComment)
    {

    }

    public function RedisList($key, $func, $start = 0, $count = -1)
    {
        $cache = Redis::LRANGE($key, $start, $count === -1 ? -1 : $count + $start - 1);

        if (!empty($cache))
        {
            return $cache;
        }

        $cache = $func();
        $cache = gettype($cache) === 'array' ? $cache : $cache->toArray();

        if (empty($cache))
        {
            return [];
        }

        if (Redis::SETNX('lock_'.$key, 1))
        {
            Redis::pipeline(function ($pipe) use ($key, $cache)
            {
                $pipe->EXPIRE('lock_'.$key, 10);
                $pipe->DEL($key);
                $pipe->RPUSH($key, $cache);
                $pipe->EXPIREAT($key, $this->expire());
                $pipe->DEL('lock_'.$key);
            });
        }

        return $count === -1 ? array_slice($cache, $start) : array_slice($cache, $start, $count);
    }

    public function RedisSort($key, $func, $isTime = false, $withScore = false, $exp = 'd')
    {
        $cache = $withScore ? Redis::ZREVRANGE($key, 0, -1, 'WITHSCORES') : Redis::ZREVRANGE($key, 0, -1);

        if (empty($cache))
        {
            $cache = $func();
            $cache = gettype($cache) === 'array' ? $cache : $cache->toArray();

            if (empty($cache))
            {
                return [];
            }

            if ($isTime)
            {
                foreach ($cache as $i => $item)
                {
                    $cache[$i] = gettype($item) === 'string' ? strtotime($item) : $item->timestamp;
                }
            }

            if (Redis::SETNX('lock_'.$key, 1))
            {
                Redis::pipeline(function ($pipe) use ($key, $cache, $exp)
                {
                    $pipe->EXPIRE('lock_'.$key, 10);
                    $pipe->DEL($key);
                    $pipe->ZADD($key, $cache);
                    $pipe->EXPIREAT($key, $this->expire($exp));
                    $pipe->DEL('lock_'.$key);
                });
            }

            return $withScore ? $cache : array_keys($cache);
        }

        return $cache;
    }

    public function RedisHash($key, $func, $exp = 'd')
    {
        $cache = Redis::HGETALL($key);

        if (!empty($cache))
        {
            return $cache;
        }

        $cache = $func();

        if (is_null($cache))
        {
            return null;
        }

        if (Redis::SETNX('lock_'.$key, 1))
        {
            Redis::pipeline(function ($pipe) use ($key, $cache, $exp)
            {
                $pipe->EXPIRE('lock_'.$key, 10);
                $pipe->HMSET($key, gettype($cache) === 'array' ? $cache : $cache->toArray());
                $pipe->EXPIREAT($key, $this->expire($exp));
                $pipe->DEL('lock_'.$key);
            });
        }

        return $cache;
    }

    public function RedisItem($key, $func, $exp = 'd')
    {
        $cache = Redis::GET($key);
        if (!is_null($cache))
        {
            return $cache;
        }

        $cache = $func();
        if (is_null($cache))
        {
            return null;
        }

        if (Redis::SETNX('lock_'.$key, 1))
        {
            Redis::pipeline(function ($pipe) use ($key, $cache, $exp)
            {
                $pipe->EXPIRE('lock_'.$key, 10);
                $pipe->SET($key, $cache);
                $pipe->EXPIREAT($key, $this->expire($exp));
                $pipe->DEL('lock_'.$key);
            });
        }

        return $cache;
    }

    public function Cache($key, $func, $exp = 'd')
    {
        return Cache::remember($key, $this->expiredAt($exp), function () use ($func)
        {
            return $func();
        });
    }

    public function ListInsertBefore($key, $value)
    {
        if (Redis::EXISTS($key))
        {
            Redis::LPUSHX($key, $value);
        }
    }

    public function ListInsertAfter($key, $value)
    {
        if (Redis::EXISTS($key))
        {
            Redis::RPUSHX($key, $value);
        }
    }

    public function ListRemove($key, $value, $count = 1)
    {
        Redis::LREM($key, $count, $value);
    }

    public function SortAdd($key, $value, $score = 0)
    {
        if (Redis::EXISTS($key))
        {
            $score = $score === 0 ? strtotime('now') : $score;
            Redis::ZADD($key, $score, $value);
        }
    }

    public function SortRemove($key, $value)
    {
        Redis::ZREM($key, $value);
    }

    public function filterIdsByMaxId($ids, $maxId, $take, $withScore = false)
    {
        if (empty($ids))
        {
            return [
                'ids' => [],
                'total' => 0,
                'noMore' => true
            ];
        }

        if ($withScore)
        {
            $offset = $maxId ? array_search($maxId, array_keys($ids)) + 1 : 0;
        }
        else
        {
            $offset = $maxId ? array_search($maxId, $ids) + 1 : 0;
        }

        $total = count($ids);
        $result = array_slice($ids, $offset, $take, $withScore);

        return [
            'ids' => $result,
            'total' => $total,
            'noMore' => $result > 0 ? ($total - ($offset + $take) <= 0) : true
        ];
    }

    public function filterIdsBySeenIds($ids, $seenIds, $take, $withScore = false)
    {
        if (empty($ids))
        {
            return [
                'ids' => [],
                'total' => 0,
                'noMore' => true
            ];
        }

        $total = count($ids);
        if ($withScore)
        {
            foreach ($ids as $key => $val)
            {
                if (in_array($key, $seenIds))
                {
                    unset($ids[$key]);
                }
            }
            $result = array_slice($ids, 0, $take, true);
        }
        else
        {
            $result = array_slice(array_diff($ids, $seenIds), 0, $take);
        }

        return [
            'ids' => $result,
            'total' => $total,
            'noMore' => count($result) < $take
        ];
    }

    public function filterIdsByPage($ids, $page, $take, $withScore = false)
    {
        $ids = gettype($ids) === 'string' ? explode(',', $ids) : $ids;

        if (empty($ids))
        {
            return [
                'ids' => [],
                'total' => 0,
                'noMore' => true
            ];
        }

        $result = array_slice($ids, $page * $take, $take, $withScore);
        $total = count($ids);

        return [
            'ids' => $result,
            'total' => $total,
            'noMore' => $total - ($page + 1) * $take <= 0
        ];
    }

    private function expiredAt($type = 'd')
    {
        if (gettype($type) === 'integer')
        {
            return $type;
        }
        if ($type === 'd')
        {
            return 720;
        }
        else if ($type === 'h')
        {
            return 60;
        }
        else if ($type === 'm')
        {
            return 5;
        }

        return 86400;
    }

    private function expire($type = 'd')
    {
        if (gettype($type) === 'integer')
        {
            return $type;
        }
        /**
         * d：缓存一天，第二天凌晨的 1 ~ 3 点删除
         * h：缓存一小时
         * m：缓存五分钟
         */
        $day = strtotime(date('Y-m-d')) + 86400 + rand(3600, 10800);
        $hour = strtotime(date('Y-m-d')) + 3600;
        $minute = strtotime(date('Y-m-d')) + 300;

        if ($type === 'd')
        {
            return $day;
        }
        else if ($type === 'h')
        {
            return $hour;
        }
        else if ($type === 'm')
        {
            return $minute;
        }

        return $day;
    }

    public function getUserIdByZone($zone, $force = false)
    {
        $userId = User::where('zone', $zone)
            ->when($force, function ($query)
            {
                return $query->withTrashed();
            })
            ->pluck('id')
            ->first();

        return is_null($userId) ? 0 : $userId;
    }

    public function userFlow($ids)
    {
        $list = $this->list($ids);
        $bangumiRepository = new BangumiRepository();

        return $bangumiRepository->appendBangumiToList($list);
    }

    public function bangumiFlow($ids)
    {
        $list = $this->list($ids);
        $userRepository = new UserRepository();

        return $userRepository->appendUserToList($list);
    }

    public function trendingFlow($ids)
    {
        $list = $this->list($ids);
        $userRepository = new UserRepository();
        $bangumiRepository = new BangumiRepository();

        $list = $userRepository->appendUserToList($list);
        $list = $bangumiRepository->appendBangumiToList($list);

        return $list;
    }

    public function convertImagePath($url)
    {
        return str_replace(config('website.image'), '', $url);
    }

    protected function formatJsonContent($content)
    {
        $content = json_decode($content, true);
        $result = [];
        foreach ($content as $item)
        {
            unset($item['id']);
            if ($item['type'] === 'img')
            {
                $item['url'] = config('website.image') . $item['url'];
            }
            $result[] = $item;
        }

        return $result;
    }
}