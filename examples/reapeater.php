<script>
window.addEventListener("DOMContentLoaded", function() {

	let button = document.getElementById("repeater-button");

	button.addEventListener("click", e => {
  	e.preventDefault();

	  let my_options = {
	    1: "Options 1",
	    2: "Options 2",
	    3: "Options 3"
	  };

	  let fields = [
	    {
	      type: "select",
	      name: "lng_level[]",
	      options: my_options,
	      placeholder: "Level",
	      default: "2",
        grid: "1-3@m",
				required: true
	    },
      {
	      type: "text",
	      name: "lng_title[]",
	      placeholder: "Title",
				grid: "expand@m",
				required: true
	    }
	  ];

	  let container = document.getElementById("lngs");
	  addRow(container, fields);

  });

});
</script>


<div id="lngs"></div>
<div class="uk-margin-small-top">
  <a id="repeater-button" class="uk-button uk-button-primary uk-button-small" href="#">
    Add
  </a>
</div>
