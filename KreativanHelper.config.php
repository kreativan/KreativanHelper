<?php
/**
 * Configure the Hello World module
 *
 * This type of configuration method requires ProcessWire 2.5.5 or newer.
 * For backwards compatibility with older versions of PW, you'll want to
 * instead want to look into the getModuleConfigInputfields() method, which
 * is specified with the .module file. So we are assuming you only need to
 * support PW 2.5.5 or newer here.
 *
 * For more about configuration methods, see here:
 * http://processwire.com/blog/posts/new-module-configuration-options/
 *
 *
 */

class KreativanHelperConfig extends ModuleConfig {

	public function getInputfields() {
		$inputfields = parent::getInputfields();

		// create templates options array
		$templatesArr = array();
		foreach($this->templates as $tmp) {
			$templatesArr["$tmp"] = $tmp->name;
		}


		$wrapper = new InputfieldWrapper();


		// hide_system_pages
		$f = $this->wire('modules')->get("InputfieldRadios");
		$f->attr('name', 'hide_system_pages');
		$f->label = 'Hide system pages from page tree';
		$f->options = array(
			'1' => "Yes",
			'2' => "No"
		);
		$f->required = true;
		$f->defaultValue = "2";
		$f->optionColumns = 1;
		$f->columnWidth = "100%";
		$inputfields->add($f);

		// sys_pages
		$f = $this->wire('modules')->get("InputfieldAsmSelect");
		$f->attr('name', 'sys_pages');
		$f->label = 'System Pages';
		$f->description = __("Additional pages that will be hidden from page tree");
		$f->options = $templatesArr;
		$inputfields->add($f);

		// load_admin_style
		$f = $this->wire('modules')->get("InputfieldRadios");
		$f->attr('name', 'load_admin_style');
		$f->label = 'Load custom admin style';
		$f->options = array(
			'1' => "Yes",
			'2' => "No"
		);
		$f->required = true;
		$f->defaultValue = "1";
		$f->optionColumns = 1;
		$f->columnWidth = "100%";
		$inputfields->add($f);

		// fa
		$f = $this->wire('modules')->get("InputfieldRadios");
		$f->attr('name', 'fa');
		$f->label = 'Load Font Awesome 5';
		$f->options = array(
			'1' => "Yes",
			'2' => "No"
		);
		$f->required = true;
		$f->defaultValue = "1";
		$f->optionColumns = 1;
		$f->columnWidth = "20%";
		$inputfields->add($f);

		// fa_link
		$f = $this->wire('modules')->get("InputfieldText");
		$f->attr('name', 'fa_link');
		$f->label = 'Font awesome 5 css link';
		$f->value = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css";
		$f->columnWidth = "80%";
		$inputfields->add($f);


		// render fields
		return $inputfields;


	}

}
