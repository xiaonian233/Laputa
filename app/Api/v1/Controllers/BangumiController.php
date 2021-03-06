<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Services\Counter\BangumiScoreCounter;
use App\Api\V1\Services\Owner\BangumiManager;
use App\Api\V1\Services\Tag\BangumiTagService;
use App\Api\V1\Services\Toggle\Bangumi\BangumiFollowService;
use App\Api\V1\Services\Toggle\Bangumi\BangumiScoreService;
use App\Api\V1\Transformers\BangumiTransformer;
use App\Models\Bangumi;
use App\Api\V1\Repositories\BangumiRepository;
use App\Models\Video;
use App\Services\OpenSearch\Search;
use App\Services\Trial\ImageFilter;
use App\Services\Trial\WordsFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

/**
 * @Resource("番剧相关接口")
 */
class BangumiController extends Controller
{
    /**
     * 番剧时间轴
     *
     * @Get("/bangumi/timeline")
     *
     * @Parameters({
     *      @Parameter("year", description="从哪一年开始获取", type="integer", required=true)
     * })
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": {"list": "番剧列表", "noMore": "没有更多了"}}),
     *      @Response(400, body={"code": 40003, "message": "参数错误"})
     * })
     */
    public function timeline(Request $request)
    {
        $year = intval($request->get('year'));
        $take = 1;

        if (!$year)
        {
            return $this->resErrBad();
        }

        $bangumiRepository = new BangumiRepository();
        $list = [];
        $minYear = intval($bangumiRepository->timelineMinYear());

        for ($i = 0; $i < $take; $i++)
        {
            $list = array_merge($list, $bangumiRepository->timeline($year - $i));
        }

        return $this->resOK([
            'list' => $list,
            'noMore' => $year <= $minYear
        ]);
    }

    /**
     * 新番列表（周）
     *
     * @Get("/bangumi/released")
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "番剧列表"})
     * })
     */
    public function released()
    {
        $data = Cache::remember('bangumi_release_list', 60, function ()
        {
            $ids = Bangumi::where('released_at', '<>', 0)
                ->orderBy('released_time', 'DESC')
                ->pluck('id');

            $repository = new BangumiRepository();
            $list = $repository->list($ids);

            $result = [
                [], [], [], [], [], [], [], []
            ];
            foreach ($list as $item)
            {
                $item['update'] = time() - $item['released_time'] < 604800;
                $id = $item['released_at'];
                $result[$id][] = $item;
                $result[0][] = $item;
            }

            $bangumiTransformer = new BangumiTransformer();
            foreach ($result as $i => $arr)
            {
                $result[$i] = $bangumiTransformer->released($arr);
            }

            return $result;
        });

        return $this->resOK($data);
    }

    /**
     * 所有的番剧标签
     *
     * @Get("/bangumi/tags")
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "标签列表"})
     * })
     */
    public function tags()
    {
        $bangumiTagService = new BangumiTagService();

        return $this->resOK($bangumiTagService->all());
    }

    /**
     * 根据标签获取番剧列表
     *
     * @Get("/bangumi/category")
     *
     * @Parameters({
     *      @Parameter("id", description="选中的标签id，`用 - 链接的字符串`", type="string", required=true),
     *      @Parameter("page", description="页码", type="integer", default=0, required=true)
     * })
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": {"list": "番剧列表", "total": "该标签下番剧的总数", "noMore": "是否没有更多了"}}),
     *      @Response(400, body={"code": 40003, "message": "请求参数错误"})
     * })
     */
    public function category(Request $request)
    {
        $tags = $request->get('id');
        $page = $request->get('page') ?: 0;

        if (is_null($tags))
        {
            return $this->resErrBad();
        }

        // 格式化为数组 -> 只保留数字 -> 去重 -> 保留value
        $tags = array_values(array_unique(array_filter(explode('-', $tags), function ($tag) {
            return !preg_match("/[^\d-., ]/", $tag);
        })));

        if (empty($tags))
        {
            return $this->resErrBad();
        }

        sort($tags);
        $repository = new BangumiRepository();

        return $this->resOK($repository->category($tags, $page));
    }

