$(document).ready(function () {

    /**
     *  Display modal confirm
     *  It's just gonna prompt you "are you sure" and redirect to the same url
     *  Using this on URLs with $_GET variables. Eg: for deleteing a page, ./?action=delete&id=123
     * 
     */
    function modalConfirm() {
        var element = document.querySelectorAll('.ivm-modal-confirm');
        for (let i = 0; i < element.length; i++) {
            UIkit.util.on(element[i], 'click', function (e) {
                e.preventDefault();
                e.target.blur();
                UIkit.modal.confirm('<h3 class="uk-text-center">Are you sure?</h3>').then(function () {
                    let thisHref = element[i].getAttribute('href');
                    // console.log(thisHref);
                    window.location.replace(thisHref);
                }, function () {
                    // console.log('Rejected.')
                });
            });
            // console.log(element[i].getAttribute('href'));
        }
    }
    modalConfirm();

    /**
     *  Drag and drop sort pages
     *  @var id int
     * 
     */
    $(function () {

        $('#ivm-sortable').sortable({
            handle: '.handle',
            stop: function (event, ui) {

                $('#ivm-sortable').css('opacity', '0.5');

                var id = $(ui.item).attr('data-id');
                var nextID = $(ui.item).next().attr('data-id');
                var prevID = $(ui.item).prev().attr('data-id');

                var ajaxURL = './';

                $.post(ajaxURL, {
                    id: id,
                    next_id: nextID,
                    prev_id: prevID,
                    action: "drag_drop_sort",
                }).done(function (data) {
                    //console.log('Data Loaded: ' + data);
                    $('#ivm-sortable').css('opacity', '1');
                });

                //console.log(id);

            }
        });

        $('#ivm-sortable').disableSelection();

    });

}); 


/**
 *  Ajax Actions
 *  Send ajax request on ivm-ajax-button click, with page id (data-id) and action name (data-action)
 *  @var thisId         data-id
 *  @var thisAction     data-action
 *  @example <a href="#" class="ivm-ajax-button" data-id="<?= $item->id ?>" data-action="publish"></a>
 * 
 */

$(document).ready(function () {


    $('.ivm-ajax-button').click(function (e) {
        e.preventDefault();

        // DOM elements
        var thisElement = $(this);
        var thisIcon    = $(this).find(".fa");

        // Hide icon add spinner
        thisIcon.addClass("uk-hidden");
        thisElement.append('<i class="fa fa-cog fa-spin fa-fw"></i>');

        // Get data
        var thisId = $(this).attr("data-id");
        var thisAction = $(this).attr("data-action");

        // Data to sent
        var thisData = { 
            id: thisId, 
            ajax_action: thisAction
        };

        // Run ajax request
        $.ajax({
            type: 'POST',
            url: "./",
            data: thisData,
            dataType: "json",
            success: function (response) {

                //console.log(thisAction + " success");

                /**
                 *  Let's do some stuff on success...
                 *  Mark unpublished, remove item from table, count trash etc...
                 * 
                 */

                if (thisAction == "publish") {

                    thisElement.closest(".ivm-ajax-parent").toggleClass("ivm-is-hidden"); 

                } else if (thisAction == "trash") {

                    thisElement.closest(".ivm-ajax-parent").remove();

                }

            },
            error: function (response) {
                console.log(thisAction + " fail");
            }

        }).done(function(response){

            thisElement.find(".fa-spin").remove();
            thisIcon.removeClass("uk-hidden");

        }).fail(function(){
            // do something
        });

    });

}); 


/**
 *  Group Actions
 *  
 */

$(document).ready(function() {

    let buttons = $(".ivm-group-action-button");
    let checkbox = $(".ivm-checkbox");

    checkbox.on("click", function(e) {
        let checked = $("input.ivm-checkbox:checked");
        if(checked.length > 0) {
            buttons.removeAttr("disabled");
        } else {
            buttons.attr("disabled", "disabled");
        }
    });

});


/**
 *  Submit Form on click
 *  Display modal confirm
 *  Requierd Attributes for the clicking element:
 *  data-form=""
 *  data-action=""
 *  @example <a href='#' data-form="#my-form" data-action="action_delete"></a>
 */
function formSubmitConfirm() {

    event.preventDefault();
	let e = event.target.getAttribute("data-form") ? event.target : event.target.parentNode;

    UIkit.modal.confirm('<h3 class="uk-text-center">Are you sure?</h3>').then(function () {

        let formID = e.getAttribute("data-form");
        let action = e.getAttribute("data-action");

        // add input field so we know what action to process
        let input = document.createElement("INPUT");
        input.setAttribute("type", "hidden");
        input.setAttribute("name", action);
        input.setAttribute("value", "1");
        document.querySelector(formID).appendChild(input);

        // Submit Form
        document.querySelector(formID).submit();


    }, function () {

        // console.log('Rejected.')

    });

}



/**
 *  Display modal confirm
 *  this will hyst redirect to the click href on confirm
 *  @example <a href='#' onclick="modalConfirm("Are you sure?", "this will redirect to the href link")">Button</a>
 */
function modalConfirm(title = "Are you sure", text = "") {

    event.preventDefault();
    let e = event.target.getAttribute("href") ? event.target : event.target.parentNode;

    let message = "<h2 class='uk-text-center uk-margin-remove'>" + title + "</h2>";
    message += (text != "") ? "<p class='uk-text-center uk-margin-small'>"+text+"</p>" : "";

    UIkit.modal.confirm(message).then(function () {
        let thisHref = e.getAttribute('href');
        window.location.replace(thisHref);
        console.log(e);
    }, function () {
        // console.log('Rejected.')
    });

}