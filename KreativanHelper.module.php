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
 *  Utility:
 *  @method     clearCache() -- clear AIOM cache and refresh modules
 *	@method     compileLess()  -- clear AIOM cache reset css_ prefix
 *	@method		getFolders($dir) 
 *	@method		uplaodFile() -- uplaod file to the destination folder
 *  
 *  API:
 *	@method 	moduleSettings() -- chanage module settings
 *
 *  @method     setFieldOptions() -- use this method to change field option based on template
 *  @method     createRepeater() -- create Repeater field
 *  @method     setRepeaterFieldOptions() -- set field options inside a Repeater or FieldsetPage
 *  @method     createFieldsetPage() -- craete FieldsetPage field
 *  @method     createOptionsField() -- create Options field
 *	@method		addTemplateField() -- add new field to the specific position in template (before-after existing field)
 *  
 *  Custom:
 *  @method     createTemplateStructure() -- create Main Page -> Subpages
 *  @method     deleteTemplateStructure() -- delete pages, templates, fields
 *
*/

class KreativanHelper extends WireData implements Module {

    public static function getModuleInfo() {
        return array(
            'title' => 'Kreativan Helper',
            'version' => 100,
            'summary' => 'Helper methods...',
            'icon' => 'code-fork',
            'singular' => true,
            'autoload' => true
        );
    }

