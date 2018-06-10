<?php

namespace App\Api\V1\Services\Vote;

use App\Api\V1\Repositories\UserRepository;
use App\Api\V1\Services\Counter\ToggleCountService;
use App\Api\V1\Transformers\UserTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Api\V1\Repositories\Repository;

/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/6/2
 * Time: 下午3:27
 */
class VoteService extends Repository
{
    protected $modal;
    protected $table;
    protected $field;
    protected $needCacheList;

    public function __construct($modalTable, $modalField, $toggleTable, $needCacheList = false)
    {
        $this->modal = $modalTable;
        $this->field = $modalField;
        $this->table = $toggleTable;
        $this->needCacheList = $needCacheList;
    }

    public function do($userId, $modalId, $count = 1)
    {
        $id = DB::table($this->table)
            ->insertGetId([
                'user_id' => $userId,
                'modal_id' => $modalId,
                'created_at' => Carbon::now()
            ]);

        $toggleCountService = new ToggleCountService($this->modal, $this->field, $this->table, $modalId);
        $toggleCountService->add($modalId, $count);

        if ($this->needCacheList)
        {
            $this->SortAdd($this->doUsersCacheKey($modalId), $userId);
            $this->SortAdd($this->usersDoCacheKey($userId), $modalId);
        }

        return $id;
    }

    public function undo($doId, $userId, $modalId)
    {
        DB::table($this->table)
            ->where('id', $doId)
            ->delete();

        $toggleCountService = new ToggleCountService($this->modal, $this->field, $this->table, $modalId);
        $toggleCountService->add($modalId, -1);

        if ($this->needCacheList)
        {
            $this->SortRemove($this->doUsersCacheKey($modalId), $userId);
            $this->SortRemove($this->usersDoCacheKey($userId), $modalId);
        }

        return 0;
    }

    public function doUsersTotal($modalId)
    {
        $toggleCountService = new ToggleCountService($this->modal, $this->field, $this->table, $modalId);

        return $toggleCountService->get($modalId);
    }

    public function usersDoTotal()
    {
        // 还没有这个表
        // user_stats?
    }

    public function check($userId, $modalId, $modalCreatorId = 0)
    {
        if ($userId === $modalCreatorId)
        {
            return 0;
        }

        $id = DB::table($this->table)
            ->whereRaw('user_id = ? and modal_id = ?', [$userId, $modalId])
            ->pluck('id')
            ->first();

        if (is_null($id))
        {
            return 0;
        }

        return $id;
    }

    public function toggle($userId, $modalId)
    {
        $doId = $this->check($userId, $modalId);

        return $doId
            ? $this->undo($doId, $userId, $modalId)
            : $this->do($userId, $modalId);
    }

    public function total($modalId)
    {
        $toggleCountService = new ToggleCountService($this->modal, $this->field, $this->table, $modalId);

        return $toggleCountService->get($modalId);
    }

    public function users($modalId, $page = 0, $count = 10)
    {
        $ids = $this->doUsersIds($modalId, $page, $count);

        if (empty($ids))
        {
            return [];
        }

        $userRepository = new UserRepository();
        $users = [];

        foreach ($ids as $id => $createdAt)
        {
            $user = $userRepository->item($id);
            if (is_null($user))
            {
                continue;
            }
            $user['created_at'] = $createdAt;
            $users[] = $user;
        }

        $userTransformer = new UserTransformer();

        return $userTransformer->toggleUsers($users);
    }
    // 某个用户的文章收藏列表
    public function usersDoIds($userId, $page = 0, $count = 10)
    {
        if (!$this->needCacheList)
        {
            return [];
        }

        $ids = $this->RedisSort($this->usersDoCacheKey($userId), function () use ($userId)
        {
            return DB::table($this->table)
                ->where('user_id', $userId)
                ->orderBy('id', 'DESC')
                ->pluck('created_at', 'modal_id AS id');

        }, true, true);

        if (empty($ids))
        {
            return [];
        }

        return $page === -1
            ? $ids
            : array_slice($ids, $page * $count, $count, true);
    }
    // 谋篇文章的收藏者们
    protected function doUsersIds($modalId, $page, $count)
    {
        if (!$this->needCacheList)
        {
            return [];
        }

        $ids = $this->RedisSort($this->doUsersCacheKey($modalId), function () use ($modalId)
        {
            return DB::table($this->table)
                ->where('modal_id', $modalId)
                ->orderBy('id', 'DESC')
                ->pluck('created_at', 'user_id AS id');

        }, true, true);

        if (empty($ids))
        {
            return [];
        }

        return array_slice($ids, $page * $count, $count, true);
    }

    protected function usersDoCacheKey($userId)
    {
        return 'user_' . $userId . '_' . $this->table . '_' . $this->field . '_ids';
    }

    protected function doUsersCacheKey($modalId)
    {
        return $this->table . '_' . $modalId .  '_' . $this->field . '_ids';
    }
}