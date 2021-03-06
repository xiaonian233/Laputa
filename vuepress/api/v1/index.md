FORMAT: 1A

# calibur.tv API docs

# 搜索相关接口

## 根据关键字搜索番剧 [GET /search/index]


+ Parameters
    + q: (string, required) - 关键字

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "番剧的 url"
            }

# 用户认证相关接口

## 发送手机验证码 [POST /door/message]
> 一个通用的接口，通过 `type` 和 `phone_number` 发送手机验证码.
目前支持 `type` 为：
1. `sign_up`，注册时调用
2. `forgot_password`，找回密码时使用

> 目前返回的数字验证码是`6位`

+ Parameters
    + type: (string, required) - 上面的某种type
    + phone_number: (number, required) - 只支持`11位`的手机号
    + geetest: (object, required) - Geetest认证对象

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "短信已发送"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40001,
                "message": "未经过图形验证码认证"
            }

+ Response 401 (application/json)
    + Body

            {
                "code": 40100,
                "message": "图形验证码认证失败"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "各种错误"
            }

+ Response 503 (application/json)
    + Body

            {
                "code": 50310,
                "message": "短信服务暂不可用或请求过于频繁"
            }

## 用户注册 [POST /door/register]
目前仅支持使用手机号注册

+ Parameters
    + access: (number, required) - 手机号
    + secret: (string, required) - `6至16位`的密码
    + nickname: (string, required) - 昵称，只能包含`汉字、数字和字母，2~14个字符组成，1个汉字占2个字符`
    + authCode: (number, required) - 6位数字的短信验证码

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "JWT-Token"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "各种错误"
            }

## 用户登录 [POST /door/login]
目前仅支持手机号和密码登录

+ Parameters
    + access: (number, required) - 手机号
    + secret: (string, required) - 6至16位的密码
    + geetest: (object, required) - Geetest认证对象

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "JWT-Token"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40001,
                "message": "未经过图形验证码认证"
            }

+ Response 401 (application/json)
    + Body

            {
                "code": 40100,
                "message": "图形验证码认证失败"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "各种错误"
            }

## 用户登出 [POST /door/logout]


+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

## 获取用户信息 [POST /door/user]
每次`启动应用`、`登录`、`注册`成功后调用

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "用户对象"
            }

+ Response 401 (application/json)
    + Body

            {
                "code": 40104,
                "message": "未登录的用户"
            }

## 重置密码 [POST /door/reset]


+ Parameters
    + access: (number, required) - 手机号
    + secret: (string, required) - 6至16位的密码
    + authCode: (number, required) - 6位数字的短信验证码

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "密码重置成功"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40001,
                "message": "未经过图形验证码认证"
            }

+ Response 401 (application/json)
    + Body

            {
                "code": 40100,
                "message": "图形验证码认证失败"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "各种错误"
            }

# 番剧相关接口

## 番剧时间轴 [GET /bangumi/timeline]


+ Parameters
    + year: (integer, required) - 从哪一年开始获取

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "list": "番剧列表",
                    "noMore": "没有更多了"
                }
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "参数错误"
            }

## 新番列表（周） [GET /bangumi/released]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "番剧列表"
            }

## 所有的番剧标签 [GET /bangumi/tags]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "标签列表"
            }

## 根据标签获取番剧列表 [GET /bangumi/category]


+ Parameters
    + id: (string, required) - 选中的标签id，`用 - 链接的字符串`
    + page: (integer, required) - 页码
        + Default: 0

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "list": "番剧列表",
                    "total": "该标签下番剧的总数",
                    "noMore": "是否没有更多了"
                }
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误"
            }

## 番剧详情 [GET /bangumi/`bangumiId`/show]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "番剧信息"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的番剧"
            }

## 番剧视频 [GET /bangumi/`bangumiId`/videos]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "videos": "视频列表",
                    "has_season": "是否有多个季度",
                    "total": "视频总数"
                }
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的番剧"
            }

## 吧主编辑番剧信息 [POST /bangumi/`bangumiId`/edit]


