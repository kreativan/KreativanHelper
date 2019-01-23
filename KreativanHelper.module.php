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
 *  
 *  API:
 *  @method     fieldOptions() -- use this method to change field option based on template
 *  @method     createRepeater() -- create Repeater field
 *  @method     repeaterFieldOptions() -- set field options inside a Repeater or FieldsetPage
 *  @method     createFieldsetPage() -- craete FieldsetPage field
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
            $this->config->scripts->append($this->config->urls->siteModules . "KreativanHelper/helper.js");
        }

        // display messages if session alert and status vars are set
        if($this->session->status == 'message') {
            $this->message($this->session->alert);
        } elseif($this->session->status == 'warning') {
            $this->warning($this->session->alert);
        } elseif($this->session->status == 'error') {
            $this->error($this->session->alert);
        }

        // reset / delete status and alert session vars
        $this->session->remove('status');
        $this->session->remove('alert');

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
     *  @example    use it in a table...
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


    /* ==================================================================================
        API Methods
    ===================================================================================== */

    /**
     *  Change Field Options 
     *  
     *  @param template     string -- Template name
     *  @param field        string -- Field Name
     *  @param options      array -- array of options eg: ["option" => value]
     * 
     *  @example $this->fieldOptions("home", "text", ["label" => "My Text"]);
     *  
     */

    public function fieldOptions($template, $field, $options) {
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
    public function createRepeater($name, $label, $fields, $items_label, $tags) {

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
    public function createFieldsetPage($name, $label, $fields, $tags) {

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
     * 
     *  @method fieldOptions()  Using this same method with custom params. Just because repeater template name has "repaeter_" prefix
     *  @param  repeater_name   string -- name of the repeater field
     *  @param  field_name      string -- name of the field
     *  @param  options         array -- field options ["option" => "value"]
     *  
     *  @example $this->fieldOptions("my_repeater_name", "text", ["label" => "My Text"]);
     * 
     */
    public function repeaterFieldOptions($repeater_name, $field_name, $options) {
        $this->fieldOptions("repeater_$repeater_name", $field_name, $options);
    }
    

}
