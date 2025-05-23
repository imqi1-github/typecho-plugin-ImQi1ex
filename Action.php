<?php

use Typecho\Db;
use Typecho\Widget;
use Utils\Helper;
use Widget\ActionInterface;

class ImQi1ex_Action extends Widget implements ActionInterface
{
  private $db;
  private $options;
  private $prefix;

  public function action()
  {
    Helper::security()->protect();
    $user = Widget::widget('Widget_User');
    $user->pass('administrator');
    $this->db = Db::get();
    $this->prefix = $this->db->getPrefix();
    $this->options = Widget::widget('Widget_Options');
    $this->on($this->request->is('do=insert'))->insertLink();
    $this->on($this->request->is('do=update'))->updateLink();
    $this->on($this->request->is('do=delete'))->deleteLink();
    $this->on($this->request->is('do=enable'))->enableLink();
    $this->on($this->request->is('do=prohibit'))->prohibitLink();
    $this->on($this->request->is('do=sort'))->sortLink();
    $this->on($this->request->is('do=feed'))->manageFeed();
    $this->response->redirect($this->options->adminUrl);
  }

  public function insertLink()
  {
    if (ImQi1ex_Plugin::linkForm('insert')->validate()) {
      $this->response->goBack();
    }
    /** 取出数据 */
    $link = $this->request->from('image', 'url', 'state');

    /** 过滤XSS */
    $link['name'] = $this->request->filter('xss')->name;
    $link['sort'] = $this->request->filter('xss')->sort;
    $link['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)' => 'maxOrder'))->from($this->prefix . 'links'))->maxOrder + 1;

    /** 插入数据 */
    $link_lid = $this->db->query($this->db->insert($this->prefix . 'links')->rows($link));

    /** 设置高亮 */
    $this->widget('Widget_Notice')->highlight('link-' . $link_lid);

    /** 提示信息 */
    $this->widget('Widget_Notice')->set(_t('友链 <a href="%s">%s</a> 已经被增加', $link['url'], $link['name']), null, 'success');

    /** 转向原页 */
    $this->response->redirect(Typecho\Common::url('extending.php?panel=ImQi1ex%2Fmanage-links.php', $this->options->adminUrl));
  }

  public function updateLink()
  {
    if (ImQi1ex_Plugin::linkForm('update')->validate()) {
      $this->response->goBack();
    }

    /** 取出数据 */
    $link = $this->request->from('image', 'url', 'state');
    $link_lid = $this->request->from('lid');

    /** 过滤XSS */
    $link['name'] = $this->request->filter('xss')->name;
    $link['sort'] = $this->request->filter('xss')->sort;

    /** 更新数据 */
    $this->db->query($this->db->update($this->prefix . 'links')->rows($link)->where('lid = ?', $link_lid));

    /** 设置高亮 */
    $this->widget('Widget_Notice')->highlight('link-' . $link_lid);

    /** 提示信息 */
    $this->widget('Widget_Notice')->set(_t('友链 <a href="%s">%s</a> 已经被更新', $link['url'], $link['name']), null, 'success');

    /** 转向原页 */
    $this->response->redirect(Typecho\Common::url('extending.php?panel=ImQi1ex%2Fmanage-links.php', $this->options->adminUrl));
  }

  public function deleteLink()
  {
    $lids = $this->request->filter('int')->getArray('lid');
    $deleteCount = 0;
    if ($lids && is_array($lids)) {
      foreach ($lids as $lid) {
        if ($this->db->query($this->db->delete($this->prefix . 'links')->where('lid = ?', $lid))) {
          $deleteCount++;
        }
      }
    }
    /** 提示信息 */
    $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('友链已经删除') : _t('没有友链被删除'), null, $deleteCount > 0 ? 'success' : 'notice');

    /** 转向原页 */
    $this->response->redirect(Typecho\Common::url('extending.php?panel=ImQi1ex%2Fmanage-links.php', $this->options->adminUrl));
  }

  public function enableLink()
  {
    $lids = $this->request->filter('int')->getArray('lid');
    $enableCount = 0;
    if ($lids && is_array($lids)) {
      foreach ($lids as $lid) {
        if ($this->db->query($this->db->update($this->prefix . 'links')->rows(array('state' => '1'))->where('lid = ?', $lid))) {
          $enableCount++;
        }
      }
    }
    /** 提示信息 */
    $this->widget('Widget_Notice')->set($enableCount > 0 ? _t('友链已经启用') : _t('没有友链被启用'), null, $enableCount > 0 ? 'success' : 'notice');

    /** 转向原页 */
    $this->response->redirect(Typecho\Common::url('extending.php?panel=ImQi1ex%2Fmanage-links.php', $this->options->adminUrl));
  }

  public function prohibitLink()
  {
    $lids = $this->request->filter('int')->getArray('lid');
    $prohibitCount = 0;
    if ($lids && is_array($lids)) {
      foreach ($lids as $lid) {
        if ($this->db->query($this->db->update($this->prefix . 'links')->rows(array('state' => '0'))->where('lid = ?', $lid))) {
          $prohibitCount++;
        }
      }
    }
    /** 提示信息 */
    $this->widget('Widget_Notice')->set($prohibitCount > 0 ? _t('友链已经禁用') : _t('没有友链被禁用'), null, $prohibitCount > 0 ? 'success' : 'notice');

    /** 转向原页 */
    $this->response->redirect(Typecho\Common::url('extending.php?panel=ImQi1ex%2Fmanage-links.php', $this->options->adminUrl));
  }

  public function sortLink()
  {
    $links = $this->request->filter('int')->getArray('lid');
    if ($links && is_array($links)) {
      foreach ($links as $sort => $lid) {
        $this->db->query($this->db->update($this->prefix . 'links')->rows(array('order' => $sort + 1))->where('lid = ?', $lid));
      }
    }
  }

  public function manageFeed()
  {
    switch ($this->request->action) {
      case "edit":
        $row = $this->db->query($this->db->update("table.feeds")->rows(array(
          "avatar" => $this->request->filter('url')->avatar,
          "name" => $this->request->filter('xss')->name,
          "feed" => $this->request->filter('url')->feed,
        ))->where("id = ?", $this->request->filter('int')->fid));
        $this->widget('Widget_Notice')->set($row > 0 ? _t('订阅信息更新成功') : _t('订阅信息未更新'), null, $row > 0 ? 'success' : 'notice');
        break;
      case "delete":
        $fids = $this->request->filter('int')->getArray('fid');
        $deleteCount = 0;
        if ($fids && is_array($fids)) {
          foreach ($fids as $fid) {
            if ($this->db->query($this->db->delete($this->prefix . 'feeds')->where('id = ?', $fid))) {
              $deleteCount++;
            }
          }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t("已删除${deleteCount}条订阅信息") : _t('没有订阅信息被删除'), null, $deleteCount > 0 ? 'success' : 'notice');
        break;
      case "delete-all":
        $this->db->query($this->db->delete($this->prefix . 'feeds'));
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t("订阅信息已全部删除"), null, 'success');
        break;
      case "test":
        $feed_url = $this->request->filter('url')->feed;
        try {
          $feed_content = file_get_contents($feed_url);
          $headers = $http_response_header; // 这个数组包含 HTTP 响应头
          $statusCode = substr($headers[0], 9, 3); // 提取状态码
          if ($statusCode != 200) {
            $this->widget('Widget_Notice')->set("Feed 地址访问失败", null, "success");
            break;
          }
        } catch (Exception $e) {
          $this->widget('Widget_Notice')->set("Feed 地址访问失败，错误信息：" . $e->getMessage(), null, "success");
          break;
        }

        // 将 XML 内容转换为 SimpleXML 对象
        $xml = simplexml_load_string($feed_content);

        if (!$xml) {
          $this->widget('Widget_Notice')->set("Feed 解析失败", null, "success");
          break;
        }

        if (isset($xml->channel->item)) {
          $this->widget('Widget_Notice')->set("Feed 解析成功，为 RSS2.0 型，最新文章为于" . (date('Y年m月d日 H:i:s', strtotime($xml->channel->item[0]->pubDate))) . "发布的" . $xml->channel->item[0]->title, null, "success");
        } elseif (isset($xml->entry)) {
          $this->widget('Widget_Notice')->set("Feed 解析成功，为 Atom 型，最新文章为于" . (date('Y年m月d日 H:i:s', strtotime($xml->entry[0]->pubDate))) . "发布的" . $xml->entry[0]->title, null, "success");
        } else {
          $this->widget('Widget_Notice')->set("Feed 格式未识别", null, "success");
        }
        break;
      default:
        $row = $this->db->query($this->db->insert("table.feeds")->rows(array(
          "avatar" => $this->request->filter('url')->avatar,
          "name" => $this->request->filter('xss')->name,
          "feed" => $this->request->filter('url')->feed,
        )));
        /** 提示信息 */
        $this->widget('Widget_Notice')->set($row > 0 ? _t('订阅信息添加成功') : _t('订阅信息未添加'), null, $row > 0 ? 'success' : 'notice');

    }
    $this->response->redirect(Typecho\Common::url('extending.php?panel=ImQi1ex%2Fmanage-feeds.php', $this->options->adminUrl));
  }
}
