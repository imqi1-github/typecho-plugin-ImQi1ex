<?php

use Typecho\Db;
use Typecho\Plugin;
use Typecho\Plugin\PluginInterface;
use Typecho\Request;
use Typecho\Widget;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Hidden;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Form\Element\Submit;
use Typecho\Widget\Helper\Form\Element\Text;
use Utils\Helper;

/**
 * NewImQi1配套插件
 *
 * @package ImQi1ex
 * @author 棋
 * @link https://imqi1.com
 * @version 2
 */
class ImQi1ex_Plugin extends Widget implements PluginInterface
{
    private static $qqWryInstance = null;

    /**
     * 激活插件方法
     *
     * @access public
     * @return string
     * @throws \Typecho\Plugin\Exception|Db\Exception
     */
    public static function activate(): string
    {   //友链相关
        $info = self::linksInstall();
        Helper::addPanel(3, 'ImQi1ex/manage-links.php', _t('友情链接'), _t('管理友情链接'), 'administrator');
        Helper::addAction('links-edit', 'ImQi1ex_Action');
        Helper::addAction('feeds-edit', 'ImQi1ex_Action');

        //友链添加
        Helper::addRoute("link-add", "/link-add", __CLASS__, "addLink");
        Helper::addRoute("update-feed-cache", "/update-feed-cache", __CLASS__, "async_update_cache");
        Plugin::factory('admin/header.php')->header = __CLASS__ . "::head";
        Plugin::factory('admin/footer.php')->end = __CLASS__ . '::footerjs';

        // 导航栏提示插件已应用
        Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::render';

        // meting解析
        Helper::addRoute("meting-api", "/meting", __CLASS__, "handleMeting");

        // RSS订阅
        $info .= self::feedInstall();
        Helper::addPanel(3, 'ImQi1ex/manage-feeds.php', _t('订阅列表'), _t('管理订阅列表'), 'administrator');


        // 后台美化
//        Plugin::factory('admin/header.php')->header = __CLASS__ . '::render';
//        Plugin::factory('admin/footer.php')->end = __CLASS__ . '::footerjs';

        return _t($info);
    }

