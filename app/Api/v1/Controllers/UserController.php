<?php
/**
 * Created by PhpStorm.
 * User: 23002
 * Date: 2017/11/25
 * Time: 0:20
 */

namespace App\Api\V1\Controllers;

use App\Api\V1\Services\Counter\Stats\TotalUserCount;
use App\Api\V1\Transformers\UserTransformer;
use App\Models\Feedback;
use App\Models\Notifications;
use App\Models\User;
use App\Api\V1\Repositories\UserRepository;
use App\Models\UserCoin;
use App\Models\UserSign;
use App\Services\OpenSearch\Search;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

/**
 * @Resource("用户相关接口")
 */
class UserController extends Controller
{
    /**
     * 用户每日签到
     *
     * @Post("/user/daySign")
     *
     * @Transaction({
     *      @Request(headers={"Authorization": "Bearer JWT-Token"}),
     *      @Response(204),
     *      @Response(401, body={"code": 40104, "message": "未登录的用户"}),
     *      @Response(403, body={"code": 40301, "message": "今日已签到"})
     * })
     */
    public function daySign()
    {
        $repository = new UserRepository();
        $userId = $this->getAuthUserId();

        if ($repository->daySigned($userId))
        {
            return $this->resErrRole('已签到');
        }

        UserCoin::create([
            'from_user_id' => $userId,
            'user_id' => $userId,
            'type' => 0
        ]);

        UserSign::create([
            'user_id' => $userId
        ]);

        User::where('id', $userId)->increment('coin_count', 1);

        return $this->resNoContent();
    }

    /**
     * 更新用户资料中的图片
     *
     * @Post("/user/setting/image")
     *
     * @Parameters({
     *      @Parameter("type", description="`avatar`或`banner`", type="string", required=true),
     *      @Parameter("url", description="图片地址，不带`host`", type="string", required=true)
     * })
     *
     * @Transaction({
     *      @Request(headers={"Authorization": "Bearer JWT-Token"}),
     *      @Response(204)
     * })
     */
    public function image(Request $request)
    {
        $userId = $this->getAuthUserId();
        $key = $request->get('type');

        if (!in_array($key, ['avatar', 'banner']))
        {
            return $this->resErrBad();
        }

        $val = $request->get('url');

        User::where('id', $userId)->update([
            $key => $val
        ]);

        Redis::DEL('user_'.$userId);

        $job = (new \App\Jobs\Trial\User\Image($userId, $key));
        dispatch($job);

        return $this->resNoContent();
    }

    /**
     * 用户详情
     *
     * @Get("/user/`zone`/show")
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "用户信息对象"}),
     *      @Response(404, body={"code": 40401, "message": "该用户不存在"})
     * })
     */
    public function show($zone)
    {
        $userRepository = new UserRepository();
        $userId = $userRepository->getUserIdByZone($zone);
        if (is_null($userId))
        {
            return $this->resErrNotFound('该用户不存在');
        }

        $userTransformer = new UserTransformer();
        $user = $userRepository->item($userId, true);
        if (is_null($user))
        {
            return $this->resErrNotFound('该用户不存在');
        }

        if ($user['deleted_at'])
        {
            if ($user['state'])
            {
                return $this->resErrLocked();
            }

            return $this->resErrNotFound('该用户不存在');
        }

        $searchService = new Search();
        if ($searchService->checkNeedMigrate('user', $userId))
        {
            $job = (new \App\Jobs\Search\UpdateWeight('user', $userId));
            dispatch($job);
        }

        return $this->resOK($userTransformer->show($user));
    }

    // TODO：API Doc
    public function profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sex' => 'required',
            'signature' => 'string|min:0|max:150',
            'nickname' => 'required|min:1|max:14',
            'birth_secret' => 'required|boolean',
            'sex_secret' => 'required|boolean'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $userId = $this->getAuthUserId();
        $birthday = date('Y-m-d H:m:s', (int)$request->get('birthday'));

        User::where('id', $userId)->update([
            'nickname' => Purifier::clean($request->get('nickname')),
            'signature' => Purifier::clean($request->get('signature')),
            'sex' => $request->get('sex'),
            'sex_secret' => $request->get('sex_secret'),
            'birthday' => $birthday ? $birthday : null,
            'birth_secret' => $request->get('birth_secret')
        ]);

        Redis::DEL('user_'.$userId);

