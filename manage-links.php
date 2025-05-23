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
      <div class="col-mb-12 col-tb-8" role="main">
          <?php
          $prefix = $db->getPrefix();
          $links = $db->fetchAll($db->select()->from($prefix . 'links')->order($prefix . 'links.order', Typecho\Db::SORT_ASC));
          ?>
        <form method="post" name="manage_categories" class="operate-form">
          <div class="typecho-list-operate clearfix">
            <div class="operate">
              <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox"
                                                                       class="typecho-table-select-all"/></label>
              <div class="btn-group btn-drop">
                <button class="btn dropdown-toggle btn-s" type="button"><i
                    class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                <ul class="dropdown-menu">
                  <li><a lang="<?php _e('你确认要删除这些友链吗?'); ?>"
                         href="<?php $security->index('/action/links-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                  <li><a lang="<?php _e('你确认要启用这些友链吗?'); ?>"
                         href="<?php $security->index('/action/links-edit?do=enable'); ?>"><?php _e('启用'); ?></a></li>
                  <li><a lang="<?php _e('你确认要禁用这些友链吗?'); ?>"
                         href="<?php $security->index('/action/links-edit?do=prohibit'); ?>"><?php _e('禁用'); ?></a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="typecho-table-wrap">
            <table class="typecho-list-table">
              <colgroup>
                <col style="width: 7%"/>
                <col style="width: 18%"/>
                <col/>
                <col style="width: 18%"/>
                <col style="width: 10%"/>
                <col style="width: 12%"/>
              </colgroup>
              <thead>
              <tr>
                <th></th>
                <th><?php _e('友链名称'); ?></th>
                <th><?php _e('友链地址'); ?></th>
                <th><?php _e('分类'); ?></th>
                <th><?php _e('图片'); ?></th>
                <th><?php _e('状态'); ?></th>
              </tr>
              </thead>
              <tbody>
              <?php if (!empty($links)): $alt = 0; ?>
                  <?php foreach ($links as $link): ?>
                  <tr id="lid-<?php echo $link['lid']; ?>">
                    <td><input type="checkbox" value="<?php echo $link['lid']; ?>" name="lid[]"/></td>
                    <td><a href="<?php echo $request->makeUriByRequest('lid=' . $link['lid']); ?>"
                           title="<?php _e('点击编辑'); ?>"><?php echo $link['name']; ?></a>
                    <td><a href="<?php echo $link['url']; ?>"><?php echo $link['url']; ?></a></td>
                    <td><?php echo $link['sort']; ?></td>
                    <td><?php
                        if ($link['image']) {
                            echo '<a href="' . $link['image'] . '" title="' . _t('点击放大') . '" target="_blank"><img class="avatar" src="' . $link['image'] . '" alt="' . $link['name'] . '" width="32" height="32"/></a>';
                        } else {
                            $options = Typecho\Widget::widget('Widget_Options');
                            $nopic_url = Typecho\Common::url('usr/plugins/Links/nopic.png', $options->siteUrl);
                            echo '<img class="avatar" src="' . $nopic_url . '" alt="NOPIC" width="32" height="32"/>';
                        }
                        ?></td>
                    <td><?php
                        if ($link['state'] == 1) {
                            echo '正常';
                        } elseif ($link['state'] == 0) {
                            echo '禁用';
                        }
                        ?></td>
                  </tr>
                  <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何友链'); ?></h6></td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </form>
      </div>
      <div class="col-mb-12 col-tb-4" role="form">
          <?php ImQi1ex_Plugin::linkForm()->render(); ?>
      </div>
    </div>
  </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script>
  $('input[name="email"]').blur(function () {
    var _email = $(this).val();
    var _image = $('input[name="image"]').val();
    if (_email !== '' && _image === '') {
      var k = "<?php $security->index('/action/links-edit'); ?>";
      $.post(k, {"do": "email-logo", "type": "json", "email": $(this).val()}, function (result) {
        var k = jQuery.parseJSON(result).url;
        $('input[name="image"]').val(k);
      });
    }
    return false;
  });
</script>
<script type="text/javascript">
  (function () {
    $(document).ready(function () {
      var table = $('.typecho-list-table').tableDnD({
        onDrop: function () {
          var ids = [];

          $('input[type=checkbox]', table).each(function () {
            ids.push($(this).val());
          });

          $.post('<?php $security->index('/action/links-edit?do=sort'); ?>',
            $.param({lid: ids}));

          $('tr', table).each(function (i) {
            if (i % 2) {
              $(this).addClass('even');
            } else {
              $(this).removeClass('even');
            }
          });
        }
      });

      table.tableSelectable({
        checkEl: 'input[type=checkbox]',
        rowEl: 'tr',
        selectAllEl: '.typecho-table-select-all',
        actionEl: '.dropdown-menu a'
      });

      $('.btn-drop').dropdownMenu({
        btnEl: '.dropdown-toggle',
        menuEl: '.dropdown-menu'
      });

      $('.dropdown-menu button.merge').click(function () {
        var btn = $(this);
        btn.parents('form').attr('action', btn.attr('rel')).submit();
      });

        <?php if (isset($request->lid)): ?>
      $('.typecho-mini-panel').effect('highlight', '#AACB36');
        <?php endif; ?>
    });
  })();
</script>
<?php include 'footer.php'; ?>