    /**
     *  安装友情链接数据表
     *
     * @return string
     * @throws Db\Exception
     * @throws Plugin\Exception
     */
    public static function linksInstall(): string
    {
        $installDb = Db::get();
        $type = explode('_', $installDb->getAdapterName());
        $type = array_pop($type);
        $prefix = $installDb->getPrefix();
        $scripts = file_get_contents('usr/plugins/ImQi1ex/' . $type . '.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $installDb->query($script, Typecho\Db::WRITE);
                }
            }
            return _t('链接表创建成功 ');
        } catch (Typecho\Db\Exception $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && (1050 == $code || '42S01' == $code)) || ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    $script = 'SELECT `lid`, `name`, `url`, `sort`, `image`, `state`, `order` from `' . $prefix . 'links`';
                    $installDb->query($script);
                    return _t('链接表创建成功 ');
                } catch (Typecho\Db\Exception $e) {
                    $code = $e->getCode();
                    if (('Mysql' == $type && (1054 == $code || '42S22' == $code)) || ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                        return self::linksUpdate($installDb, $type, $prefix);
                    }
                    throw new Typecho\Plugin\Exception(_t("链接表创建失败。错误号：") . $code);
                }
            } else {
                throw new Typecho\Plugin\Exception(_t('链接表创建失败。错误号：') . $code);
            }
        }
    }

    /**
     * 更新友情链接信息数据库层面实现
     *
     * @param $installDb
     * @param $type
     * @param $prefix
     * @return string
     * @throws Plugin\Exception
     */
    public static function linksUpdate($installDb, $type, $prefix): string
    {
        $scripts = file_get_contents('usr/plugins/ImQi1ex/Update_' . $type . '.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $installDb->query($script, Typecho\Db::WRITE);
                }
            }
            return _t('检测到旧版本友情链接数据表，升级成功 ');
        } catch (\Typecho\Db\Exception $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && (1060 == $code || '42S21' == $code))) {
                return _t('友情链接数据表已经存在 ');
            }
            throw new \Typecho\Plugin\Exception(_t('ImQi1ex插件启用失败。错误号：') . $code);
        }
    }

    /**
     * @throws Plugin\Exception
     * @throws Db\Exception
     */
    private static function feedInstall(): string
    {
        $db = Db::get();
        $prefix = $db->getPrefix();
        try {
            $db->query("create table {$prefix}feeds (
            id int PRIMARY KEY AUTO_INCREMENT,
            feed varchar(64),
            name varchar(16),
            avatar varchar(256)
        )");
            return _t("订阅链接表创建成功 ");
        } catch (\Typecho\Db\Exception $e) {
            $code = $e->getCode();
            if ((1050 == $code || '42S01' == $code)) {
                return _t('订阅链接表已存在 ');
            } else {
                throw new \Typecho\Plugin\Exception(_t('ImQi1ex插件启用失败。错误号：') . $code);
            }
        }
    }

    /**
     * 禁用插件方法
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate(): void
    {
        Helper::removeAction('links-edit');
        Helper::removeAction('feeds-edit');
        Helper::removePanel(3, 'ImQi1ex/manage-links.php');
        Helper::removePanel(3, 'ImQi1ex/manage-feeds.php');

        Helper::removeRoute("meting-api");
        Helper::removeRoute("link-add");
        Helper::removeRoute("update-feed-cache");
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho\Widget\Helper\Form $form 配置面板
     * @return void
     */
    public static function config(Typecho\Widget\Helper\Form $form)
    {
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Form $form
     * @return void
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 渲染表单
     *
     * @param $action
     * @return Form
     * @throws Db\Exception
     * @throws \Typecho\Widget\Exception
     */
    public static function linkForm($action = null): Form
    {
        /** 构建表格 */

        $form = new Form(Helper::security()->getIndex('/action/links-edit'), Form::POST_METHOD);

        /** 友链名称 */
        $name = new Text('name', null, null, _t('友链名称*'));
        $form->addInput($name);

        /** 友链地址 */
        $url = new Text('url', null, "https://", _t('友链地址*'));
        $form->addInput($url);

        /** 友链分类 */
        $sort = new Text('sort', null, null, _t('友链分类'), _t('建议以英文字母开头，只包含字母与数字'));
        $form->addInput($sort);

        /** 友链图片 */
        $image = new Text('image', null, null, _t('友链图片'), _t('需要以http://或https://开头，留空表示没有友链图片'));
        $form->addInput($image);

        /** 友链状态 */
        $list = array('0' => '禁用', '1' => '启用');
        $state = new Radio('state', $list, '1', '友链状态');
        $form->addInput($state);

        /** 友链动作 */
        $do = new Hidden('do');
        $form->addInput($do);

        /** 友链主键 */
        $lid = new Hidden('lid');
        $form->addInput($lid);

        /** 提交按钮 */
        $submit = new Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        $request = Request::getInstance();

        if (isset($request->lid) && 'insert' != $action) {
            /** 更新模式 */
            $db = Db::get();
            $prefix = $db->getPrefix();
            $link = $db->fetchRow($db->select()->from($prefix . 'links')->where('lid = ?', $request->lid));
            if (!$link) {
                throw new \Typecho\Widget\Exception(_t('友链不存在'), 404);
            }

            $name->value($link['name']);
            $url->value($link['url']);
            $sort->value($link['sort']);
            $image->value($link['image']);
            $state->value($link['state']);
            $do->value('update');
            $lid->value($link['lid']);
            $submit->value(_t('编辑友链'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('增加友链'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('必须填写友链名称'));
            $url->addRule('required', _t('必须填写友链地址'));
            $name->addRule('maxLength', _t('友链名称最多包含50个字符'), 50);
            $url->addRule('maxLength', _t('友链地址最多包含200个字符'), 200);
            $sort->addRule('maxLength', _t('友链分类最多包含50个字符'), 50);
            $image->addRule('maxLength', _t('友链图片最多包含200个字符'), 200);
        }
        if ('update' == $action) {
            $lid->addRule('required', _t('友链主键不存在'));
        }
        return $form;
    }

    /**
     * 为正文中的相关标记替换为友情链接
     *
     * @return array
     * @throws Db\Exception
     */
    public static function getLink(): array
    {
        $db = Db::get();
        return $db->fetchAll($db->select("name", "url", "sort", "image", "order")->from('table.links')->where("state = 1")->order("order"));
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render(): void
    {
        echo '<button class="btn install">'
            . htmlspecialchars('安装应用')
            . '</button>';
    }

    /**
     * 为后台增加PWA
     *
     * @access public
     * @return void
     */
    public static function head(): void
    {
        $options = \Widget\Options::alloc();
        echo '<link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'normalize.css', true) . '">
    <link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'grid.css', true) . '">
    <link rel="stylesheet" href="' . $options->adminStaticUrl('css', 'style.css', true) . '">';
        $resource = Helper::options()->staticUrl ?: Helper::options()->themeUrl . "/public";
        echo "<link rel='manifest' href='" . $resource . "/imqi1-admin-manifest.json'>";
        echo "<script>'ServiceWorker' in Navigator && Navigator.ServiceWorker.register('" . Helper::options()->siteUrl . "sw.js')</script>";
    }

    /**
     * 页脚js
     *
     * @access public
     * @return void
     */
    public static function footerjs(): void
    {
        echo "<script>
      $(document).ready(function() {
        // 监听应用安装事件
        window.addEventListener('appinstalled', () => {
          window.deferredPrompt = null;
        });
      
        // 监听安装提示事件
        window.addEventListener('beforeinstallprompt', (e) => {
          // 阻止默认的提示
          e.preventDefault();
          // 保存事件到 window 对象
          window.deferredPrompt = e;
      
          let link = $('.more-link');
          if (link.length > 0 && link.find('a.install').length === 0) {
            let a = $('<a class=\'install\'>安装应用</a>');
            a.on('click', installFunc);
            a.attr('href', '#');
            link.append(a);
          }
          
          let install = $('.install');
          if (install.length > 0) {
            install.on('click', installFunc);
          }
        });
      
        // 安装操作
        const installFunc = async () => {
          // 确保 deferredPrompt 是有效的
          if (window.deferredPrompt) {
            // 调用 prompt() 显示安装提示
            window.deferredPrompt.prompt();
      
            // 处理用户选择
            window.deferredPrompt.userChoice.then((result) => {
              if (result.outcome === 'accepted') {
                console.log('用户接受安装');
              } else {
                console.log('用户拒绝安装');
              }
              // 安装完成后清空 deferredPrompt
              window.deferredPrompt = null;
            });
          }
        }
      });
    </script>";
    }

    /**
     * 处理meting相关请求
     *
     * @return void
     */
    public static function handleMeting(): void
    {
        include_once "lib/meting.php";
    }

    public static function addLink()
    {
        $token = $_REQUEST['token'];

        if (!self::validateToken($token)) {
            throw new Exception("Token失效，请刷新页面重新申请，或联系站主");
        }

        $name = $_REQUEST['name'];
        $url = $_REQUEST['url'];
        $_sort = $_REQUEST['sort'] ?: "";
        $image = $_REQUEST['image'] ?: "";

        if (!$name) {
            throw new Exception("请填写名称", 400);
        }

        if (!$url) {
            throw new Exception("请填写链接", 400);
        }

        $db = Db::get();
        $code = $db->query($db->insert("table.links")->rows(array(
            "name" => $name,
            "url" => $url,
            "sort" => $_sort,
            "image" => $image,
            "state" => 0
        )));

        if ($code) {
            echo "友链添加成功";
        } else {
            throw new Exception("友链添加失败，code：" . $code);
        }
    }

    private static function validateToken($encrypted_token): bool
    {
        if (!$encrypted_token) {
            return false;
        }
        try {
            $key = "imqi1.com";
            $cipher = "aes-256-cbc";
            $encrypted_token = base64_decode($encrypted_token);
            $iv_length = openssl_cipher_iv_length($cipher);
            $iv = substr($encrypted_token, 0, $iv_length);
            $encrypted_string = substr($encrypted_token, $iv_length);
            $decrypted_string = openssl_decrypt($encrypted_string, $cipher, $key, 0, $iv);
            $timestamp = substr($decrypted_string, strlen($key));
            if (!is_numeric($timestamp)) return false;
            $current_time = time();
            $validity_period = 1800;
            return ($current_time - $timestamp) <= $validity_period;
        } catch (Exception) {
            return false;
        }
    }

    public static function getFeeds()
    {
        $db = Db::get();
        return $db->fetchAll($db->select("avatar", "name", "feed")->from('table.feeds'));
    }

    public static function feedForm()
    {
        $request = Request::getInstance();
        $form = new Form(Helper::security()->getIndex('/action/feeds-edit'), Form::POST_METHOD);

        /** 订阅名称 */
        $name = new Text('name', null, null, _t('订阅名称*'));
        $form->addInput($name);

        /** 订阅头像 */
        $avatar = new Text('avatar', null, null, _t('订阅头像*'));
        $form->addInput($avatar);

        /** 订阅链接 */
        $feed_url = new Text('feed', null, null, _t('订阅链接*'));
        $form->addInput($feed_url);

        /** 订阅主键 */
        $fid = new Hidden('fid');
        $fid->value($request->fid);
        $form->addInput($fid);

        $do = new Hidden("do");
        $do->value("feed");
        $form->addInput($do);

        $action = new Hidden('action');
        $form->addInput($action);

        /** 提交按钮 */
        $submit = new Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (isset($request->fid)) {
            /** 更新模式 */
            $db = Db::get();
            $prefix = $db->getPrefix();
            $feed = $db->fetchRow($db->select()->from($prefix . 'feeds')->where('id = ?', $request->fid));
            if (!$feed) {
                throw new \Typecho\Widget\Exception(_t('订阅不存在'), 404);
            }

            $avatar->value($feed['avatar']);
            $name->value($feed['name']);
            $feed_url->value($feed['feed']);
            $action->value("edit");
            $submit->value(_t('编辑订阅'));
        } else {
            $submit->value(_t('添加订阅'));
        }

        $avatar->addRule('required', _t('必须填写订阅头像'));
        $name->addRule('required', _t('必须填写订阅名称'));
        $feed_url->addRule('required', _t('必须填写订阅链接'));

        return $form;
    }

    public static function testFeedForm()
    {
        $form = new Form(Helper::security()->getIndex('/action/feeds-edit'), Form::POST_METHOD);

        $feed_url = new Text('feed', null, null, _t('订阅链接*'));
        $form->addInput($feed_url);

        $submit = new Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        $submit->value("测试链接");

        $do = new Hidden("do");
        $do->value("feed");
        $form->addInput($do);

        $action = new Hidden('action');
        $form->addInput($action);
        $action->value("test");

        $feed_url->addRule('required', _t('必须填写订阅名称'));
        return $form;
    }

    public static function echoCache()
    {
        echo file_get_contents(__DIR__ . "/cache/feed_cache.html");
    }

    public static function filePath()
    {
        return __DIR__ . "/cache/feed_cache.html";
    }

    public static function async_update_cache($feeds = []): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $token = isset($_REQUEST["token"]) ? $_REQUEST['token'] : "";

        if (!self::validateToken($token)) {
            throw new Exception("Token无效");
        }

        if (!$feeds) {
            $db = Db::get();
            $feeds = $db->fetchAll($db->select("name", "avatar", "feed")->from("table.feeds"));
        }

        // 用来存储所有从 Feed 获取到的数据
        $all_feed_data = [];

        // 遍历每个 Feed
        foreach ($feeds as $feed) {
            // 获取当前 Feed 的数据
            try {
                $feed_data = self::fetch_feed_data($feed['feed']);
            } catch (\Exception) {
                continue;
            }

            // 将数据存储在 $all_feed_data 数组中
            $all_feed_data[] = [
                'avatar' => $feed['avatar'],
                'name' => $feed['name'],
                'data' => $feed_data,
            ];
        }

        // 生成新的 Feed HTML 内容
        $new_feed_html = self::generate_feed_html($all_feed_data);

        // 将新的 HTML 内容保存到缓存文件
        file_put_contents(__DIR__ . "/cache/feed_cache.html", $new_feed_html);

        echo "更新成功";

        exit;
    }

    public static function fetch_feed_data($feed_url): array
    {
        if (empty($feed_url)) {
            throw new Exception("FeedURL列表为空");
        }

        try {
            // 使用 file_get_contents 获取 RSS Feed 的内容
            $feed_content = file_get_contents($feed_url);
        } catch (\Exception $e) {
            throw new Exception("读取 RSS 内容失败，错误信息：" . $e->getMessage());
        }

        // 将 XML 内容转换为 SimpleXML 对象
        $xml = simplexml_load_string($feed_content);

        // 如果解析失败，返回空数组
        if (!$xml) {
            throw new Exception("RSS 解析失败");
        }

        // 提取标题、摘要和发布日期
        $feed_data = [];

        $i = 0;

        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                if ($i++ >= 15) break;
                $snippet = (string)$item->description;
                $snippet = self::truncateString($snippet);
                $feed_data[] = [
                    'title' => (string)$item->title,
                    'link' => (string)$item->link,
                    'snippet' => $snippet,
                    'updated' => (string)$item->pubDate,
                ];
            }
        } elseif (isset($xml->entry)) {
            // 遍历每个 <item> 元素（RSS Feed 中的每篇文章）
            foreach ($xml->entry as $item) {
                if ($i++ >= 15) break;
                $snippet = preg_replace('/\s+/', '', strip_tags((string)$item->summary));
                $snippet = self::truncateString($snippet);
                $feed_data[] = [
                    'title' => (string)$item->title, // 文章标题
                    'link' => (string)$item->id,
                    'snippet' => $snippet,
                    'updated' => (string)$item->published, // 文章发布日期
                ];
            }
        } else {
            throw new Exception("无法识别的 Feed 格式");
        }


        return $feed_data;
    }

    public static function truncateString($string, $length = 350)
    {
        // 判断字符串长度
        if (mb_strlen($string, 'UTF-8') > $length) {
            // 截取字符串并添加省略号
            return mb_substr($string, 0, $length, 'UTF-8') . '...';
        } else {
            // 不超过指定长度，直接返回原字符串
            return $string;
        }
    }

    public static function generate_feed_html($feeds): string
    {
        $html = '';

        $flattened = [];

        foreach ($feeds as $item) {
            foreach ($item['data'] as $dataItem) {
                $flattened[] = array_merge($dataItem, [
                    'name' => $item['name'],
                    'avatar' => $item['avatar']
                ]);
            }
        }

        // 按时间排序，最先发布的排到最前面
        usort($flattened, function ($a, $b) {
            return strtotime($b['updated']) - strtotime($a['updated']);
        });

        $i = 1;

        foreach ($flattened as $feed) {

            if ($i++ > 30) break;
            $html .= '<div class="feed-item-box">';
            $html .= '    <div class="feed-item-avatar">';
            $html .= '        <img src="' . $feed['avatar'] . '" alt="头像" width="50" height="50" />';
            $html .= '    </div>';
            $html .= '    <div class="feed-item-right">';
            $html .= '        <a class="feed-item-title" target="_blank" href="' . htmlspecialchars($feed['link']) . '">' . htmlspecialchars($feed['title']) . '</a>';
            $html .= '        <div class="feed-item-snippet">' . htmlspecialchars($feed['snippet']) . '</div>';
            $html .= '        <div class="feed-item-information">';
            $html .= '            <div class="feed-item-name">' . htmlspecialchars($feed['name']) . '</div>';
            $html .= '            <div class="feed-item-time">' . date('Y年m月d日 H:i:s', strtotime($feed['updated'])) . '</div>';
            $html .= '        </div>';
            $html .= '    </div>';
            $html .= '</div>';
        }

        return $html;
    }

    public static function echoIpInformation(string $ip)
    {
        include_once "lib/QQWry.php";
        if (is_null(ImQi1ex_Plugin::$qqWryInstance)) {
            ImQi1ex_Plugin::$qqWryInstance = new QQWry(); // 初始化类;
        }
        $detail = ImQi1ex_Plugin::$qqWryInstance->getDetail($ip); // 调用查询函数
        if (!$detail) return;
        $position = null;
        if (str_contains($detail["dataA"], "中国–")) {
            $position = explode("–", $detail["dataA"])[1];
        }
        if ($position) {
            echo "<span><i class=\"ri-map-pin-2-fill\"></i>$position</span>";
        }
        if ($detail["dataB"]) {
            if (str_contains($detail["dataB"], "移动")) {
                echo "<span><i class=\"ri-earth-fill\"></i>移动</span>";
            } else if (str_contains($detail["dataB"], "联通")) {
                echo "<span><i class=\"ri-earth-fill\"></i>联通</span>";
            } else if (str_contains($detail["dataB"], "电信")) {
                echo "<span><i class=\"ri-earth-fill\"></i>电信</span>";
            } else
                echo "<span><i class=\"ri-earth-fill\"></i>" . $detail['dataB'] . "</span>";
        }
    }
}
