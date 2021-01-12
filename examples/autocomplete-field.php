<?php namespace ProcessWire;

$actionURL = $page_name == "main" ? "" : $page_name;

// Build form
$form = $this->modules->get("InputfieldForm");
$form->action = "./$actionURL";
$form->method = "GET";
$form->attr("id+name","agency-find"); 

// PageAutocomplete (get users)
$f = $this->modules->get("InputfieldPageAutocomplete");
$f->label = 'Select Agency';
$f->notes = "Find agency by name";
$f->name = 'agency_id';
$f->required = true;
$f->maxSelectedItems = 1;
$f->template_id = $this->templates->get("name=business-page");
$f->searchFields = "name title subtitle";
$f->labelFieldFormat = "{title} "; // ({category.title}, {category.parent.title})";
$f->columnWidth = "100%";
$f->value = $input->get->agency_id;
// Add ffield to the form (do this for each field)
$form->append($f);

echo $form->render();

?>

<script>
const autoCompleteSubmit = () => {

  let form = document.querySelector("#agency-find");
  let input = document.querySelector("#Inputfield_agency_id_input");

  // set input name user_id instead of user_id[]
  let _input = document.querySelector("#Inputfield_agency_id");
  _input.setAttribute("name", "agency_id");
  
  input.addEventListener("change", () => {
    form.submit();
  });

  document.querySelector(".InputfieldPageAutocompleteRemove").addEventListener("click", () => {
    _input.value = "";
    form.submit();
  });

};
autoCompleteSubmit()
</script>
