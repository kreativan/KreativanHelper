<?php
/**
 *  KreativanHelper Module
 *
 *  @author Ivan Milincic <kreativan@outlook.com>
 *  @copyright 2020 Ivan Milincic
 *
*/

class KreativanHelper extends WireData implements Module {

  public static function getModuleInfo() {
    return array(
      'title' => 'Kreativan Helper',
      'version' => 100,
      'summary' => 'Admin related helper methods...',
      'icon' => 'code-fork',
      'singular' => true,
      'autoload' => true
    );
  }

  public function __construct() {
    $this->module_url = $this->config->urls->siteModules.$this->className();
  }

  public function ready() {

    // Register $helper api var
    $this->wire('helper', $this, true);

    // var => data
    $_vars = $this->config->paths->templates . "_vars.php";
    if(file_exists($_vars)) include($_vars);

    // Register other vars
    if($vars && isset($vars) && count($vars) > 0)
    foreach($vars as $key => $value) {
      $this->wire($key, $value, true);
    }

  }

  public function init() {

    if($this->isAdminPage()) {

      $this->config->styles->append($this->module_url."/admin.css");
      if($this->fa == "1") $this->config->styles->append($this->fa_link);
      if($this->load_admin_style == "1") $this->config->styles->append($this->module_url."/style.css");
      $this->config->scripts->append($this->module_url."/helper.js");
      $this->config->scripts->append($this->module_url."/repeater.js");

      // include hooks file
      include("_hooks.php");
      include("actions.php");
      include("actions-ajax.php");

      // js
      // console.log(ProcessWire.config.kreativanHelper);
      $this->config->js('kreativanHelper', [
        'templatesPath' => "{$this->config->paths->templates}",
        'templatesUrl' => "{$this->config->urls->templates}",
      ]);

      /**
       *  Set $_SESSION["new_back"]
       *  This is used this to redirect back to module page,
       *  after creating new page.
       *  @see $this->newPageLink()
       */
      if($this->input->get->new_back) {
        $this->session->set("new_back", $this->input->get->new_back);
      }

      /**
       *  If there is $_SESSION["new_back"]
       *  redirect back to the module on page save + exit
       *  @see $this->redirect()
       */
      if($this->session->get("new_back")) {
        if(($this->input->post('submit_save') == 'exit') || ($this->input->post('submit_publish') == 'exit')) {
          $this->input->post->submit_save = 1;
          $this->addHookAfter("Pages::saved", $this, "redirect");
        }
      }

      // run hide pages hook
      $this->addHookAfter('ProcessPageList::execute', $this, 'hidePages');

    }

    // run methods
    return $this->dragDropSort();

  }

  /* ----------------------------------------------------------------
    Helpers
  ------------------------------------------------------------------- */
  // Check if current page is admin page
  public function isAdminPage() {
    if(strpos($_SERVER['REQUEST_URI'], $this->wire('config')->urls->admin) === 0) {
      return true;
    } else {
      return false;
    }
  }

  /**
   *  Generate admin action link
   *  @param string $action_name - name of the action $input->get->{action_name}
   *  @param string $item_id - related page id
   *  dotn forget to clear ~ from redirect_segment before redirect
  */
 public function actionURL($action_name, $item_id) {
   $url_segments = $this->input->urlSegment1;
   $url_segments .= ($this->input->urlSegment2) ? "/{$this->input->urlSegment2}" : "";
   $url_segments .= ($this->input->urlSegment3) ? "/{$this->input->urlSegment3}" : "";
   $i = 0;
   foreach($this->input->get as $key => $value) {
     if($i++ > 0) {
       $url_segments .= "~&${key}={$value}~";
     } else {
       $url_segments .= "~?${key}={$value}~";
     }
   }
   $action_url = $this->page->url . "?admin_action=$action_name&id=$item_id&redirect_segment={$url_segments}";
   return $action_url;
 }

 /**
  *  Page Edit Link
  *  Use this method to generate page edit link.
  *  @param integer $id  Page ID
  *  @example href='{$this->pageEditLink($item->id)}';
  */
  public function pageEditLink($id) {
    $currentURL = $_SERVER['REQUEST_URI'];
    $url_segment = explode('/', $currentURL);
    $url_segment = $url_segment[sizeof($url_segment)-1];
    // encode & to ~
    $url_segment = str_replace("&", "~", $url_segment);
    return $this->page->url . "edit/?id=$id&back_url={$url_segment}";
  }