    /**
     * 番剧详情
     *
     * @Get("/bangumi/`bangumiId`/show")
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "番剧信息"}),
     *      @Response(404, body={"code": 40401, "message": "不存在的番剧"})
     * })
     */
    public function show($id)
    {
        $repository = new BangumiRepository();
        $bangumi = $repository->item($id);
        if (is_null($bangumi))
        {
            return $this->resErrNotFound('没有找到番剧');
        }

        $userId = $this->getAuthUserId();

        $bangumiFollowService = new BangumiFollowService();
        $bangumi['follow_users'] = $bangumiFollowService->users($id);
        $bangumi['followed'] = $bangumiFollowService->check($userId, $id);

        $bangumiScoreService = new BangumiScoreService();
        $bangumiScoreCounter = new BangumiScoreCounter();
        $bangumi['count_score'] = $bangumiScoreService->total($id);
        $bangumi['scored'] = $bangumiScoreService->check($userId, $id);
        $bangumi['score'] = $bangumiScoreCounter->get($id);

        $bangumiManager = new BangumiManager();
        $bangumi['is_master'] = $bangumiManager->isOwner($id, $userId);
        $bangumi['manager_users'] = $bangumiManager->users($id);

        $bangumiTagService = new BangumiTagService();
        $bangumi['tags'] = $bangumiTagService->tags($id);

        $bangumiTransformer = new BangumiTransformer();

        $searchService = new Search();
        if ($searchService->checkNeedMigrate('bangumi', $id))
        {
            $job = (new \App\Jobs\Search\UpdateWeight('bangumi', $id));
            dispatch($job);
        }

        return $this->resOK($bangumiTransformer->show($bangumi));
    }

    /**
     * 番剧视频
     *
     * @Get("/bangumi/`bangumiId`/videos")
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": {"videos": "视频列表", "has_season": "是否有多个季度", "total": "视频总数"}}),
     *      @Response(404, body={"code": 40401, "message": "不存在的番剧"})
     * })
     */
    public function videos($id)
    {
        $repository = new BangumiRepository();
        $bangumi = $repository->item($id);

        if (is_null($bangumi))
        {
            return $this->resErrNotFound('没有找到番剧');
        }

        return $this->resOK($repository->videos($id, json_decode($bangumi['season'])));
    }

    public function updateBangumiRelease(Request $request)
    {
        $bangumi_id = $request->get('bangumi_id');
        $video_id = $request->get('video_id');

        $video = Video::find($video_id);
        if (is_null($video))
        {
            return $this->resErrBad('不存在的视频');
        }

        Bangumi::where('id', $bangumi_id)->update([
            'released_time' => time(),
            'released_video_id' => $video_id
        ]);

        Redis::DEL('bangumi_release_list');
        Redis::DEL('bangumi_' . $bangumi_id);

        $job = (new \App\Jobs\Push\Baidu('bangumi/news'));
        dispatch($job);

        return $this->resNoContent();
    }

    public function adminList(Request $request)
    {
        $curPage = $request->get('cur_page') ?: 0;
        $toPage = $request->get('to_page') ?: 1;
        $take = $request->get('take') ?: 10;

        $list = Bangumi::withTrashed()
            ->orderBy('id', 'DESC')
            ->select('id', 'name', 'deleted_at')
            ->take(($toPage - $curPage) * $take)
            ->skip($curPage * $take)
            ->get();

        return $this->resOK([
            'list' => $list,
            'total' => Bangumi::count()
        ]);
    }

    public function deleteBangumi(Request $request)
    {
        $id = $request->get('id');
        $bangumi = Bangumi::withTrashed()->find($id);

        if (is_null($bangumi))
        {
            return $this->resErrNotFound();
        }

        if (is_null($bangumi->deleted_at))
        {
            $bangumi->delete();

            $job = (new \App\Jobs\Search\Index('D', 'bangumi', $id));
            dispatch($job);

            Redis::DEL('bangumi_'.$id);
        }
        else
        {
            $bangumi->restore();
        }

        return $this->resNoContent();
    }

