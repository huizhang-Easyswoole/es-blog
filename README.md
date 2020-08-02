# Easyswoole简单博客系统

> 几乎每个程序员都有一个博客梦，用别人的吧扩展性又不强，自己想加些花里胡哨的东西又没法扩展，最重要的是没有一个合适的博客编辑工具，我不知道大家的习惯如何
我是比较喜欢phpstorm的markdown插件, 那问题来了！怎么样用最简单的方式将markdown文档与博客系统关联呢? 思来想去，还是用最简单最好用的easyswoole
来解决这个问题。

## 1. 设计思路

1. ide编辑文档
2. easyswoole 定时扫描文档变更情况,将必要信息入库
3. 完毕

## 2. 支持功能

1. 支持各种markdown编辑器编辑博客
2. 支持博客分类(只支持一级)
3. 热门博客
4. 博客详情
5. 访问量
6. seo(手动seo或者利用easyswoole的words-match组件自动生成seo)
7. 详情导航

## 3. 安装过程

#### 将代码fock到自己的github仓库
![:](View/Static/Images/es-blog-fock.png)

#### 将代码拉到服务器or本地

````text
git clone https://github.com/huizhang-Easyswoole/es-blog.git
````

#### 启动

> 用于定时拉代码
````text
nohup /usr/bin/bash git-pull.sh &
````

#### 创建两张表

`博客分类表`

````sql
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_name` (`menu_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
````

`博客详情表`

````sql
CREATE TABLE `article_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `cover` text NOT NULL,
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `utime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `menu_name` varchar(255) NOT NULL DEFAULT '',
  `file_name` varchar(255) NOT NULL DEFAULT '',
  `pv` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
````

#### 修改blog.ini

````ini
;博客所用数据库配置
[db]
host=127.0.0.1
port=3306
user=root
password=root
database=blog

;页面展示
[view]
;页面LOGO
logo='ES-BLOG'
title='ES-BLOG'
````

#### 启动

````text
php easyswoole start
````

## 4. 效果

#### 整体

![:](/View/Static/Images/es-blog-home.png)

#### 博客详情

![:](View/Static/Images/es-blog-detail.png)

#### 编辑博客

![:](View/Static/Images/es-blog-write.png)

## 5. 如何编辑博客

> 在项目Doc目录下创建目录(目录只支持一级),目录下创建编辑markdown文档，将改动push到github，服务器上的git-pull.sh就会自动拉最新的文档。

![:](View/Static/Images/es-blog-dir.png)

## 6. 如何使用seo

#### 手动

> 用---分隔

![:](View/Static/Images/es-blog-seo.png)

#### 利用words-match组件

> 将写的博客出现频率和自认为重点的词直接放到wordsmatch.txt文件中

![:](View/Static/Images/es-blog-wm.png)


## 7. 总结

> 能凑合用了但不完善，欢迎大家提issue和pr
