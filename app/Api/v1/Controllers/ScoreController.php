<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2018/7/11
 * Time: 下午3:55
 */

namespace App\Api\V1\Controllers;

use App\Api\V1\Repositories\BangumiRepository;
use App\Api\V1\Repositories\Repository;
use App\Api\V1\Repositories\ScoreRepository;
use App\Api\V1\Repositories\UserRepository;
use App\Api\V1\Services\Counter\ScoreViewCounter;
use App\Api\V1\Services\Toggle\Bangumi\BangumiScoreService;
use App\Api\V1\Services\Toggle\Score\ScoreLikeService;
use App\Api\V1\Services\Toggle\Score\ScoreMarkService;
use App\Api\V1\Services\Toggle\Score\ScoreRewardService;
use App\Api\V1\Services\Trending\ScoreTrendingService;
use App\Api\V1\Transformers\BangumiTransformer;
use App\Api\V1\Transformers\ScoreTransformer;
use App\Models\Score;
use App\Services\OpenSearch\Search;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;

class ScoreController extends Controller
{
    public function show($id)
    {
        $scoreRepository = new ScoreRepository();
        $score = $scoreRepository->item($id, true);
        if (is_null($score) || !$score['published_at'])
        {
            return $this->resErrNotFound();
        }

        if ($score['deleted_at'])
        {
            if ($score['state'])
            {
                return $this->resErrLocked();
            }

            return $this->resErrNotFound();
        }

        $userRepository = new UserRepository();
        $userId = $score['user_id'];
        $user = $userRepository->item($userId);
        if (is_null($user))
        {
            return $this->resErrNotFound();
        }

        $bangumiRepository = new BangumiRepository();
        $bangumiId = $score['bangumi_id'];
        $bangumi = $bangumiRepository->item($bangumiId);
        if (is_null($bangumi))
        {
            return $this->resErrNotFound();
        }

        $visitorId = $this->getAuthUserId();
        $score['user'] = $user;
        $score['bangumi'] = $bangumiRepository->panel($bangumiId, $visitorId);

        if ($score['is_creator'])
        {
            $scoreRewardService = new ScoreRewardService();
            $score['reward_users'] = $scoreRewardService->users($id);
            $score['rewarded'] = $scoreRewardService->check($visitorId, $id, $userId);
            $score['like_users'] = [
                'list' => [],
                'total' => 0,
                'noMore' => true
            ];;
            $score['liked'] = false;
        }
        else
        {
            $scoreLikeService = new ScoreLikeService();
            $score['like_users'] = $scoreLikeService->users($id);
            $score['liked'] = $scoreLikeService->check($visitorId, $id, $userId);
            $score['reward_users'] = [
                'list' => [],
                'total' => 0,
                'noMore' => true
            ];;
            $score['rewarded'] = false;
        }

        $scoreMarkService = new ScoreMarkService();
        $score['marked'] = $scoreMarkService->check($visitorId, $id);
        $score['mark_users'] = $scoreMarkService->users($id);

        $scoreViewCounter = new ScoreViewCounter();
        $score['view_count'] = $scoreViewCounter->add($id);

        $transformer = new ScoreTransformer();

        $searchService = new Search();
        if ($searchService->checkNeedMigrate('score', $id))
        {
            $job = (new \App\Jobs\Search\UpdateWeight('score', $id));
            dispatch($job);
        }

        return $this->resOK($transformer->show($score));
    }

    public function edit($id)
    {
        $scoreRepository = new ScoreRepository();
        $score = $scoreRepository->item($id);
        if (is_null($score))
        {
            return $this->resErrNotFound();
        }
        $userId = $this->getAuthUserId();
        if ($score['user_id'] != $userId)
        {
            return $this->resErrRole();
        }

        return $this->resOK($score);
    }

    public function bangumis(Request $request)
    {
        $bangumiId = $request->get('id');
        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($bangumiId);
        if (is_null($bangumi))
        {
            return $this->resErrNotFound();
        }

        $scoreRepository = new ScoreRepository();
        $score = $scoreRepository->bangumiScore($bangumiId);

        return $this->resOK($score);
    }

    public function users(Request $request)
    {
        $zone = $request->get('zone');
        $page = $request->get('page') ?: 0;
        $take = $request->get('take') ?: 10;

        $repository = new Repository();
        $userId = $repository->getUserIdByZone($zone);
        if (!$userId)
        {
            return $this->resErrNotFound();
        }

        $scoreTrendingService = new ScoreTrendingService(0, $userId);

        return $this->resOK($scoreTrendingService->users($page, $take));
    }

