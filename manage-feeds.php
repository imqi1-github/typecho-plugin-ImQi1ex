<?php

/** 初始化组件 */
Typecho\Widget::widget('Widget_Init');

/** 注册一个初始化插件 */
Typecho\Plugin::factory('admin/common.php')->begin();

Typecho\Widget::widget('Widget_Options')->to($options);
Typecho\Widget::widget('Widget_User')->to($user);
Typecho\Widget::widget('Widget_Security')->to($security);
Typecho\Widget::widget('Widget_Menu')->to($menu);

/** 初始化上下文 */
$request = $options->request;
$response = $options->response;
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">
            <div class="col-mb-12 col-tb-8"
                 role="main">
                <?php
                $prefix = $db->getPrefix();
                $feeds = $db->fetchAll($db->select()->from("table.feeds"));
                ?>
                <form method="post"
                      action="<?php $security->index('/action/feeds-edit?action=delete&do=feed'); ?>"
                      name="manage_categories"
                      class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <div class="btn-group btn-drop">
                                <button class="btn btn-s"
                                        type="submit"> <?php _e('批量删除'); ?> </button>
                                <button class="btn btn-s error"
                                        onclick="location.href='<?php $security->index("/action/feeds-edit?action=delete-all&do=feed") ?>'"
                                        type="button"> <?php _e('全部删除'); ?> </button>
                            </div>
                        </div>
                    </div>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col style="width: 15%"/>
                                <col style="width: 15%"/>
                                <col style="width: 15%"/>
                                <col style="width: 55%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th></th>
                                <th><?php _e('名称'); ?></th>
                                <th><?php _e('图片'); ?></th>
                                <th><?php _e('链接'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($feeds)): $alt = 0; ?>
                                <?php foreach ($feeds as $feed): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   value="<?php echo $feed['id']; ?>"
                                                   name="fid[]"
                                                   id="fid-<?php echo $feed['id']; ?>"
                                            />
                                            <label for="fid-<?php echo $feed['id']; ?>">选择</label>
                                        </td>
                                        <td>
                                            <a href="<?php echo $request->makeUriByRequest('fid=' . $feed['id']); ?>"
                                               title="<?php _e('点击编辑'); ?>"><?php echo $feed['name']; ?></a>
                                        <td><?php
                                            if ($feed['avatar']) {
                                                echo '<a href="' . $feed['avatar'] . '" title="' . _t('点击放大') . '" target="_blank"><img class="avatar" src="' . $feed['avatar'] . '" alt="' . $feed['name'] . '" width="32" height="32"/></a>';
                                            } else {
                                                $options = Typecho\Widget::widget('Widget_Options');
                                                $nopic_url = Typecho\Common::url('usr/plugins/Links/nopic.png', $options->siteUrl);
                                                echo '<img class="avatar" src="' . $nopic_url . '" alt="NOPIC" width="32" height="32"/>';
                                            }
                                            ?></td>
                                        <td><?php echo $feed["feed"] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <h6 class="typecho-list-table-title"><?php _e('还未添加订阅信息'); ?></h6>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="col-mb-12 col-tb-4"
                 role="form">
                <?php ImQi1ex_Plugin::feedForm()->render(); ?>
              <h3>测试 Feed 链接</h3>
              <?php ImQi1ex_Plugin::testFeedForm()->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
    $(document).ready(function () {
    })
</script>

<?php include 'footer.php'; ?>
