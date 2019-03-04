<?php
/**
 *  KreativanHelper Module
 *
 *  @author Ivan Milincic <lokomotivan@gmail.com>
 *  @copyright 2018 Ivan Milincic
 *  
 *  Admin:
 *  @method     adminActions() -- page actions
 *  @method     adminAjax() -- page actions ajax
 *  @method     dragDropSort() -- drag and drop sort pages
 *     
 *  Custom UI:
 *  @method     includeAdminFile($this, admin.php, "main");
 *  @method     hidePages() -- gied pages from page tree
 *  @method     adminPageEdit() -- page edit for custom module
 *  @method     redirect() -- redirect method for custom ui    
 *  @method     pageEditLink($id) -- get page edit link in custom ui
 *  @method     newPageLink($parent_id) -- create new page link for custom ui 
 *
*/

class KreativanHelper extends WireData implements Module {

    public static function getModuleInfo() {
        return array(
            'title' => 'Kreativan Helper',
            'version' => 100,
            'summary' => 'Helper methods used in custom admin UI...',
            'icon' => 'code-fork',
            'singular' => true,
            'autoload' => true
        );
    }

    public function init() {

        // if its admin page add custom css/js files
        if(strpos($_SERVER['REQUEST_URI'], $this->wire('config')->urls->admin) === 0) {
            //$this->config->styles->append($this->config->urls->siteModules . $this->className() . "/admin.css");
            //$this->config->styles->append($this->config->urls->siteModules . $this->className() . "/style.css");
            $this->config->styles->append($this->config->urls->siteModules . $this->className() . "/ivm.css");
            $this->config->scripts->append($this->config->urls->siteModules . $this->className() . "/helper.js");
        }

        // display messages if session alert and status vars are set
        if($this->session->admin_status == 'message') {
            $this->message($this->session->admin_alert);
        } elseif($this->session->admin_status == 'warning') {
            $this->warning($this->session->admin_alert);
        } elseif($this->session->admin_status == 'error') {
            $this->error($this->session->admin_alert);
        }

        // reset / delete status and alert session vars
        $this->session->remove('admin_status');
        $this->session->remove('admin_alert');
		
		
		/**
         *  Set @var new_back session
         * 
         *  This is used this to redirect back to module page,
         *  after creating new page.
         *  See @method newPageLink()
         * 
         */
        if($this->input->get->new_back) {
            $this->session->set("new_back", $this->input->get->new_back);
        }

        /**
         *  If there is @var new_back session,
         *  redirect back to the module on page save + exit
         *  See @method redirect 
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

    /* ==========================================================================  
        Admin Actions
    ==========================================================================  */

    /**
     *  Admin Actions
     *  Actions that will be excecuted on $_GET request
     * 
     *  @var action     publish, unpublish, trash...
     *  @var id         integer, page id / selector id
     * 
     *  @example        
     *  <a href="./?action=publish&id=123"></a>
     *
     */
    public function adminActions() {
        $this->files->include("./actions.php");
    }


    /**
     *  Process Ajax request
     *  This will run in init method,
     *  Module is autoload, so it will listen and process ajax requests submited to the current page "./"
     * 
     *  @var ajax_action    publish, unpublish, trash...
     *  @var id             integer, page id / selector id
     * 
     *  @example use it in a table...
     * 
     *  <td>
     *      <a href="#" class="ivm-ajax-button" data-id="<?=$item->id?>" data-action="publish">
     *          <i class="fa fa-toggle-on"></i>
     *      </a>
     *  </td>
     *
     */
    public function adminAjax() {

        if($this->config->ajax) {

            $this->files->include("./actions-ajax.php");

        }

    }

    /**
     * 
     *	Sort Pages drag and drop
     *  Run this in init method
     *  
     *  @example
     *  
     *  <table>
     *      <tbody id="ivm-sortable">
     *          <tr class='ivm-ajax-parent' data-sort='<?= $item->sort ?>' data-id='<?= $item->id ?>'>
     *              <td>
     *                  <div class="handle"><i class='fa fa-bars'></i></div>  
     *              </td>
     *              <td>My Item</td>
     *          </tr>
     *      </tbody>
     *  </table>
     * 
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
	
	
	/* =========================================================== 
        Admin Methods
    =========================================================== */
	
	/**
     *  Include Admin File
     *  This will include admin php file from the module folder
     *  @var module         module we are using this method in, usually its $this
     *  @var file_name		php file name from module folder
     *	@var page_name		used to indentify active page
     *  
     *  @example return $this->modules->get("KreativanHelper")->includeAdminFile($this, "admin.php", "main");
     *
     */
    public function includeAdminFile($module, $file_name, $page_name) {

        /** 
         *  Remove @var back_url session 
         *  Remove @var new_back session 
         *  This will reset current session vars,
         *  used for redirects on page save + exit
         * 
         */
        $this->session->remove("back_url");
        $this->session->remove("new_back");

        $vars = [
            "this_module" => $module,
            "page_name" => $page_name,
            "module_edit_URL" => $this->urls->admin . "module/edit?name=" . $module->className() . "&collapse_info=1",
            "helper" => $this,
        ];

        $template_file = $this->config->paths->siteModules . $module->className() . "/" . $file_name;
        return $this->files->render($template_file, $vars);

    }
	
	/**
     *  Intercept page tree json and remove page from it
     *  We will remove page by its template
     *  @var pagetemplate template of the current page in a loop
     *  @var tmpArr Array of tamplates we wish to remove
     *
     */
    public function hidePages(HookEvent $event){

        // get system pages
        $sysPagesArr = $this->modules->get("cmsCore")->sys_pages;

        // aditional pages to hide by ID
        $customArr = [];
        if($this->hide_admin == "1") {
            array_push($customArr, "2");
        }

        if($this->config->ajax) {

            // manipulate the json returned and remove any pages found from array
            $json = json_decode($event->return, true);
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
	
	/**
	 *	Edit admin page from custom UI
	 *	@example admin/MODULE_URL/edit/id?=PAGE_ID
	 *	This method handles redirects on page save+exit, 
	 *	set the custom breadcrumbs etc...
	 *	Use this method inside modules: public function executeEdit()
	 *	
     *	@example:
	 *	public function executeEdit() {
	 *		return $this->modules->get("KreativanHelper")->adminPageEdit();
	 *	}
	 *
	 */

	public function adminPageEdit() {

        /**
         *  Set @var back_url session var
         *  So we can redirect back where we left
         * 
         */
        if($this->input->get->back_url) {
            $this->session->set("back_url", $this->input->get->back_url);
        }

        /**
         *  Redirect on save + exit
         *  based on the @var back_url using @method redirect 
         * 
         */
        if($this->session->get("back_url")) {
            if(($this->input->post('submit_save') == 'exit') || ($this->input->post('submit_publish') == 'exit')) {
                $this->input->post->submit_save = 1;
                $this->addHookAfter("Pages::saved", $this->modules->get("KreativanHelper"), "redirect");
            }
        }


        /**
         *  Set the breadcrumbs 
         *  add @var back_url to the breacrumb link 
         * 
         */
        $this->fuel->breadcrumbs->add(new Breadcrumb($this->page->url.$this->input->get->back_url, $this->page->title));

        // Execute Page Edit
        $processEdit = $this->modules->get('ProcessPageEdit');
        return $processEdit->execute();

    }
	
	/**
     *  Page Edit Link
     *  Use this method to generate page edit link.
     *  @var id     integer, page id 
     *  @example    href='{$this->pageEditLink($item->id)}';
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
        return $this->page->url . "edit/?id=$id&back_url={$url_segment}";

    }

    /**
     *  New Page Link
     *  Use this method to generate new page link
     *  @var parent_id      integer, parent page id
     *  @example            href='{$this->newPageLink($parent_id)}';
     * 
     */
    public function newPageLink($parent_id) {
        return $this->config->urls->admin . "page/add/?parent_id={$parent_id}&new_back={$this->page->name}";
    }
	
	/**
     *	This is our main redirect function.
     *	We are using this function to redirect back to previews page 
     *  on save+exit and save+publish actions
     *  based on @var back_url and @var new_back session
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

}