+ Parameters
    + avatar: (string, required) - 封面图链接，不包含 host
    + banner: (string, required) - 背景图链接，不包含 host
    + summary: (string, required) - 200字以内的纯文本
    + tags: (array, required) - 标签的id数组

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误或内容非法"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "权限不足"
            }

+ Response 503 (application/json)
    + Body

            {
                "50301": 40301,
                "message": "服务暂不可用"
            }

# 帖子相关接口

## 新建帖子 [POST /post/create]
> 图片对象示例：
1. `key` 七牛传图后得到的 key，不包含图片地址的 host，如一张图片 image.calibur.tv/user/1/avatar.png，七牛返回的 key 是：user/1/avatar.png，将这个 key 传到后端
2. `width` 图片的宽度，七牛上传图片后得到
3. `height` 图片的高度，七牛上传图片后得到
4. `size` 图片的尺寸，七牛上传图片后得到
5. `type` 图片的类型，七牛上传图片后得到

+ Parameters
    + bangumiId: (integer, required) - 所选的番剧 id
    + title: (string, required) - 标题`40字以内`
    + desc: (string, required) - content可能是富文本，desc是`120字以内的纯文本`
    + content: (string, required) - 内容，`1000字以内`
    + images: (array, required) - 图片对象数组

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "帖子id"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误"
            }

## 帖子详情 [GET /post/`postId`/show]


+ Parameters
    + only: (integer, optional) - 是否只看楼主
        + Default: 0

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "bangumi": "番剧信息",
                    "user": "作者信息",
                    "post": "帖子信息"
                }
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "帖子不存在/番剧不存在/作者不存在"
            }

## 删除帖子 [POST /post/`postId`/deletePost]


+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 401 (application/json)
    + Body

            {
                "code": 40104,
                "message": "未登录的用户"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "权限不足"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的帖子"
            }

# 图片相关接口

## 获取首页banner图 [GET /image/banner]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "图片列表"
            }

## 获取 Geetest 验证码 [GET /image/captcha]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "success": "数字0或1",
                    "gt": "Geetest.gt",
                    "challenge": "Geetest.challenge",
                    "payload": "字符串荷载"
                }
            }

## 获取图片上传token [GET /image/uptoken]


+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "upToken": "上传图片的token",
                    "expiredAt": "token过期时的时间戳，单位为s"
                }
            }

+ Response 401 (application/json)
    + Body

            {
                "code": 40104,
                "message": "未登录的用户"
            }

## 新建相册 [POST /image/album/create]


+ Parameters
    + bangumi_id: (integer, required) - 所选的番剧 id
    + name: (string, required) - 相册名称`30字以内`
    + is_cartoon: (boolean, required) - 是不是漫画（只有吧主/管理员才能上传漫画）
    + is_creator: (boolean, required) - 是不是原唱（漫画默认都不是原创）
    + url: (string, required) - 封面图片链接，不包含 host
    + width: (integer, required) - 图片宽度
    + height: (integer, required) - 图片高度
    + size: (string, required) - 图片尺寸
    + type: (string, required) - 图片类型
    + part: (integer, required) - 漫画是第几集，非漫画传0

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "相册对象"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误，或这一集的漫画已存在，或图片非法"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "权限不足（漫画）"
            }

## 编辑相册 [POST /image/album/edit]


+ Parameters
    + id: (integer, required) - 相册 id
    + name: (string, required) - 相册名称`30字以内`
    + url: (string, required) - 封面图片链接，不包含 host
    + width: (integer, required) - 图片宽度
    + height: (integer, required) - 图片高度
    + size: (string, required) - 图片尺寸
    + type: (string, required) - 图片类型
    + part: (integer, required) - 漫画是第几集，非漫画传0

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "相册对象"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误，或这一集的漫画已存在，或图片非法"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "权限不足（漫画）"
            }

## 上传单张图片 [POST /image/single/upload]


