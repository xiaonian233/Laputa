## calibur.tv社区系统的详细介绍

### 目录大纲
0. 前言
1. 我们的功能与竞品是什么
2. 我们这样做的好处是什么
3. 为什么竞品不做我们做的事
4. 为什么是这几个功能，而不是其它功能，这些功能是不是伪需求
5. 如何管理这么多的功能
6. 这样的模式，是否有成功的先例

### 前言：
1. 任何一个互联网产品，将其功能属性进行归类，都可以分成：社交属性和工具属性两种
    - QQ、微信、微博 的主要属性是社交（链接人与人之间的媒介），次要属性是工具
    - 美颜相机、支付宝、优酷、爱奇艺...基本只有工具属性，没有社交价值
2. 一个只有工具属性的产品，盈利途径是贫乏的，如：
    - **【向社交转型】**：超级课程表作为一个工具软件，为了盈利，强行加入社交元素，导致产品死掉
    - **【接商业广告】**：如一些文件管理器、垃圾清理工具...虽然能够盈利，但因为广告破坏用户体验，用户会慢慢流失
    - **【靠技术壁垒】**：依靠技术壁垒盈利的产品，最终只会被大公司（腾讯、阿里、小米）低价收购，或被大公司的入局后的价格战淘汰，除非你足够有钱（抖音、快手）
    - **【靠资源壁垒】**：如优酷、爱奇艺、腾讯动漫这种产品，只能靠版权资源来维持，需要大量的资本投入以及一个好的推广平台（优酷被阿里收购，爱奇艺被百度收购，腾讯动漫就是腾讯的产品）
    - 因此：一个没有技术壁垒，没有资本背景的工具属性的产品，如果没有发展成社交的可能（超级手电筒、美颜相机）那就只能植入广告来快速变现，如果有发展成社交的可能（超级课程表）那么最长远的发展方向就是向社交转型
3. 一个只有社交属性的产品，在中国已经没有很好的机会了：
    - 社交的入口，只有：QQ、微信
    - 想要在社交上突破，要么是垂直社交，要么是基于场景的社交（或者可以说是基于工具基础的社交）
        >垂直社交：由于QQ、微信等产品面向的用户量太庞大，所以无法精确的为每一个小群体来提供特异性的功能，因此专门为某个小群体提供特异性功能的产品就可以发展起来，比如说：
            - 一大堆的相亲平台，兴趣社交平台 都是这样的，他们的盈利模式也只有：卖会员、送礼物、接商业广告
            - 这样的产品，如果没有工具属性作为支撑，基本不可能做大的

        >工具社交：具有一定特异功能的社交产品，如：
            - **微博**：基于新闻媒体的社交，当年战胜了**腾讯微博**后活了下来
            - **知乎**：基于专业问答的社交，知乎能发展起来，完全是因为**百度知道**和**腾讯问问**在竞争，但这两个都不是公司的主营产品，所以没有认真发展