    public function drafts()
    {
        $userId = $this->getAuthUserId();
        $ids = Score::where('user_id', $userId)
            ->whereNull('published_at')
            ->orderBy('updated_at', 'DESC')
            ->pluck('id')
            ->toArray();

        if (empty($ids))
        {
            return $this->resOK([]);
        }

        $scoreRepository = new ScoreRepository();
        $bangumiRepository = new BangumiRepository();
        $bangumiTransformer = new BangumiTransformer();
        $list = $scoreRepository->list($ids);

        $result = [];
        foreach ($list as $item)
        {
            $bangumi = $bangumiRepository->item($item['bangumi_id']);
            if (is_null($bangumi))
            {
                continue;
            }
            $item['bangumi'] = $bangumiTransformer->item($bangumi);
            $result[] = $item;
        }

        $scoreTransformer = new ScoreTransformer();

        return $this->resOK($scoreTransformer->drafts($result));
    }

    public function check(Request $request)
    {
        $bangumiId = $request->get('id');
        $userId = $this->getAuthUserId();

        $likeService = new BangumiScoreService();
        if (!$likeService->check($userId, $bangumiId))
        {
            return $this->resOK(0);
        }

        $createdId = (int)Score::whereRaw('user_id = ? and bangumi_id = ?', [$userId, $bangumiId])
            ->pluck('id')
            ->first();

        return $this->resOK($createdId);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bangumi_id' => 'required|integer',
            'intro' => 'required|max:120',
            'title' => 'required|string|max:30',
            'content' => 'required|Array',
            'lol' => 'required|integer|min:0|max:10',
            'cry' => 'required|integer|min:0|max:10',
            'fight' => 'required|integer|min:0|max:10',
            'moe' => 'required|integer|min:0|max:10',
            'sound' => 'required|integer|min:0|max:10',
            'vision' => 'required|integer|min:0|max:10',
            'role' => 'required|integer|min:0|max:10',
            'story' => 'required|integer|min:0|max:10',
            'express' => 'required|integer|min:0|max:10',
            'style' => 'required|integer|min:0|max:10',
            'do_publish' => 'required|boolean',
            'is_creator' => 'required|boolean'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $bangumiId = $request->get('bangumi_id');
        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($bangumiId);
        if (is_null($bangumi))
        {
            return $this->resErrNotFound();
        }

        $user = $this->getAuthUser();
        $userId = $user->id;
        $doPublished = $request->get('do_publish');
        $bangumiScoreService = new BangumiScoreService();
        if ($doPublished && $bangumiScoreService->check($userId, $bangumiId))
        {
            return $this->resErrBad('同一番剧不能重复评分');
        }

        $scoreRepository = new ScoreRepository();
        $lol = $request->get('lol');
        $cry = $request->get('cry');
        $fight = $request->get('fight');
        $moe = $request->get('moe');
        $sound = $request->get('sound');
        $vision = $request->get('vision');
        $role = $request->get('role');
        $story = $request->get('story');
        $express = $request->get('express');
        $style = $request->get('style');
        $total = $lol + $cry + $fight + $moe + $sound + $vision + $role + $story + $express + $style;
        $content = $scoreRepository->filterContent($request->get('content'));
        $title = Purifier::clean($request->get('title'));
        $intro = $request->get('intro');
        $now = Carbon::now();

        $newId = DB::table('scores')
            ->insertGetId([
                'user_id' => $userId,
                'user_age' => $this->computeBirthday($user->birthday),
                'user_sex' => $user->sex,
                'bangumi_id' => $bangumiId,
                'lol' => $lol,
                'cry' => $cry,
                'fight' => $fight,
                'moe' => $moe,
                'sound' => $sound,
                'vision' => $vision,
                'role' => $role,
                'story' => $story,
                'express' => $express,
                'style' => $style,
                'total' => $total,
                'title' => $title,
                'content' => $content,
                'intro' => $intro,
                'created_at' => $now,
                'updated_at' => $now,
                'published_at' => $doPublished ? $now : null,
                'is_creator' => $request->get('is_creator')
            ]);

        if ($doPublished)
        {
            $scoreRepository->doPublish($userId, $newId, $bangumiId);;
        }

        return $this->resOK($newId);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'title' => 'required|string|max:30',
            'bangumi_id' => 'required|integer',
            'intro' => 'required|max:120',
            'content' => 'required|Array',
            'lol' => 'required|integer|min:0|max:10',
            'cry' => 'required|integer|min:0|max:10',
            'fight' => 'required|integer|min:0|max:10',
            'moe' => 'required|integer|min:0|max:10',
            'sound' => 'required|integer|min:0|max:10',
            'vision' => 'required|integer|min:0|max:10',
            'role' => 'required|integer|min:0|max:10',
            'story' => 'required|integer|min:0|max:10',
            'express' => 'required|integer|min:0|max:10',
            'style' => 'required|integer|min:0|max:10',
            'do_publish' => 'required|boolean'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $bangumiId = $request->get('bangumi_id');
        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($bangumiId);
        if (is_null($bangumi))
        {
            return $this->resErrNotFound();
        }

        $user = $this->getAuthUser();
        $userId = $user->id;
        $newId = $request->get('id');
        $scoreRepository = new ScoreRepository();
        $score = $scoreRepository->item($newId);
        if ($score['user_id'] != $userId)
        {
            return $this->resErrRole();
        }

        $doPublished = $request->get('do_publish');
        $bangumiScoreService = new BangumiScoreService();
        if ($doPublished && $bangumiScoreService->check($userId, $bangumiId))
        {
            $oldId = (int)Score::whereRaw('user_id = ? and bangumi_id = ?', [$userId, $bangumiId])
                ->pluck('id')
                ->first();

            if ($oldId && $oldId !== $newId)
            {
                return $this->resErrBad('同一番剧不能重复评分');
            }
        }

        $lol = $request->get('lol');
        $cry = $request->get('cry');
        $fight = $request->get('fight');
        $moe = $request->get('moe');
        $sound = $request->get('sound');
        $vision = $request->get('vision');
        $role = $request->get('role');
        $story = $request->get('story');
        $express = $request->get('express');
        $style = $request->get('style');
        $total = $lol + $cry + $fight + $moe + $sound + $vision + $role + $story + $express + $style;
        $content = $scoreRepository->filterContent($request->get('content'));
        $intro = $request->get('intro');
        $title = Purifier::clean($request->get('title'));

        Score::where('id', $newId)
            ->update([
                'user_age' => $this->computeBirthday($user->birthday),
                'user_sex' => $user->sex,
                'lol' => $lol,
                'cry' => $cry,
                'fight' => $fight,
                'moe' => $moe,
                'sound' => $sound,
                'vision' => $vision,
                'role' => $role,
                'story' => $story,
                'express' => $express,
                'style' => $style,
                'total' => $total,
                'title' => $title,
                'content' => $content,
                'intro' => $intro,
                'published_at' => $score['published_at'] ? $score['published_at'] : ($doPublished ? Carbon::now() : null)
            ]);

        if ($doPublished)
        {
            $scoreRepository->doPublish($userId, $newId, $bangumiId);;
        }
        Redis::DEL($scoreRepository->itemCacheKey($newId));

        return $this->resNoContent();
    }

