<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/6/10
 * Time: 上午9:52
 */

namespace App\Api\V1\Services\Trending;

use App\Api\V1\Repositories\BangumiRepository;
use App\Api\V1\Repositories\PostRepository;
use App\Api\V1\Repositories\UserRepository;
use App\Api\V1\Services\Comment\PostCommentService;
use App\Api\V1\Services\Counter\PostViewCounter;
use App\Api\V1\Services\Toggle\Post\PostLikeService;
use App\Api\V1\Services\Toggle\Post\PostMarkService;
use App\Api\V1\Services\Toggle\Post\PostRewardService;
use App\Api\V1\Services\Trending\Base\TrendingService;
use App\Api\V1\Transformers\PostTransformer;
use App\Models\Post;
use Carbon\Carbon;

class PostTrendingService extends TrendingService
{
    protected $visitorId;
    protected $bangumiId;

    public function __construct($bangumiId = 0, $userId = 0)
    {
        parent::__construct('posts', $bangumiId, $userId);
    }

    public function computeNewsIds()
    {
        return Post
            ::where('state', 0)
            ->when($this->bangumiId, function ($query)
            {
                return $query
                    ->where('bangumi_id', $this->bangumiId)
                    ->whereNull('top_at');
            })
            ->orderBy('created_at', 'desc')
            ->latest()
            ->take(100)
            ->pluck('id');
    }

    public function computeActiveIds()
    {
        return Post
            ::where('state', 0)
            ->when($this->bangumiId, function ($query)
            {
                return $query
                    ->where('bangumi_id', $this->bangumiId)
                    ->whereNull('top_at');
            })
            ->orderBy('updated_at', 'desc')
            ->latest()
            ->take(100)
            ->pluck('updated_at', 'id');
    }

    public function computeHotIds()
    {
        $ids = Post
            ::where('state', 0)
            ->where('created_at', '>', Carbon::now()->addDays(-30))
            ->when($this->bangumiId, function ($query)
            {
                return $query
                    ->where('bangumi_id', $this->bangumiId)
                    ->whereNull('top_at');
            })
            ->pluck('id');

        $postRepository = new PostRepository();
        $postLikeService = new PostLikeService();
        $postViewCounter = new PostViewCounter();
        $postCommentService = new PostCommentService();

        $list = $postRepository->list($ids);

        $result = [];
        // https://segmentfault.com/a/1190000004253816
        foreach ($list as $item)
        {
            if (is_null($item))
            {
                continue;
            }

            $postId = $item['id'];
            $likeCount = $postLikeService->total($postId);
            $viewCount = $postViewCounter->get($postId);
            $commentCount = $postCommentService->getCommentCount($postId);

            $result[$postId] = (
                    $likeCount +
                    ($viewCount && log($viewCount, 10) * 4) +
                    ($commentCount && log($commentCount, M_E))
                ) / pow((((time() * 2 - strtotime($item['created_at']) - strtotime($item['updated_at'])) / 2) + 1), 0.3);
        }

        return $result;
    }

    public function computeUserIds()
    {
        return Post
            ::where('state', 0)
            ->where('user_id', $this->userId)
            ->orderBy('created_at', 'desc')
            ->pluck('id');
    }

    public function getListByIds($ids)
    {
        $store = new PostRepository();
        if ($this->bangumiId)
        {
            $list = $store->bangumiFlow($ids);
        }
        else if ($this->userId)
        {
            $list = $store->userFlow($ids);
        }
        else
        {
            $list = $store->trendingFlow($ids);
        }
        if (empty($list))
        {
            return [];
        }

        $likeService = new PostLikeService();
        $rewardService = new PostRewardService();
        $markService = new PostMarkService();
        $commentService = new PostCommentService();

        $list = $commentService->batchGetCommentCount($list);
        $list = $likeService->batchTotal($list, 'like_count');
        $list = $markService->batchTotal($list, 'mark_count');
        foreach ($list as $i => $item)
        {
            if ($item['is_creator'])
            {
                $list[$i]['like_count'] = 0;
                $list[$i]['reward_count'] = $rewardService->total($item['id']);
            }
            else
            {
                $list[$i]['like_count'] = $likeService->total($item['id']);
                $list[$i]['reward_count'] = 0;
            }
        }

        $transformer = new PostTransformer();
        if ($this->bangumiId)
        {
            return $transformer->bangumiFlow($list);
        }
        else if ($this->userId)
        {
            return $transformer->userFlow($list);
        }
        return $transformer->trendingFlow($list);
    }
}