+ Parameters
    + bangumi_id: (integer, required) - 所选的番剧 id
    + name: (string, required) - 相册名称`30字以内`
    + is_creator: (boolean, required) - 是不是原唱（漫画默认都不是原创）
    + url: (string, required) - 封面图片链接，不包含 host
    + width: (integer, required) - 图片宽度
    + height: (integer, required) - 图片高度
    + size: (string, required) - 图片尺寸
    + type: (string, required) - 图片类型

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "新图片 id"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误，或图片非法"
            }

## 编辑单张图片 [POST /image/single/edit]


+ Parameters
    + id: (integer, required) - 图片 id
    + bangumi_id: (integer, required) - 所选的番剧 id
    + name: (string, required) - 相册名称`30字以内`

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误，或图片非法"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "权限不足"
            }

## 上传相册内图片 [POST /image/album/upload]
> 图片对象示例：
1. `url` 七牛传图后得到的 key，不包含图片地址的 host，如一张图片 https://image.calibur.tv/user/1/avatar.png，七牛返回的 key 是：user/1/avatar.png，将这个 key 传到后端
2. `width` 图片的宽度，七牛上传图片后得到
3. `height` 图片的高度，七牛上传图片后得到
4. `size` 图片的尺寸，七牛上传图片后得到
5. `type` 图片的类型，七牛上传图片后得到

+ Parameters
    + album_id: (integer, required) - 相册 id
    + images: (array, required) - 图片对象数组

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "新图片 id"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "权限不足，比如不是吧主，却修改漫画"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "相册不存在"
            }

## 自己的相册列表 [GET /image/album/users]


+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "相册数组"
            }

## 用户的相册列表 [GET /image/users]


+ Parameters
    + zone: (string, required) - 用户的空间地址
    + page: (integer, required) - 页数
        + Default: 0

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "list": "相册列表",
                    "total": "总数",
                    "noMore": "是否还有更多"
                }
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "用户不存在"
            }

## 自己的相册列表 [POST /image/album/delete]


+ Parameters
    + id: (integer, required) - 相册 id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "权限不足"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "相册不存在"
            }

## 用户的相册列表 [GET /image/${image_id}/show]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "相册页面信息"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "图片不存在"
            }

## 番剧漫画列表 [GET /bangumi/${bangumi_id}/cartoon]


+ Parameters
    + take: (integer, required) - 取的格式
        + Default: 12
    + page: (integer, required) - 页数
        + Default: 0
    + sort: (string, required) - 升降序，desc 或者 asc
        + Default: desc

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "list": "漫画列表",
                    "total": "总数",
                    "noMore": "是否还有更多"
                }
            }

## 相册内图片的排序 [POST /image/album/`albumId`/sort]


+ Parameters
    + result: (string, required) - 相册内图片排序后的`ids`，用`,`拼接的字符串

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "data": "参数错误"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的相册"
            }

## 删除相册里的图片 [POST /image/album/`albumId`/deleteImage]


+ Parameters
    + result: (string, required) - 相册内图片排序后的`ids`，用`,`拼接的字符串
    + imageId: (integer, required) - 要删除的图片id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "data": "请求参数错误"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的相册，或要删除的图片已经被删除"
            }

# 动漫角色相关接口

## 番剧角色列表 [GET /bangumi/`bangumiId`/roles]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "角色列表"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的番剧"
            }

## 给角色应援 [POST /cartoon_role/`roleId`/star]


+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的角色"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "没有足够的金币"
            }

## 角色的粉丝列表 [POST /cartoon_role/`roleId`/fans]


## 角色详情 [GET /cartoon_role/`roleId`/show]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "bangumi": "番剧简介",
                    "data": "角色信息"
                }
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的角色"
            }

# 视频相关接口

## 获取视频资源 [GET /video/${videoId}/show]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "info": "视频对象",
                    "bangumi": "番剧信息",
                    "list": {
                        "total": "视频总数",
                        "repeat": "是否重排",
                        "videos": "视频列表"
                    }
                }
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的视频资源"
            }

## 记录视频播放信息 [GET /video/${videoId}/playing]


+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

# 用户相关接口

## 用户每日签到 [POST /user/daySign]


+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 401 (application/json)
    + Body

            {
                "code": 40104,
                "message": "未登录的用户"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "今日已签到"
            }