  /**
   *  New Page Link
   *  Use this method to generate new page link
   *  @param integer $parent_id  Parent page id
   *  @example href='{$this->newPageLink($parent_id)}';
   */
   public function newPageLink($parent_id) {
     return $this->config->urls->admin . "page/add/?parent_id={$parent_id}&new_back={$this->page->name}";
   }

  /* ----------------------------------------------------------------
    Admin UI
  ------------------------------------------------------------------- */

  /**
   *  Include Admin File
   *  This will include admin php file from the module folder
   *  @var Module $module         module we are using this method in, usually its $this
   *  @var string $file_name		php file name from module folder
   *	@var string $page_name		used to indentify active page
   *
   *  @example return $this->modules->get("cmsHelper")->includeAdminFile($this, "admin.php", "main");
   *
   */
  public function includeAdminFile($module, $file_name, $page_name) {

    // save before removing session var
    $back_url = $this->session->get("back_url");

    /**
     *  Remove @var back_url session
     *  Remove @var new_back session
     *  This will reset current session vars,
     *  used for redirects on page save + exit
     */
    $this->session->remove("back_url");
    $this->session->remove("new_back");

    if(!empty($back_url)) {
      // decode back_url:  ~ to &  - see @method pageEditLink()
      $back_url = str_replace("~", "&", $back_url);
      $goto = $this->page->url . $back_url;
      $this->session->redirect($goto);
    }

    $vars = [
      "this_module" => $module,
      "page_name" => $page_name,
      "module_edit_URL" => $this->urls->admin . "module/edit?name=" . $module->className() . "&collapse_info=1",
      "helper" => $this,
      "module_dir" => $this->config->paths->siteModules . $module->className() . "/",
    ];

    $template_file = $this->config->paths->siteModules . $module->className() . "/" . $file_name;
    return $this->files->render($template_file, $vars);

  }


  /**
   *  Intercept page tree json and remove page from it
   *  We will remove page by its template
   */
  public function hidePages(HookEvent $event){

    // get system pages
    $sysPagesArr = $this->sys_pages;

    // aditional pages to hide by ID
    $customArr = [];
    if($this->hide_system_pages == "1") {
      $customArr[] = "2"; // admin
      $customArr[] = $this->pages->get("template=system");
    }

    if($this->config->ajax) {

      // manipulate the json returned and remove any pages found from array
      $json = json_decode($event->return, true);
      if($json) {
        foreach($json['children'] as $key => $child){
          $c = $this->pages->get($child['id']);
          $pagetemplate = $c->template;
          if(in_array($pagetemplate, $sysPagesArr) || in_array($c, $customArr)) {
            unset($json['children'][$key]);
          }
        }
        $json['children'] = array_values($json['children']);
        $event->return = json_encode($json);
      }

    }

  }

  /**
   *	Sort Pages drag and drop
   *  Run this in init method
   *
	 */
	public function dragDropSort() {

		if($this->config->ajax) {

      if($this->input->post->action == "drag_drop_sort") {

        $id = $this->sanitizer->int($this->input->post->id);
        $this_page = $this->pages->get("id=$id");

        $next_id = $this->sanitizer->int($this->input->post->next_id);
        $next_page = (!empty($next_id)) ? $this->pages->get("id=$next_id") : "";

        $prev_id = $this->sanitizer->int($this->input->post->prev_id);
        $prev_page = (!empty($prev_id)) ? $this->pages->get("id=$prev_id") : "";

        // move to end
        if(empty($next_id)) {
          $lastSibling = $this_page->siblings('include=all')->last();
          $this->pages->insertAfter($this_page, $lastSibling);
        }
        // move to beginning
        if(empty($prev_id)) {
          $this->pages->sort($this_page, 0);
        }

        // insert after preview page
        if(!empty($next_page) && !empty($prev_page)) {
          $this->pages->insertAfter($this_page, $prev_page);
        }

      }

		}

  }


