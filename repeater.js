function createInput(name, placeholder = "") {

  let input = document.createElement("INPUT");
  input.setAttribute("type", "text");
  input.setAttribute("name", name);
  input.setAttribute("placeholder", placeholder)
  input.classList.add("uk-input");

  return input;

}

function createSelect(name, options, placeholder = "", selected = "") {

  let select = document.createElement("SELECT");
  select.setAttribute("name", name);
  select.classList.add("uk-select");
  select.style.width = "100%";

  if(placeholder != "") {
    var option = document.createElement("OPTION");
    option.value = "";
    option.text = `- ${placeholder} -`;
    select.appendChild(option);
  }

  for (let key in options){
    if(options.hasOwnProperty(key)){

      let value = key;
      let label = options[key];

      var option = document.createElement("OPTION");
      option.value = value;
      option.text = label;
      if(value == selected) option.setAttribute("selected", "selected");
      select.appendChild(option);

    }
  }

  return select;

}

function addRow(container, fields, dragDrop = false) {

  // Create Row (grid)
  let grid = document.createElement("DIV");
  grid.classList.add("uk-grid");
  grid.classList.add("uk-grid-small");
  grid.classList.add("uk-flex-middle");


  // Create handle element
  if(dragDrop) {
    let handleDiv = document.createElement("DIV");
    handleDiv.classList.add("uk-width-auto@m");
    let handle = document.createElement("SPAN");
    handle.classList.add("handle");
    handle.classList.add("fas");
    handle.classList.add("fa-arrows-alt");
    //handle.innerHTML = '<i class="fas fa-bars"></i>';
    handleDiv.appendChild(handle);
    grid.appendChild(handleDiv);
  }

  // Loop troung fields and add them to the row (grid)
  for(let i = 0; i < fields.length; i++) {

    let field = fields[i];
    // console.log(field.type);

    // Create DIV
    let div = document.createElement("DIV");
    let gridSize = field.grid ? field.grid : "expand@m";
    div.classList.add(`uk-width-${gridSize}`);

    // Create field and append it to the div
    if (field.type === "text") {
      let input = createInput(field.name, field.placeholder);
      if(field.required && field.required === true) input.setAttribute("required", "required");
      div.appendChild(input);
    } else if (field.type === "select") {
      let select = createSelect(field.name, field.options, field.placeholder, field.default);
      if(field.required && field.required === true) select.setAttribute("required", "required");
      div.appendChild(select);
    }

    // Append div to the grid element
    grid.appendChild(div);

  }

  // Create Delete Button Element
  let delDiv = document.createElement("DIV");
  delDiv.classList.add("uk-width-auto@m");
  let delButton = document.createElement("A");
  delButton.setAttribute("href", "#");
  delButton.classList.add("del-row");
  delButton.innerHTML = '<i class="fas fa-times uk-text-danger"></i>';
  delDiv.appendChild(delButton);

  // Append del div
  grid.appendChild(delDiv);

  // Append grid to the container
  container.appendChild(grid);

   // handle Delete Row
   delButton.addEventListener("click", e => {
    e.preventDefault();
    grid.remove();
  });

}