    public function delete(Request $request)
    {
        $id = $request->get('id');
        $userId = $this->getAuthUserId();
        $scoreRepository = new ScoreRepository();
        $score = $scoreRepository->item($id);
        if (is_null($score))
        {
            return $this->resErrNotFound();
        }
        if ($score['user_id'] != $userId)
        {
            return $this->resErrRole();
        }

        $scoreRepository->deleteProcess($id);

        return $this->resNoContent();
    }

    public function trials()
    {
        $ids = Score::withTrashed()
            ->where('state', '<>', 0)
            ->pluck('id')
            ->toArray();

        if (empty($ids))
        {
            return $this->resOK([]);
        }

        $scoreRepository = new ScoreRepository();
        $list = $scoreRepository->list($ids, true);

        return $this->resOK($list);
    }

    public function pass(Request $request)
    {
        $id = $request->get('id');

        $scoreRepository = new ScoreRepository();
        $scoreRepository->recoverProcess($id);

        return $this->resNoContent();
    }

    public function ban(Request $request)
    {
        $id = $request->get('id');

        $scoreRepository = new ScoreRepository();
        $scoreRepository->deleteProcess($id);

        return $this->resNoContent();
    }

    protected function computeBirthday($birthday)
    {
        $age = strtotime($birthday);
        if ($age === false)
        {
            return 0;
        }
        list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age));
        $now = strtotime("now");
        list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now));
        $age = $y2 - $y1;
        if ((int)($m2.$d2) < (int)($m1.$d1))
            $age -= 1;

        return $age;
    }
}