  public function adminPageEdit() {

    /**
     *  Set @var back_url session var
     *  So we can redirect back where we left
     */
    if($this->input->get->back_url) {
      // decode back_url:  ~ to &  - see @method pageEditLink()
      $back_url_decoded = str_replace("~", "&", $this->input->get->back_url);
      $this->session->set("back_url", $back_url_decoded);
    }

    /**
     *  Set the breadcrumbs
     *  add $_SESSION["back_url"] to the breacrumb link
     */
    $this->fuel->breadcrumbs->add(new Breadcrumb($this->page->url.$this->session->get("back_url"), $this->page->title));

    // Force activate multi-language page variations
    if($this->languages && $this->languages->count) {
      foreach($this->languages as $lng) {
        $id = $this->sanitizer->int($this->input->get->id);
        $p = $this->pages->get("id=$id");
        $status_field = "status{$lng}";
        if($p->{$status_field} != "" && $p->{$status_field} != 1) {
          $p->of(false);
          $p->{$status_field} = 1;
          $p->save();
        }
      }
    }

    // Execute Page Edit
    $processEdit = $this->modules->get('ProcessPageEdit');
    return $processEdit->execute();

  }

  /**
   *	This is our main redirect function.
   *	We are using this function to redirect back to previews page
   *  on save+exit and save+publish actions
   *  based on $_SESSION["back_url"] and $_SESSION["new_back"]
   */
  public function redirect() {

    if($this->session->get("back_url")) {
      $goto = "./../" . $this->session->get("back_url");
    } elseif($this->session->get("new_back")) {
      $new_back   = $this->session->get("new_back");
      $goto       = $this->pages->get("template=admin, name=$new_back")->url;
    } else {
      $goto = $this->page->url;
    }

    $this->session->redirect($goto);

  }

  /* ------------------------------------------------------------------------------
    Methods
  --------------------------------------------------------------------------------- */

  /**
   *  Save Module Settings
   *  @param string $module     module class name
   *  @param array $data        module settings
   */
  public function saveModule($module, $data = []) {
    $old_data = $this->modules->getModuleConfigData($module);
    $data = array_merge($old_data, $data);
    $this->modules->saveModuleConfigData($module, $data);
  }

  /**
   *  Activate page for all languages
   *  @param page $p
   */
  public function setMultilangPage($p) {
    $languages = wire("languages");
    if(!empty($languages) && count($languages) > 0) {
      foreach($languages as $lng)  {
        if($lng->name != "default") {
          $status = "status{$lng->id}";
          $p->of(false);
          $p->{$status} = 1;
          $p->save();
        }
      }
    }
  }

  // Check if multilanguage is installed
  public function isMultiLang($debug = false) {

    $errors = [];

    $lng_modules = [
      "FieldtypePageTitleLanguage",
      "FieldtypeTextLanguage",
      "FieldtypeTextareaLanguage",
      "LanguageSupport",
      "LanguageSupportPageNames",
      "LanguageSupportFields",
      "LanguageTabs",
    ];

    foreach($lng_modules as $m) {
      if($this->modules->isInstalled($m) === false) {
        $errors[] = $m . " is missing.";
      }
    }

    if($debug === true) {
      return count($errors) > 0 ? $errors : true;
    } else {
      return count($errors) > 0 ? false : true;
    }

  }

  /**
   *  Upload Image
   *  @param string $field_name - name of a file field in a form
   *  @param string $dest - upload destination
   *  @param string $rename - new file name without extension (optional)
   */
  public function uploadImage($field_name, $dest, $rename = "") {
    if(is_array($_FILES["$field_name"]["name"])) {
      $this_file   = $_FILES["$field_name"]["name"][0];
      $temp_file   = $_FILES["$field_name"]["tmp_name"][0];
    } else {
      $this_file   = $_FILES["$field_name"]["name"];
      $temp_file   = $_FILES["$field_name"]["tmp_name"];
    }
    if($this_file && !empty($this_file) && $this_file != "") {
      $this_file_ext = pathinfo($this_file, PATHINFO_EXTENSION);
      $rename = $rename != "" ? "{$rename}.{$this_file_ext}" : $this_file;
      move_uploaded_file($temp_file, "{$dest}{$rename}");
    }
  }

}