    public function getAdminBangumiInfo(Request $request)
    {
        $id = $request->get('id');

        $bangumi = Bangumi::withTrashed()->find($id);
        if (is_null($bangumi))
        {
            return $this->resErrNotFound();
        }

        $bangumiTagService = new BangumiTagService();

        $bangumi['alias'] = $bangumi['alias'] === 'null' ? '' : json_decode($bangumi['alias'])->search;
        $bangumi['tags'] = $bangumiTagService->tags($id);
        $bangumi['season'] = $bangumi['season'] === 'null' ? '' : $bangumi['season'];
        $bangumi['published_at'] = $bangumi['published_at'] * 1000;

        return $this->resOK($bangumi);
    }

    public function create(Request $request)
    {
        $releasedId = $request->get('released_video_id') ?: 0;
        $time = Carbon::now();
        $bangumiId = Bangumi::insertGetId([
            'name' => $request->get('name'),
            'avatar' => $request->get('avatar'),
            'banner' => $request->get('banner'),
            'summary' => $request->get('summary'),
            'released_at' => $request->get('released_at'),
            'released_video_id' => $releasedId,
            'season' => $request->get('season') ? $request->get('season') : 'null',
            'alias' => $request->get('alias') ? json_encode([
                'search' => $request->get('alias')
            ]) : 'null',
            'published_at' => $request->get('published_at') ?: 0,
            'others_site_video' => $request->get('others_site_video'),
            'end' => $request->get('end'),
            'created_at' => $time,
            'updated_at' => $time
        ]);

        $bangumiTagService = new BangumiTagService();
        $bangumiTagService->update($bangumiId, $request->get('tags'));

        if ($releasedId)
        {
            Redis::DEL('bangumi_release_list');
        }
        Redis::DEL('bangumi_all_list');

        $bangumiRepository = new BangumiRepository();
        $bangumiRepository->migrateSearchIndex('C', $bangumiId);

        return $this->resCreated($bangumiId);
    }

    public function edit(Request $request)
    {
        $rollback = false;
        $bangumiId = $request->get('id');
        DB::beginTransaction();

        $bangumiTagService = new BangumiTagService();
        $result = $bangumiTagService->update($bangumiId, $request->get('tags'));

        if (!$result)
        {
            $rollback = true;
        }

        $bangumi = Bangumi::withTrashed()->where('id', $bangumiId)->first();
        $arr = [
            'name' => $request->get('name'),
            'avatar' => $request->get('avatar'),
            'banner' => $request->get('banner'),
            'summary' => $request->get('summary'),
            'released_at' => $request->get('released_at'),
            'released_video_id' => $request->get('released_video_id'),
            'season' => $request->get('season') ? $request->get('season') : 'null',
            'alias' => $request->get('alias') ? json_encode([
                'search' => $request->get('alias')
            ]) : 'null',
            'end' => $request->get('end'),
            'published_at' => $request->get('published_at'),
            'others_site_video' => $request->get('others_site_video'),
            'has_cartoon' => $request->get('has_cartoon'),
            'has_video' => $request->get('has_video')
        ];

        $result = $bangumi->update($arr);
        if ($result === false)
        {
            $rollback = true;
        }

        if ($rollback)
        {
            DB::rollBack();

            return $this->resErrBad('更新失败');
        }
        else
        {
            DB::commit();

            Redis::DEL('bangumi_'.$bangumiId);
            Redis::DEL('bangumi_'.$bangumiId.'_videos');

            $bangumiRepository = new BangumiRepository();
            $bangumiRepository->migrateSearchIndex('U', $bangumiId);

            return $this->resNoContent();
        }
    }

    public function setManager(Request $request)
    {
        $userId = $request->get('user_id');
        $bangumiId = $request->get('bangumi_id');

        $bangumiManager = new BangumiManager();
        $result = $bangumiManager->set($bangumiId, $userId);

        if (!$result)
        {
            return $this->resErrBad();
        }

        return $this->resNoContent();
    }

    public function removeManager(Request $request)
    {
        $userId = $request->get('user_id');
        $bangumiId = $request->get('bangumi_id');

        $bangumiManager = new BangumiManager();
        $result = $bangumiManager->remove($bangumiId, $userId);

        if (!$result)
        {
            return $this->resErrBad();
        }

        return $this->resNoContent();
    }