4. 一个兼具社交属性与工具属性的产品，能够发展壮大：
    - 如上面的知乎、微博都是在一定的工具基础上发展出来的社交，然后慢慢壮大
    - 但也有从工具转型成社交失败的例子，比如[超级课程表](https://www.zhihu.com/question/31866977)，比如之前的[支付宝](https://www.zhihu.com/question/53096041)
5. 总结一下就是工具属性和社交属性如何找方向，未来的发展是什么，说的再多也都是一样的逻辑。

### 我们的功能与竞品：
1. [番剧版块](https://m.calibur.tv/bangumi/33)，对标 [bilibili](https://www.bilibili.com/bangumi/media/md1650/?from=search&seid=9840610067289015812)，附 [番剧](https://baike.baidu.com/item/%E7%95%AA%E5%89%A7/17528517?fr=aladdin) 的解释
2. [帖子功能](https://www.calibur.tv/world/post), 对标 [百度贴吧](https://tieba.baidu.com/f?kw=calibur)
3. [图片功能](https://www.calibur.tv/world/image)，对标 [pixiv](https://www.pixiv.net/)
4. [漫评功能](https://www.calibur.tv/world/review), 对标 [豆瓣电影](https://movie.douban.com/)
5. [研发中]问答功能，对标 [知乎](https://www.zhihu.com)
6. [盖楼功能](https://www.calibur.tv/role/trending)，对标 [Bilibili动画角色人气大赏](https://baike.baidu.com/item/Bilibili%E5%8A%A8%E7%94%BB%E8%A7%92%E8%89%B2%E4%BA%BA%E6%B0%94%E5%A4%A7%E8%B5%8F/19282818?fromtitle=B%E8%90%8C&fromid=19815735&fr=aladdin)、[百度贴吧盖楼](https://zhidao.baidu.com/question/324922534.html)
7. [漫画功能](https://www.calibur.tv/pins/6327)，对标 [腾讯动漫](http://ac.qq.com/ComicView/index/id/505430/cid/931)
8. [视频功能](https://www.calibur.tv/video/1001)，对标 [bilibili](https://www.bilibili.com/bangumi/play/ep13872)

### 我们这样做的好处是什么：
> 一个产品，包含了多个竞品的功能，就像一个大杂烩，这样做有什么好处吗？

1. 我个人的时间精力、技术实力、运营资本都是无法与竞品相比的，因此我们不会试图在单一功能上超越竞品
2. 各个竞品在单一功能上比我们强，用户面也更大，但也由于竞品面向的用户群体太笼统了，导致无法为某一特殊用户群体做垂直化研发，比如：
    - **百度贴吧**的用户量极大，而**动漫爱好者**仅仅是其中的一小部分，所以贴吧没有看番剧和看漫画的功能，只有社交属性没有工具属性
    - **豆瓣电影**的评分功能是针对电影而设立的，完全不适用于来给动漫打分，专业的动漫评分平台在国内其实是一个缺失
    - **知乎**的问答氛围比较好，但是主要用户年龄段在20岁以上，这些用户已经过了感性的去看动漫的年龄，因此对那些在小孩子（15岁左右）喜欢的作品无法友好的讨论
3. 工具属性上，我们无法胜过[bilibili](https://www.bilibili.com/)，事实上现阶段没有哪家视频为主的公司能胜过bilibili，因此我们想在社交属性上取得突破
4. 社交属性上，国内还没有一个**氛围良好**的为**动漫爱好者**垂直开发的社交平台
    - **acfun**已经完了
    - **bilibili**是一个强工具属性的产品，并且也在努力向社交转型，但它做的并不够好，我们会在后面详细讲述
    - **第一弹**是靠着色情内容起家的产品，目前已经通过游戏运营实现了盈利，社区靠着极个别高质量的用户撑着，该产品涉及数据造假，因此称不上一个强劲的竞争对手
    - 其它竞品如**半次元**等并没有很强的社交属性
5. 因此，我们的逻辑是：通过开发竞品的版块，实现其基本的功能，满足竞品用户的基本需求，然后通过看视频、漫画的工具属性以及多个版块共同营造的**社区氛围**来吸引用户，
之后我们会针对**动漫爱好者**这一群体，来优化我们的每个功能版块，留住用户。

### 为什么竞品不做我们做的事？

### 各版块之间的关系：
1. 目前网站的所有功能都是以**番剧系统**和**用户系统**来拓展的
2. **番剧版块**是[ACGN](https://baike.baidu.com/item/ACGN/194297?fr=aladdin)文化的承载基础，目前它已经承载了动画（视频功能）、漫画（漫画功能）
3. **帖子功能**、**图片功能**、**评分功能**都是番剧版块的子功能，这些功能的话题目前都限制在**番剧**中

### 帖子功能介绍：
1. 目前帖子功能已经较完善，实现了竞品的很多功能：
    - 竞品有，我们也有的：帖子发布，插入图片，帖子加精、置顶
    - 竞品有，我们没有的：插入视频（不做），投票（之后会做），插入链接（待定）
    - 我们有，竞品没有的：帖子申请原创保护，原创帖子有金币激励政策（关于金币的介绍，可查看其它文件）
    - 未来我们会做的：给帖子打标签，比如：新闻资讯、剧情预测...
2. 由于发表一篇帖子的门槛极低（十几秒就可以发一篇低质量的帖子），所以帖子的职能是：
    - 为绝大多数普通用户提供表达的入口，允许用户发表低质量的内容，但必须是与动漫相关
    - 一些新闻资讯、用户的猜想、投票，引导用户在帖子版块发表
    - 将绝大多数无法发表出高质量内容的用户控制在这个版块，保证其他版块内容的高质量
3. 版块的管理：
    - 帖子的内容较杂乱，质量也偏低，因此需要用户辅助审核
    - 普通用户可以举报内容，举报之后提交到后台，如果确实违规，则删除内容
    - 吧主可以删除内容，删除之后提交到后台，如果确实违规，则给吧主加分，如果没有违规，则恢复内容，吧主扣分（目前吧主功能和计分系统以及实现，但未给吧主开通删帖的权限）
4. 未来规划：
    - 按顺序实现：标签功能、投票功能
    - 为吧主开通删帖的权限

### 图片功能介绍：
1. 图片功能已经实现了：上传单张图片、相册和漫画（只有吧主可以上传漫画）
2. 我们的竞品，只有**腾讯动漫**有漫画功能，但**腾讯动漫**不支持用户上传图片，因此这个功能同时具有社交属性和工具属性，很有竞争价值
3. 图片功能相比帖子功能门槛更高，但搬运起来却很简单，因此图片区的原创内容会相对较少一些
4. 表达途径无非是：文字、图片、视频、音频。视频和音频我们暂时不作考虑，因此图片功能也是为了弥补社区内容表达方式单一的缺陷，这同样也是其它竞品存在的缺陷
5. 我们的图片功能还有缺陷：
    - 需要支持为图片、相册绑定标签，然后通过标签来搜索图片
6. 职能：
    - 丰富用户表达的渠道，并让社区的内容更加多彩
    - 搭配金币功能吸引画师入住，提供优质的图片内容
    - 搭配金币功能吸引优质[cosplay](https://baike.baidu.com/item/cosplay/114892?fr=aladdin)内容的提供者，并为这些用户提供一定的收入（制作个人专辑杂志）
    - 如果未来能够做漫画运营业务，这个功能将会是基础
7. 管理：
    - 针对侵犯他人版权的图片，和帖子功能一样，设立举报机制即可
    - 针对非法的图片内容（涉黄、涉暴恐、政治敏感）的图片，我们已经接入了[七牛云](https://www.qiniu.com/products/dora)的图片智能识别，保证社区的安全运转
    - 我们还未对图片中的二维码、超链接进行分析，之后需要完善
8. 未来规划：
    - 短期内只会为这个版块加上标签功能

### 漫评功能介绍：
1. 相较于帖子功能和图片功能，漫评功能的门槛更高，因为它[创作成本](https://www.calibur.tv/review/create)很高
2. 漫评功能主要是：为高质量用户提供区别于普通用户的表达渠道而开发的功能
3. 相比于[受用户诟病](https://www.zhihu.com/question/287683133)的 bilibili 漫评区和等同于无的豆瓣电影漫评，我们的漫评功能是相对于完善和专业的，可以说是首创
4. 职能：
    - 为优质用户提供表达途径，将优质内容提炼出来，并为用户分层，有利于社区管理
    - 为之后会做的类似于[豆瓣电影Top250](https://movie.douban.com/top250)打基础
    - 通过漫评功能，可以知道该作品的内容爆点是什么，什么样年龄段的用户喜欢，什么性别的用户喜欢，为将来给用户做智能推荐打基础
    - 作为独创功能，用来与竞品竞争
5. 漫评功能面临的最大问题就是：不同用户之间对同一作品的评价不同而引发的舌战，我们的管理策略是：
    - 所有版块（包括帖子、图片）的评论区都是可以有开关的
        - 允许所有人评论（默认）
        - 只允许好友评论
        - 不允许任何人评论
    - 即使未来推出的私信功能，我们也是默认只有好友（两个人互相关注）之间可以往来
    - 尽量引导用户发表赞扬的想法，而减少毫无道理的贬低，并且针对不同价值取向，我们会努力让社区朝着求同存异的方向发展
6. 未来规划：
    - 满屏功能的各项评分维度还需要继续观察，找到最合理的评分标准

### 问答功能（开发中）介绍：
1. 未来我们会开发出问答功能
2. 职能同漫评功能一样，为优质内容创作者提供表达途径
3. 与漫评功能的使用场景不同
4. 未来规划，在讲审核流程和消息功能完善后会做出问答功能

### 这些功能的应用场景

### 功能上给用户分层

### 氛围的重要性

### 内容审核机制

### bilibili向社交转型的难点

### 第一弹是一家怎样的公司