        $job = (new \App\Jobs\Trial\User\Text($userId));
        dispatch($job);

        return $this->resNoContent();
    }

    /**
     * 用户关注的番剧列表
     *
     * @Get("/user/`zone`/followed/bangumi")
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "番剧列表"}),
     *      @Response(404, body={"code": 40401, "message": "该用户不存在"})
     * })
     */
    public function followedBangumis($zone)
    {
        $userRepository = new UserRepository();
        $userId = $userRepository->getUserIdByZone($zone);

        if (!$userId)
        {
            return $this->resErrNotFound('该用户不存在');
        }

        $bangumis = $userRepository->followedBangumis($userId);

        return $this->resOK($bangumis);
    }

    /**
     * 用户回复的帖子列表
     *
     * @Get("/user/`zone`/posts/reply")
     *
     * @Transaction({
     *      @Request({"minId": "看过的帖子列表里，id 最小的那个帖子的 id"}),
     *      @Response(200, body={"code": 0, "data": "帖子列表"}),
     *      @Response(404, body={"code": 40401, "message": "找不到用户"})
     * })
     */
    public function postsOfReply(Request $request, $zone)
    {
        $userRepository = new UserRepository();
        $userId = $userRepository->getUserIdByZone($zone);
        if (!$userId)
        {
            return $this->resErrNotFound('找不到用户');
        }

        $ids = $userRepository->replyPostIds($userId);
        if (empty($ids))
        {
            return $this->resOK([
                'list' => [],
                'total' => 0,
                'noMore' => true
            ]);
        }

        $page = $request->get('page') ?: 0;
        $take = 10;
        $idsObject = $this->filterIdsByPage($ids, $page, $take);

        $data = [];
        foreach ($idsObject['ids'] as $id)
        {
            $data[] = $userRepository->replyPostItem($userId, $id);
        }

        $result = [];
        foreach ($data as $item)
        {
            if ($item)
            {
                $result[] = $item;
            }
        }

        return $this->resOK([
            'list' => $result,
            'total' => $idsObject['total'],
            'noMore' => $idsObject['noMore']
        ]);
    }

    /**
     * 用户反馈
     *
     * @Post("/user/feedback")
     *
     * @Transaction({
     *      @Request({"type": "反馈的类型", "desc": "反馈内容，最多120字"}),
     *      @Response(204),
     *      @Response(400, body={"code": 40003, "message": "请求参数错误"})
     * })
     */
    public function feedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|integer',
            'desc' => 'required|max:120',
            'ua' => 'required'
        ]);

        if ($validator->fails())
        {
            return $this->resErrParams($validator);
        }

        $userId = $this->getAuthUserId();

        Feedback::create([
            'type' => $request->get('type'),
            'desc' => $request->get('desc'),
            'ua' => $request->get('ua'),
            'user_id' => $userId
        ]);

        return $this->resNoContent();
    }

    /**
     * 用户消息列表
     *
     * @Get("/user/notifications/list")
     *
     * @Transaction({
     *      @Request({"minId": "看过的最小id"}),
     *      @Request(headers={"Authorization": "Bearer JWT-Token"}),
     *      @Response(200, body={"code": 0, "data": "消息列表"}),
     *      @Response(401, body={"code": 40104, "message": "未登录的用户"})
     * })
     */
    public function notifications(Request $request)
    {
        $userId = $this->getAuthUserId();
        $minId = $request->get('minId') ?: 0;
        $take = 10;

        $repository = new UserRepository();

        return $this->resOK($repository->getNotifications($userId, $minId, $take));
    }

    /**
     * 用户未读消息个数
     *
     * @Get("/user/notifications/count")
     *
     * @Transaction({
     *      @Response(200, body={"code": 0, "data": "未读个数"}),
     *      @Response(401, body={"code": 40104, "message": "未登录的用户"})
     * })
     */
    public function waitingReadNotifications()
    {
        $repository = new UserRepository();
        $count = $repository->getNotificationCount($this->getAuthUserId());

        return $this->resOK($count);
    }

    /**
     * 读取某条消息
     *
     * @Post("/user/notifications/read")
     *
     * @Transaction({
     *      @Request({"id": "消息id"}),
     *      @Request(headers={"Authorization": "Bearer JWT-Token"}),
     *      @Response(204)
     * })
     */
    public function readNotification(Request $request)
    {
        $id = $request->get('id');
        $notification = Notifications::find($id);

        if (is_null($notification))
        {
            return $this->resNoContent();
        }

        if (intval($notification['to_user_id']) !== $this->getAuthUserId())
        {
            return $this->resNoContent();
        }

        Notifications::where('id', $id)->update([
            'checked' => true
        ]);

        Redis::DEL('notification-' . $id);

        return $this->resNoContent();
    }

    /**
     * 清空未读消息
     *
     * @Post("/user/notifications/clear")
     *
     * @Transaction({
     *      @Request(headers={"Authorization": "Bearer JWT-Token"}),
     *      @Response(204)
     * })
     */
    public function clearNotification()
    {
        $userId = $this->getAuthUserId();

        Notifications
        ::where('to_user_id', $userId)
        ->update([
            'checked' => true
        ]);

        Redis::DEL('user-' . $userId . '-notification-ids');

        return $this->resNoContent();
    }

    public function fakers()
    {
        $users = User::withTrashed()
            ->where('faker', 1)
            ->orderBy('id', 'DESC')
            ->get();

        return $this->resOK($users);
    }

    public function fakerReborn(Request $request)
    {
        $phone = $request->get('phone');

        $count = User::withTrashed()->where('phone', $phone)->count();
        if ($count)
        {
            return $this->resErrBad('手机号已被占用');
        }

        $userId = $request->get('id');
        User::where('id', $userId)
            ->update([
                'phone' => $phone,
                'faker' => 0
            ]);

        Redis::DEL('user_' . $userId);

        return $this->resNoContent();
    }

    public function coinDescList(Request $request)
    {
        $curPage = $request->get('cur_page') ?: 0;
        $toPage = $request->get('to_page') ?: 1;
        $take = $request->get('take') ?: 10;

        $list = User::orderBy('coin_count', 'DESC')
            ->select('nickname', 'id', 'zone', 'coin_count', 'faker')
            ->take(($toPage - $curPage) * $take)
            ->skip($curPage * $take)
            ->get();

        $totalUserCount = new TotalUserCount();

        return $this->resOK([
            'list' => $list,
            'total' => $totalUserCount->get()
        ]);
    }

    public function addUserToTrial(Request $request)
    {
        $userId = $request->get('id');

        $user = User::find($userId);

        if (is_null($user))
        {
            $this->resErrNotFound('不存在的用户');
        }

        User::where('id', $userId)
            ->update([
                'state' => 1
            ]);

        return $this->resNoContent();
    }

    public function feedbackList()
    {
        $list = Feedback::where('stage', 0)->get();

        return $this->resOK($list);
    }

    public function readFeedback(Request $request)
    {
        Feedback::where('id', $request->get('id'))->update([
            'stage' => 1
        ]);

        return $this->resNoContent();
    }

    public function adminUsers()
    {
        $users = User::where('is_admin', 1)
            ->select('id', 'zone', 'nickname')
            ->get();

        return $this->resOK($users);
    }

    public function removeAdmin(Request $request)
    {
        $userId = $this->getAuthUserId();
        $id = $request->get('id');

        if (intval($id) === 1 || $userId !== 1)
        {
            return $this->resErrRole();
        }

        User::whereRaw('id = ? and is_admin = 1', [$id])
            ->update([
                'is_admin' => 0
            ]);

        return $this->resNoContent();
    }

    public function addAdmin(Request $request)
    {
        $userId = $this->getAuthUserId();

        if ($userId !== 1)
        {
            return $this->resErrRole();
        }

        User::whereRaw('id = ? and is_admin = 0', [$request->get('id')])
            ->update([
                'is_admin' => 1
            ]);

        return $this->resNoContent();
    }

    public function getUserCoinTransactions(Request $request)
    {
        $curPage = $request->get('cur_page') ?: 0;
        $toPage = $request->get('to_page') ?: 1;
        $take = $request->get('take') ?: 10;
        $userId = $request->get('id');

        $list = DB::table('user_coin')
            ->where('user_id', $userId)
            ->orWhere('from_user_id', $userId)
            ->select('id', 'created_at', 'from_user_id', 'user_id', 'type', 'type_id', 'id', 'count')
            ->orderBy('created_at', 'DESC')
            ->take(($toPage - $curPage) * $take)
            ->skip($curPage * $take)
            ->get();

        $userRepository = new UserRepository();
        $result = [];
        foreach ($list as $item)
        {
            $transaction = [
                'id' => $item->id,
                'type' => '',
                'action' => '',
                'count' => $item->count,
                'action_id' => $item->type_id,
                'created_at' => $item->created_at,
                'about_user_id' => '无',
                'about_user_phone' => '无',
                'about_user_sign_at' => '无'
            ];
            if ($item->type == 0)
            {
                $transaction['type'] = '收入';
                $transaction['action'] = '每日签到';
            }
            else if ($item->type == 1)
            {
                $transaction['action'] = '打赏帖子';
                if ($item->from_user_id == $userId)
                {
                    $transaction['type'] = '支出';
                }
                else
                {
                    $transaction['type'] = '收入';
                }
            }
            else if ($item->type == 2)
            {
                $transaction['action'] = '邀请注册';
                $transaction['type'] = '收入';
            }
            else if ($item->type == 3)
            {
                $transaction['action'] = '偶像应援';
                $transaction['type'] = '支出';
            }
            else if ($item->type == 4)
            {
                $transaction['action'] = '打赏图片';
                if ($item->from_user_id == $userId)
                {
                    $transaction['type'] = '支出';
                }
                else
                {
                    $transaction['type'] = '收入';
                }
            }
            else if ($item->type == 5)
            {
                $transaction['action'] = '提现';
                $transaction['type'] = '支出';
            }

            if ($transaction['type'] === '收入' && $item->from_user_id != 0 && $item->from_user_id != $userId)
            {
                $user = $userRepository->item($item->from_user_id);
                $transaction['about_user_id'] = $user['id'];
                $transaction['about_user_phone'] = $user['phone'];
                $transaction['about_user_sign_at'] = $user['created_at'];
            }
            if ($transaction['type'] === '支出' && $item->user_id != 0)
            {
                $user = $userRepository->item($item->user_id);
                $transaction['about_user_id'] = $user['id'];
                $transaction['about_user_phone'] = $user['phone'];
                $transaction['about_user_sign_at'] = $user['created_at'];
            }

            $result[] = $transaction;
        }

        return $this->resOK([
            'list' => $result,
            'total' => UserCoin::where('user_id', $userId)->orWhere('from_user_id', $userId)->count()
        ]);
    }

    public function withdrawal(Request $request)
    {
        $adminId = $this->getAuthUserId();
        if ($adminId !== 1)
        {
            return $this->resErrRole();
        }

        $userId = $request->get('id');
        $coinCount = User::where('id', $userId)
            ->pluck('coin_count')
            ->first();

        if ($coinCount < 100)
        {
            return $this->resErrBad('未满100金币');
        }

        $money = $request->get('money');
        if ($money > $coinCount)
        {
            return $this->resErrBad('超出拥有金额');
        }

        User::where('id', $userId)->increment('coin_count', -$money);
        UserCoin::create([
            'from_user_id' => 0,
            'user_id' => $userId,
            'type' => 5,
            'count' => $money
        ]);

        return $this->resNoContent();
    }

    public function trials()
    {
        $users = User
            ::withTrashed()
            ->where('state', '<>', 0)
            ->orderBy('updated_at', 'DESC')
            ->get();

        return $this->resOK($users);
    }

    public function ban(Request $request)
    {
        $userId = $request->get('id');
        DB::table('users')
            ->where('id', $userId)
            ->update([
                'state' => 0,
                'deleted_at' => Carbon::now()
            ]);

        $job = (new \App\Jobs\Search\Index('D', 'user', $userId));
        dispatch($job);

        Redis::DEL('user_' . $userId);

        return $this->resNoContent();
    }

    public function pass(Request $request)
    {
        User::where('id', $request->get('id'))->update([
            'state' => 0
        ]);

        return $this->resNoContent();
    }

    public function recover(Request $request)
    {
        $userId = $request->get('id');
        $userRepository = new UserRepository();
        $user = $userRepository->item($userId, true);
        if (is_null($user))
        {
            return $this->resErrNotFound();
        }

        User::withTrashed()->where('id', $userId)->restore();

        $userRepository->migrateSearchIndex('C', $userId);

        return $this->resNoContent();
    }

    public function deleteUserInfo(Request $request)
    {
        $userId = $request->get('id');
        User::where('id', $userId)
            ->update([
                $request->get('key') => $request->get('value') ?: ''
            ]);

        return $this->resNoContent();
    }
}