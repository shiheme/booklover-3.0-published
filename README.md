## booklover-3.0-master

案例查看：搜索小程序-建始同城共享书  

- 进入screen目录查看预览图  
- 进入sketch目录下载设计源图  

#比比爱书者小程序3.0

1. 电子书下载（年度豆瓣榜、亚马逊销售榜）   
2. 纸质书借阅（适合公司、学校、地区共享图书）  
3. 连载书阅读  
4. 媒体号推荐（收集一些达人视频号教学）  
5. 评星/留言   
6. 跟随系统调整风格   
7. 书籍多级分类筛选   
8. 书籍列表与瀑布流显示   
9. 分享到朋友圈/好友/群/生成海报   
10. 点赞/收藏   
11. 借阅帮助/借阅福利   
12. 语录增加酷炫音效播放（仿豆瓣FM特效）   
13. 搜索增加记录（仿QQ侧滑特效）   

# 安装步骤：

# 前期准备
1. 购买服务器、域名进行备案并安装最新版本的wordpress(网上很多教程，略过)  
2. 自行配置ssl，保证https开头能访问你的网站(网上很多教程，略过)  
3. 自行前往微信小程序平台申请开发账号，小程序主体可选择个人，类型选择工具>信息(网上很多教程，略过)  

# 开始
1. 下载code > 插件 > wp-mini-program上传到wordpress插件目录下（/wp-content/plugins），进入Wordpress后台启用刚刚安装的此插件  
2. 在插件设置中[小程序授权]一栏中填写在小程序平台获取的Appid和AppSecret  
3. 在插件设置中[常规设置]一栏中填写DownloadFile合法域名，第一栏填写你的域名，下面域名也要添加  
- https://ae.weixin.qq.com  
- https://thirdwx.qlogo.cn  
- https://wx.qlogo.cn  
4. 在插件设置中[常规设置]一栏中填写评论回复通知、审核通过通知、资讯更新提醒三个订阅模板，模板去小程序后台 > 功能 > 订阅内容获取，搜索选用【留言回复通知】【审核通过通知】【资讯更新提醒】 
5. 下载code > 小程序中的代码存放在本地，打开小程序开发者工具，导入刚下载的代码
6. 找到base.js这个文件打开并修改第1行为你的域名，第5、6行修改为刚刚得到的订阅模板
7. 接下来进入小程序后台 > 开发 > 服务器域名，添加【request合法域名】【downloadFile合法域名】，和刚刚wordpress后台添加的合法域名保持一致
8. 在wordpress后台 > 插件 > 安装新插件，搜索pods并安装启用此插件
9. 安装后进入pods设置先启用组件MigratePackages
10. 下载code > 导入数据 > podsdata.txt ,使用MigratePackages的导入功能导入该文件中的数据代码，导入成功后，左侧会出现书目等自定义文件类型
11. 此时大功告成可进行文章发布了
12. code中同时提供了demo数据，你可以下载code > 导入数据 > booklover.WordPress.2021-02-10.app等数据进行导入工作。操作流程：进入Wordpress后台 > 工具 > 导入 > 导入Wordpress，将demo中的5个数据文件依次导入。
13. 进入小程序开发者工具，点击编译，可以看到页面效果。

# 完成
1. 完成以上操作后就可以发布了，通过小程序开发者工具上传代码到小程序后台，提交版本发布即可在1-3天通过审核。提交前记得完善小程序的图标、标题等信息。  

公众号：APP比比