## 更新用户资料中的图片 [POST /user/setting/image]


+ Parameters
    + type: (string, required) - `avatar`或`banner`
    + url: (string, required) - 图片地址，不带`host`

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

## 用户详情 [GET /user/`zone`/show]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "用户信息对象"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "该用户不存在"
            }

## 用户关注的番剧列表 [GET /user/`zone`/followed/bangumi]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "番剧列表"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "该用户不存在"
            }

## 用户发布的帖子列表 [GET /user/`zone`/posts/mine]


+ Parameters
    + page: (integer, required) - 页码
        + Default: 0

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "帖子列表"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "找不到用户"
            }

## 用户回复的帖子列表 [GET /user/`zone`/posts/reply]


+ Request (application/json)
    + Body

            {
                "minId": "看过的帖子列表里，id 最小的那个帖子的 id"
            }

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "帖子列表"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "找不到用户"
            }

## 用户喜欢的帖子列表 [GET /user/`zone`/posts/like]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "帖子列表"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "找不到用户"
            }

## 用户收藏的帖子列表 [GET /user/`zone`/posts/mark]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "帖子列表"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "找不到用户"
            }

## 用户反馈 [POST /user/feedback]


+ Request (application/json)
    + Body

            {
                "type": "反馈的类型",
                "desc": "反馈内容，最多120字"
            }

+ Response 204 (application/json)

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误"
            }

## 用户消息列表 [GET /user/notifications/list]


+ Request (application/json)
    + Body

            {
                "minId": "看过的最小id"
            }

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "消息列表"
            }

+ Response 401 (application/json)
    + Body

            {
                "code": 40104,
                "message": "未登录的用户"
            }

## 用户未读消息个数 [GET /user/notifications/count]


+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": "未读个数"
            }

+ Response 401 (application/json)
    + Body

            {
                "code": 40104,
                "message": "未登录的用户"
            }

## 读取某条消息 [POST /user/notifications/read]


+ Request (application/json)
    + Body

            {
                "id": "消息id"
            }

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

## 清空未读消息 [POST /user/notifications/clear]


+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

## 用户应援的角色列表 [GET /user/`zone`/followed/role]


+ Parameters
    + page: (integer, required) - 页码
        + Default: 0

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "list": "角色列表",
                    "total": "总数",
                    "noMore": "没有更多"
                }
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "该用户不存在"
            }

# 评论相关接口

## 新建主评论 [POST /comment/create]


+ Parameters
    + content: (string, required) - 内容，`1000字以内`
    + images: (array, required) - 图片对象数组
    + type: (string, required) - 某个 type
    + id: (integer, required) - 评论主题的 id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "主评论对象"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误"
            }

## 获取主评论列表 [GET /comment/main/list]


+ Parameters
    + type: (string, required) - 某个 type
    + id: (integer, required) - 如果是帖子，则是帖子id
    + fetchId: (integer, required) - 你通过这个接口获取的评论列表里最后的那个id
        + Default: 0

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "list": "主评论列表",
                    "total": "总数",
                    "noMore": "没有更多了"
                }
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误"
            }

## 子评论列表 [GET /comment/sub/list]
> 一个通用的接口，通过 `type` 和 `commentId` 来获取子评论列表.

> `commentId`是父评论的 id：
1. `父评论` 一部视频下的评论列表，列表中的每一个就是一个父评论
2. `子评论` 每个父评论都有回复列表，这个回复列表中的每一个就是子评论

+ Parameters
    + type: (string, required) - 某种 type
    + id: (integer, required) - 父评论 id
    + maxId: (integer, required) - 该父评论下看过的最大的子评论 id
        + Default: 0

+ Response 200 (application/json)
    + Body

            {
                "code": 0,
                "data": {
                    "list": "评论列表",
                    "total": "评论总数",
                    "noMore": "没有更多了"
                }
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错误"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "不存在的父评论"
            }

## 回复评论 [POST /comment/reply]