    public function upgradeManager(Request $request)
    {
        $userId = $request->get('user_id');
        $bangumiId = $request->get('bangumi_id');

        $bangumiManager = new BangumiManager();
        $result = $bangumiManager->upgrade($bangumiId, $userId);

        if (!$result)
        {
            return $this->resErrBad();
        }

        return $this->resNoContent();
    }

    public function downgradeManager(Request $request)
    {
        $userId = $request->get('user_id');
        $bangumiId = $request->get('bangumi_id');

        $bangumiManager = new BangumiManager();
        $result = $bangumiManager->downgrade($bangumiId, $userId);

        if (!$result)
        {
            return $this->resErrBad();
        }

        return $this->resNoContent();
    }

    /**
     * 吧主编辑番剧信息
     *
     * @Post("/bangumi/`bangumiId`/edit")
     *
     * @Parameters({
     *      @Parameter("avatar", description="封面图链接，不包含 host", type="string", required=true),
     *      @Parameter("banner", description="背景图链接，不包含 host", type="string", required=true),
     *      @Parameter("summary", description="200字以内的纯文本", type="string", required=true),
     *      @Parameter("tags", description="标签的id数组", type="array", required=true)
     * })
     *
     * @Transaction({
     *      @Request(headers={"Authorization": "Bearer JWT-Token"}),
     *      @Response(204),
     *      @Response(400, body={"code": 40003, "message": "请求参数错误或内容非法"}),
     *      @Response(403, body={"code": 40301, "message": "权限不足"}),
     *      @Response(503, body={"50301": 40301, "message": "服务暂不可用"})
     * })
     */
    public function editBangumiInfo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'banner' => 'required|string',
            'avatar' => 'required|string',
            'summary' => 'required|max:200',
            'tags' => 'array'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $bangumiRepository = new BangumiRepository();
        $bangumi = $bangumiRepository->item($id);
        if (is_null($bangumi))
        {
            return $this->resErrNotFound();
        }

        $userId = $this->getAuthUserId();
        $bangumiManager = new BangumiManager();
        if (!$bangumiManager->isOwner($id, $userId))
        {
            return $this->resErrRole();
        }

        $summary = Purifier::clean($request->get('summary'));
        $avatar = $request->get('avatar');
        $banner = $request->get('banner');

        $wordsFilter = new WordsFilter();
        if ($wordsFilter->count($summary) > 2)
        {
            return $this->resErrBad('修改文本不合法，请联系管理员查看');
        }

        $imageFilter = new ImageFilter();
        if ($imageFilter->bad($avatar) || $imageFilter->bad($banner))
        {
            return $this->resErrBad('修改图片不合法，请联系管理员查看');
        }

        DB::beginTransaction();
        $rollback = false;
        $bangumiTagService = new BangumiTagService();
        $result = $bangumiTagService->update($id, $request->get('tags'));
        if (!$result)
        {
            $rollback = true;
        }

        $result = Bangumi::where('id', $id)
            ->update([
                'summary' => $summary,
                'avatar' => $avatar,
                'banner' => $banner,
                'state' => $userId
            ]);
        if ($result === false)
        {
            $rollback = true;
        }

        if ($rollback)
        {
            DB::rollBack();
            return $this->resErrServiceUnavailable('更新失败');
        }
        else
        {
            DB::commit();

            Redis::DEL('bangumi_' . $id);
            Redis::DEL('bangumi_'. $id .'_tags');

            $bangumiRepository = new BangumiRepository();
            $bangumiRepository->migrateSearchIndex('U', $id);

            return $this->resNoContent();
        }
    }

    public function trials()
    {
        $bangumiIds = Bangumi::where('state', '<>', 0)
            ->pluck('id');

        if (is_null($bangumiIds))
        {
            return $this->resOK([]);
        }

        $bangumiRepository = new BangumiRepository();

        $list = $bangumiRepository->list($bangumiIds);

        return $this->resOK($list);
    }

    public function pass(Request $request)
    {
        Bangumi::where('id', $request->get('id'))
            ->update([
                'state' => 0
            ]);

        return $this->resNoContent();
    }
}