    public function init() {

        // if its admin page add custom css/js files
        if(strpos($_SERVER['REQUEST_URI'], $this->wire('config')->urls->admin) === 0) {
            //$this->config->styles->append($this->config->urls->siteModules . "KreativanHelper/admin.css");
            //$this->config->styles->append($this->config->urls->siteModules . "KreativanHelper/style.css");
            $this->config->styles->append($this->config->urls->siteModules . "KreativanHelper/ivm.css");
            $this->config->scripts->append($this->config->urls->siteModules . "KreativanHelper/helper.js");
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
     *          <tr data-sort='<?= $item->sort ?>' data-id='<?= $item->id ?>'>
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

    /* ==================================================================================
        Utility
    ===================================================================================== */

    /**
     *  clearCache()
     *  Reload modules, compile less and clear cache
     *
     */
    public function clearCache() {

        /**
         *  Reset AIOM
         * 
         */

        // delete aiom cached css files
        $aiom_cache = $this->config->paths->assets."aiom";
        $aiom_cache_files = glob("$aiom_cache/*");
        foreach($aiom_cache_files as $file) {
            if(is_file($file))
            unlink($file);
        }

        // add new random css prefix to avoid browser cache
        $random_prefix = "css_".rand(10,100)."_";
        $old_data = $this->modules->getModuleConfigData('AllInOneMinify');
        $new_data = array('stylesheet_prefix' => $random_prefix);
        $data = array_merge($old_data, $new_data);
        $module = 'AllInOneMinify';
        $this->modules->saveModuleConfigData($module, $data);

        /**
         *  Refresh Modules
         * 
         */

        $this->modules->refresh();

    }
	
	/**
     *  compileLess()
     * 
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
	
	/**
     *  Get Folders 
     * 
     */
    public function getFolders($dir) {
        return array_filter(glob("{$dir}*"), 'is_dir');
    }
	
	
	/**
	 * 	Uplaod File
	 * 
	 * 	@param file_field_name	string, name of the file field in the uplaod form
	 *  @param dest             string, path to upload folder
	 *  @param valid            array, allowed file extensions
	 * 
	 */
	public function uplaodFile($file_field_name = "", $dest = "", $valid = ['jpg', 'jpeg', 'gif', 'png']) {

		// if there is no uplaod path trow error
		if(!is_dir($dest)) {
			if(!wireMkdir($dest)) $this->error("No upload path!"); 
		}

		// WireUpload
		$upload = new WireUpload("$file_field_name");
		$upload->setMaxFiles(1);
		$upload->setOverwrite(true);
		$upload->setDestinationPath($dest);
		$upload->setValidExtensions($valid); 

		try {
			// execute upload
			$files = $upload->execute();
			// dump($files);
		} catch(Exception $e) {
			$error = $e->getMessage();
			$this->error($error); 
		}

	}


    /* ==================================================================================
        API Methods
    ===================================================================================== */
	
	/**
     *  Module Settings
     *  
     *  @param module   str     module class name
     *  @param data     array   module settings ["name" => "value"]
     * 
     */
    public function moduleSettings($module, $data = []) {

        $old_data = $this->modules->getModuleConfigData($module);
        $data = array_merge($old_data, $data);
        $this->modules->saveModuleConfigData($module, $data);

    }

    /**
     *  Change Field Options for specific template 
     * 
     *  @param template     string -- Template name
     *  @param field        string -- Field Name
     *  @param options      array -- array of options eg: ["option" => value]
     * 
     *  @example $this->setFieldOptions("home", "text", ["label" => "My Text"]);
     *  
     */

    public function setFieldOptions($template, $field, $options) {
        // change field settings for this template
        $t = wire('templates')->get($template);
        $f = $t->fieldgroup->getField($field, true);
        foreach($options as $key => $value) {
            $f->$key = $value;
        }
        $this->fields->saveFieldgroupContext($f, $t->fieldgroup);//save new setting in context
    }

    /**
     *  Create Repeater
     * 
     *  @param name         str -- The name of your repeater field
     *  @param label        str -- The label for your repeater
     *  @param fields       array -- Array of fields names to add to repeater
     *  @param items_label  str -- Lable for repeater items eg: {title} 
     *  @param tags         str -- Tags for the repeater field
     * 
     *  @example    $this->createRepeater("dropdown", "Dropdown", $fields_array, "{title}", "Repeaters");
     * 
     */     
    public function createRepeater($name, $label, $fields, $items_label, $tags = "") {

        // Create field
        $f = new Field();
        $f->type = $this->modules->get("FieldtypeRepeater");
        $f->name = $name;
        $f->label = $label;
        $f->tags = $tags;
        $f->repeaterReadyItems = 3;
        $f->repeaterTitle = $items_label;

        // Create fieldgroup
        $fg = new Fieldgroup();
        $fg->name = "repeater_$name";

        // Add fields to fieldgroup
        foreach($fields as $field) {
            $fg->append($this->fields->get($field));
        }

        $fg->save();

        // Create template
        $tmp = new Template();
        $tmp->name = "repeater_$name";
        $tmp->flags = 8;
        $tmp->noChildren = 1;
        $tmp->noParents = 1;
        $tmp->noGlobal = 1;
        $tmp->slashUrls = 1;
        $tmp->fieldgroup = $fg;

        $tmp->save();

        // Setup page for the repeater - Very important
        $p = "for-field-{$f->id}";
        $f->parent_id = $this->pages->get("name=$p")->id;
        $f->template_id = $tmp->id;
        $f->repeaterReadyItems = 3;

        // Now, add the fields directly to the repeater field
        foreach($fields as $field) {
            $f->repeaterFields = $this->fields->get($field);
        }

        $f->save();

        return $f;

    }

    /**
     *  Create FieldsetPage
     * 
     *  This is basically same as repeater, except it's using "FieldtypeFieldsetPage" module, and using fewer params.
     *  To change field options we can use same @method repeaterFieldOptions();
     * 
     *  @param name         str -- The name of your repeater field
     *  @param label        str -- The label for your repeater
     *  @param fields       array -- Array of fields names to add to repeater
     *  @param tags         str -- Tags for the repeater field
     * 
     *  @example    $this->createFieldsetPage("my_block", "My Block", $fields_array, "Blocks");
     * 
     */     
    public function createFieldsetPage($name, $label, $fields, $tags = "") {

        // Create field
        $f = new Field();
        $f->type = $this->modules->get("FieldtypeFieldsetPage");
        $f->name = $name;
        $f->label = $label;
        $f->tags = $tags;

        // Create fieldgroup
        $fg = new Fieldgroup();
        $fg->name = "repeater_$name";

        // Add fields to fieldgroup
        foreach($fields as $field) {
            $fg->append($this->fields->get($field));
        }

        $fg->save();

        // Create template
        $tmp = new Template();
        $tmp->name = "repeater_$name";
        $tmp->flags = 8;
        $tmp->noChildren = 1;
        $tmp->noParents = 1;
        $tmp->noGlobal = 1;
        $tmp->slashUrls = 1;
        $tmp->fieldgroup = $fg;

        $tmp->save();

        // Setup page for the repeater - Very important
        $p = "for-field-{$f->id}";
        $f->parent_id = $this->pages->get("name=$p")->id;
        $f->template_id = $tmp->id;

        // Now, add the fields directly to the repeater field
        foreach($fields as $field) {
            $f->repeaterFields = $this->fields->get($field);
        }

        $f->save();

        return $f;

    }

    /**
     *  Repeater & FieldsetPage Field Options
	 *	(Yep, FieldsetPage works same as Repeater)
     * 
     *  @method setFieldOptions()  Using this same method with custom params. Just because repeater template name has "repaeter_" prefix
     *  @param  repeater_name   string -- name of the repeater field
     *  @param  field_name      string -- name of the field
     *  @param  options         array -- field options ["option" => "value"]
     *  
     *  @example $this->setRepeaterFieldOptions("my_repeater_name", "text", ["label" => "My Text"]);
     * 
     */
    public function setRepeaterFieldOptions($repeater_name, $field_name, $options) {
        $this->setFieldOptions("repeater_$repeater_name", $field_name, $options);
    }


    /**
     *  Create Options Field
     *  @param inputfield   string -- InputfieldRadios / InputfieldAsmSelect / InputfieldCheckboxes / InputfieldSelect / InputfieldSelectMultiple
     *  @param name         string -- Field name
     *  @param label        string -- field label
     *  @param options_arr  array -- eg: ["one", "two", "three"]
     *  @param tags         string -- Field tag
     * 
     */
    public function createOptionsField($inputfield, $name, $label, $options_arr, $tags = "") {


        $i = 1;
        $options = "";
        foreach($options_arr as $opt) {
            $options .= $i++ . "={$opt}\n";
        }

        $f = new Field();
        $f->type = $this->modules->get("FieldtypeOptions");
        $f->inputfieldClass = $inputfield; // input type: radio, select etc...
        $f->name = $name;
        $f->label = $label;
        $f->tags = $tags;
        $f->save(); 
        // save before adding options
        // $options = "1=Blue\n2=Green\n3=Brown\n";
        $set_options = new \ProcessWire\SelectableOptionManager();
        $set_options->setOptionsString($f, $options, false);
        $f->save();

        // Radio options 1 column
        if($inputfield == "InputfieldRadios") {
            $f->required = "1";
            $f->defaultValue = "1";
            $f->optionColumns = "1";
            $f->save();
        }

    }
	
	/**
     *  addTemplateField()
     *  Add field to the specific position in template (before-after existing field)
     *  
     *  @param  tmpl        	string, template name
     *  @param  new_field       string, name of the field we want to add
     *  @param  mark_field      string, field name, we will add new field before or after this field
     *  @param  before_after    string, before / after 
     * 
     */
    public function addTemplateField($tmpl, $new_field, $mark_field, $before_after = "after") {

        // get template
        $template = $this->templates->get("$tmpl");
		
		// get existing field from the template, 
        // we will insert new field before or after this field
        $existingField = $template->fieldgroup->fields->get("$mark_field");

        // new field that we want to insert
        $newField = $this->fields->get("$new_field");

        // insert new field before existing one
        if($before_after == "before") {
            $template->fieldgroup->insertBefore($newField, $existingField);
        } else {
            $template->fieldgroup->insertAfter($newField, $existingField);
        }

        $template->fieldgroup->save();

    }
	

    /**
     *  Create Template Structure
     *  @example Page -> Subpage
     * 
     *  @param main array -- eg: ["name" => "my_template_name", "fields" => ["One", "Two", "Three"]];
     *  @var name string -- template name
     *  @var fields array --  template fields
     *  @var icon string (fa-icon) -- template icon
     *  @var parent page id
     *  @var page_title string
     * 
     *  @param item array -- eg: ["name" => "my_template_name", "fields" => ["One", "Two", "Three"]];
     *  @var name string -- template name
     *  @var fields array --  template fields
     *  @var icon string (fa-icon) -- template icon
     *  @var page_title string
     * 
     *  @param tag string
     * 
     * 
     */
    public function createTemplateStructure($main, $item, $tag = "") {

        $main_name          = $main["name"] ? $main["name"] : "";
        $main_fields        = $main["fields"] ? $main["fields"] : "";
        $main_icon          = $main["icon"] ? $main["icon"] : "";
        $main_parent        = $main["parent"] ? $main["parent"] : "";
        $main_page_title    = $main["page_title"] ? $main["page_title"] : "";

        $item_name          = $item["name"] ? $item["name"] : "";
        $item_fields        = $item["fields"] ? $item["fields"] : "";
        $item_icon          = $item["icon"] ? $item["icon"] : "";
        $item_page_title    = $item["page_title"] ? $item["page_title"] : "";

        // Main Fieldgroup
        $main_fg = new Fieldgroup();
        $main_fg->name = $main_name;
        foreach($main_fields as $field) {
            $main_fg->add($this->fields->get($field)); 
        }
        $main_fg->save();

        // Main Template 
        $main_t = new Template();
        $main_t->name = $main_name;
        $main_t->fieldgroup = $main_fg;
        if(!empty($main_icon)) {
            $main_t->pageLabelField = "$main_icon";
        }
        $main_t->save();
        

        // Item Fieldgroup
        $item_fg = new Fieldgroup();
        $item_fg->name = $item_name;
        foreach($item_fields as $field) {
            $item_fg->add($this->fields->get($field)); 
        }
        $item_fg->save();

        // Item Template 
        $item_t = new Template();
        $item_t->name = $item_name;
        $item_t->fieldgroup = $item_fg; // add the field group
        $item_t->save();

        // Item Template options
        $item_t = wire('templates')->get($item_name);
        $item_t->noChildren = "1";
        $item_t->tags = $tag;
        $item_t->pageLabelField = $item_icon;
        $item_t->parentTemplates = array(wire('templates')->get($main_name)); // allowedForParents
        $item_t->save();


        // Main Template Options
        $main_t = wire('templates')->get("main-menu");
        $main_t->noParents = '-1';
        $main_t->tags = $tag;
        $main_t->pageLabelIcon = $main_icon;
        $main_t->childTemplates = array(wire('templates')->get($item_name)); // allowedForChildren
        $main_t->save();

        // Create Example Pages
        if(!empty($main_parent)) {

            $main_p = new Page();
            $main_p->template = $main_name;
            $main_p->parent = $main_parent;
            $main_p->title = $main_page_title;
            $main_p->save();

            $item_p = new Page();
            $item_p->template = $item_name;
            $item_p->parent = $main_p;
            $item_p->title = $item_page_title;
            $item_p->save();

        }

    }

    /**
     *  Delete Template Structure
     *  @param temp_array array -- template names
     *  @param fields_arr array -- field names
     * 
     */
    public function deleteTemplateStructure($temp_array, $fields_arr) {

        // 1. Delete Pages
        foreach($temp_array as $tmp) {
            $p_arr = $this->pages->find("template=$tmp, include=all");
            if($p_arr->count) {
                foreach($p_arr as $p) {
                    $p->delete(true);
                }
            }
        }

        // 2. Delete Templates
        foreach($temp_array as $tmp) {
            $t = $this->templates->get($tmp);
            if($t && !empty($t)) {
                $this->templates->delete($t);
            }
        }

        // 3. Delete Fieldgroup
        foreach($temp_array as $tmp) {
            $fg = $this->fieldgroups->get($tmp);
            if($fg && !empty($fg)) {
                $this->fieldgroups->delete($fg);
            }
        }

        // 4. Delete Fields
        foreach($fields_arr as $field) {
            $f = $this->fields->get($field);
            if($f && !empty($f)) {
                $this->fields->delete($f);
            }
        }



    }

}