+ Parameters
    + type: (string, required) - 某种 type
    + id: (integer, required) - 父评论 id
    + targetUserId: (integer, required) - 父评论的用户 id
    + content: (string, required) - 评论内容，`纯文本，100字以内`

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "子评论对象"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "参数错误"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "内容已删除"
            }

## 删除子评论 [POST /comment/sub/delete]


+ Parameters
    + type: (string, required) - 某种 type
    + id: (integer, required) - 父评论 id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "参数错误"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "该评论已被删除"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "继续操作前请先登录"
            }

## 删除主评论 [POST /comment/main/delete]


+ Parameters
    + type: (string, required) - 某种 type
    + id: (integer, required) - 父评论 id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 204 (application/json)

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "参数错误"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "该评论已被删除"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "继续操作前请先登录"
            }

## <喜欢/取消喜欢>主评论 [POST /comment/main/toggleLike]


+ Parameters
    + type: (string, required) - 某种 type
    + id: (integer, required) - 父评论 id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "是否已喜欢"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "参数错误"
            }

## <喜欢/取消喜欢>子评论 [POST /comment/sub/toggleLike]


+ Parameters
    + type: (string, required) - 某种 type
    + id: (integer, required) - 父评论 id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 201 (application/json)
    + Body

            {
                "code": 0,
                "data": "是否已喜欢"
            }

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "参数错误"
            }

# 用户社交点击相关接口

## 检查toggle状态 [POST /toggle/{type}/check]
> 目前支持的 type：like, follow
如果是 like，modal 支持：post、image、score
如果是 follow，modal 支持：bangumi

+ Parameters
    + modal: (string, required) - 要检测的模型
    + id: (integer, required) - 要检测的id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            "一个boolean值"

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错"
            }

## 获取发起操作的用户列表 [GET /toggle/{type}/users]
> 目前支持的 type：like, follow，contributors
如果是 like，modal 支持：post、image、score
如果是 follow，modal 支持：bangumi
如果是 contributors，bangumi 支持：bangumi（就是吧主列表）

+ Parameters
    + modal: (string, required) - 要请求的模型
    + id: (integer, required) - 要请求的id
    + page: (integer, required) - 页码
        + Default: 0
    + take: (integer, optional) - 获取的个数
        + Default: 10

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            "一个boolean值"

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错"
            }

## 喜欢或取消喜欢 [POST /toggle/like]
> 目前支持的 type：post、image、score

+ Parameters
    + type: (string, required) - 要请求的类型
    + id: (integer, required) - 要请求的id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            "一个boolean值"

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40303,
                "message": "原创内容只能打赏，不能喜欢 | 不能喜欢自己的内容"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "检测的对象不存在"
            }

## 关注或取消关注 [POST /toggle/follow]
> 目前支持的 type：bangumi

+ Parameters
    + type: (string, required) - 要请求的类型
    + id: (integer, required) - 要请求的id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            "一个boolean值"

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "吧主不能取消关注"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "检测的对象不存在"
            }

## 收藏或取消收藏 [POST /toggle/mark]
> 目前支持的 type：post、image、score

+ Parameters
    + type: (string, required) - 要请求的类型
    + id: (integer, required) - 要请求的id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            "一个boolean值"

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40301,
                "message": "不能收藏自己的内容"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "检测的对象不存在"
            }

## 打赏或取消打赏 [POST /toggle/reward]
> 目前支持的 type：post、image、score

+ Parameters
    + type: (string, required) - 要请求的类型
    + id: (integer, required) - 要请求的id

+ Request (application/json)
    + Headers

            Authorization: Bearer JWT-Token

+ Response 200 (application/json)
    + Body

            "一个boolean值"

+ Response 400 (application/json)
    + Body

            {
                "code": 40003,
                "message": "请求参数错 | 不支持该类型内容的打赏"
            }

+ Response 403 (application/json)
    + Body

            {
                "code": 40303,
                "message": "非原创内容只能喜欢，不能打赏 | 金币不足 | 未打赏过 | 不能打赏自己的内容"
            }

+ Response 404 (application/json)
    + Body

            {
                "code": 40401,
                "message": "检测的对象不存在"
            }