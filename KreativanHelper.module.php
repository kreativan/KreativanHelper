<?php
/**
 *  KreativanHelper Module
 *
 *  @author Ivan Milincic <kreativan@outlook.com>
 *  @copyright 2020 Ivan Milincic
 *
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

  public function init() {

    // if its admin page add custom css files
    if(strpos($_SERVER['REQUEST_URI'], $this->wire('config')->urls->admin) === 0) {
      $this->config->styles->append($this->config->urls->siteModules.$this->className()."/admin.css");
      if($this->fa == "1") {
        $this->config->styles->append($this->fa_link);
      }
      if($this->load_admin_style == "1") {
        $this->config->styles->append($this->config->urls->siteModules.$this->className()."/style.css");
      }
      $this->config->scripts->append($this->config->urls->siteModules.$this->className()."/helper.js");
    }

    // display messages if session alert and status vars are set
    if($this->session->admin_status == 'message') {
      $this->message($this->session->admin_message);
    } else if($this->session->admin_status == 'warning') {
      $this->warning($this->session->admin_message);
    } elseif($this->session->admin_status == 'error') {
      $this->error($this->session->admin_message);
    }

    // reset / delete status and alert session vars
    $this->session->remove('admin_status');
    $this->session->remove('admin_message');


    /**
     *  Set $_SESSION["new_back"]
     *
     *  This is used this to redirect back to module page,
     *  after creating new page.
     *  @see newPageLink()
     *
     */
    if($this->input->get->new_back) {
      $this->session->set("new_back", $this->input->get->new_back);
    }

    /**
     *  If there is $_SESSION["new_back"]
     *  redirect back to the module on page save + exit
     *  @see redirect()
     *
     */
    if($this->session->get("new_back")) {
      if(($this->input->post('submit_save') == 'exit') || ($this->input->post('submit_publish') == 'exit')) {
        $this->input->post->submit_save = 1;
        $this->addHookAfter("Pages::saved", $this, "redirect");
      }
    }

    // run hide pages hook
    $this->addHookAfter('ProcessPageList::execute', $this, 'hidePages');

    // run methods
    return $this->adminActions() . $this->adminAjax() . $this->dragDropSort();

  }

  /* ===========================================================
    Admin Methods
  =========================================================== */

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
     *
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
        "api" => $this->modules->get("cmsApi"),
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
    if($this->hide_admin == "1") {
      array_push($customArr, "2");
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
   *  Admin Actions
   *  Actions that will be excecuted on $_GET request
   *  @var action     publish, unpublish, trash...
   *  @var id         integer, page id / selector id
   */
  public function adminActions() {
    $this->files->include("./actions.php");
  }


  /**
   *  Process Ajax request
   *  This will run in init method,
   *  Module is autoload, so it will listen and process ajax requests submited to the current page "./"
   *  @var ajax_action    publish, unpublish, trash...
   *  @var id             integer, page id / selector id
   *
   */
  public function adminAjax() {

    if($this->config->ajax) {

      $this->files->include("./actions-ajax.php");

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
     *
     */
    if($this->input->get->back_url) {
      // decode back_url:  ~ to &  - see @method pageEditLink()
      $back_url_decoded = str_replace("~", "&", $this->input->get->back_url);
      $this->session->set("back_url", $back_url_decoded);
    }


    /**
     *  Set the breadcrumbs
     *  add $_SESSION["back_url"] to the breacrumb link
     *
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


  public function urlSegment() {
    /**
     *	Get current url segments.
     *	We are looking for pagination segments: "page2, page3...",
     *  And current GET variables: "?id=123..."
     *	We will be adding this segment at the end of the links.
    */

    $currentURL = $_SERVER['REQUEST_URI'];
    $url_segment = explode('/', $currentURL);
    $url_segment = $url_segment[sizeof($url_segment)-1];
    return $url_segment;

  }


  /**
   *  Page Edit Link
   *  Use this method to generate page edit link.
   *  @param integer $id  Page ID
   *  @example href='{$this->pageEditLink($item->id)}';
   *
   */
  public function pageEditLink($id) {

    /**
     *	Get current url and it's last segment so we can go back to same page later on.
     *	We are looking for pagination related segments like "page2, page3...",
     *  including current GET variables.
     *	We will be passing this segment string as a GET variable via page edit link.
     *
     */
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
   *
   */
  public function newPageLink($parent_id) {
    return $this->config->urls->admin . "page/add/?parent_id={$parent_id}&new_back={$this->page->name}";
  }


  /**
   *	This is our main redirect function.
   *	We are using this function to redirect back to previews page
   *  on save+exit and save+publish actions
   *  based on $_SESSION["back_url"] and $_SESSION["new_back"]
   *
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

  /* ----------------------------------------------------------------
    Helper Methods
  ------------------------------------------------------------------- */

  /**
   *  Save Module Settings
   *
   *  @param string $module     module class name
   *  @param array $data        module settings
   *
   */
  public function saveModule($module, $data = []) {
    $old_data = $this->modules->getModuleConfigData($module);
    $data = array_merge($old_data, $data);
    $this->modules->saveModuleConfigData($module, $data);
  }

  // Check if multilanguage is installed
  public function isMultiLang($debug = false) {

    $errors = [];

    $lng_modules = [
      "FieldtypePageTitleLanguage",
      "FieldtypeTextLanguage",
      "FieldtypeTextareaLanguage",
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

  /* ----------------------------------------------------------------
    AIOM
  ------------------------------------------------------------------- */
  
  /**
   *  Clear AIOM cache
	 *	@see compileLess()
	 *	Reload modules
  */
  public function clearCache() {
		$this->compileLess();
    $this->modules->refresh();
  }

  /**
   *  Clear AIOM cache
	 *	Delete aiom cache files and change css_prefix
   *	to force browser to clear cache.
   *  @return void
  */
  public function compileLess() {

    // delete AIOM cached css files
    $aiom_cache = $this->config->paths->assets."aiom";
    $aiom_cache_files = glob("$aiom_cache/*");
    foreach($aiom_cache_files as $file) {
        if(is_file($file))
        unlink($file);
    }

    $random_prefix = "css_".rand(10,1000)."_";
    $this->moduleSettings("AllInOneMinify", ["stylesheet_prefix" => "$random_prefix"]);

  }

}
