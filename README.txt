﻿V1.1Release
更新日期：201706014
更新内容：
    修复某些PHP版本下启用插件报错；
    增强采集历史管理页面功能；
    修复某些情况下创建、删除任务错乱的问题；
    优化采集数量统计，数量更准确；
    增加文章内容添加后缀；
    适应某些gbk或者gb2312编码的，并且列表页和内容页编码不一样的网站。这个也真是够奇葩的。。。

V1.0Release
更新日期：20170530
更新内容：
    屏蔽掉某些环境下图片下载失败后的系统警告信息；
    增加下载到本地或者七牛云的图片失败后替换为本地404图片（直接替换插件中的404.jpg）；
    分类自动匹配，在标题中匹配所有的分类名称，如果没有匹配到则在内容中自动匹配；
    标签自动匹配，在标题和内容中匹配所有的已存在的标签；
    优化了设置页面，重新排版。隔离开必选设置和可选设置；
    添加内容替换，可以进行一定的伪原创；
    添加了一个历史记录管理页面，可以删除采集的历史文章。


V1.0Beta5
更新日期：20170416
更新内容：
	修复部分环境下，http.php路径报错导致不能启用插件的问题；
	修改内容匹配默认取第一个结果为取所有的结果；
	增加翻页模式，可以采集一个文章下面设置多次翻页；
	修改本地图片保存文件名，防止多篇文章图片名称重复。

V1.0Beta4
更新日期：20170309
更新内容：
	增加七牛云存储选项，可以选择将图片上传到七牛云。

V1.0Beta3
更新日期：20170308
更新内容：
	更改“翻页链接”匹配模式，直接设置xpath节点；
	增加“翻页次数”，控制采集的数量；
	更改“内容链接”匹配模式，直接设置xpath节点。定位更准确，更灵活；
	增加“发布用户”选项；
	每个任务单独增加“运行”按钮，可以直接运行任务；
	增加“日志”查看页面，方便查看运行日志。

V1.0Beta2
更新日期：20170306
更新内容：
	修正某些php版本下的问题。
