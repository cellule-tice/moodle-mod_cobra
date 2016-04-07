$(document).ready(init);
var displayParams = new Array();
var ccOrder = new Array();
var jsonObject;
var params;

function init()
{
    initDisplayParams();
    //hide left menu in claroline 1.10
    $('#courseLeftSidebar').hide();
    $('#courseRightContent').css('border','none');
    $('#courseRightContent').css('padding-left','0px');
    if ( $('#toggleLeftMenu') ) $('#toggleLeftMenu').trigger('click');
    $('small').hide();
    $("#glossary").css('height', $("#text").css('height'));

    $(document).on('click', '.lemma', displayDetails);
    $(document).on('click', '.expression', displayDetails);
    $(document).on('click','.cc_source',displayFullCC);
    $(document).on('click','.occ_source',displayFullOcc);
    $(document).on('click','#showCard',displayCard);
    $(document).on('click','a.setVisible', setVisible);
    $(document).on('click','a.setInvisible', setInvisible);
    $(document).on('click','a.moveUp', moveUp);
    $(document).on('click','a.moveDown', moveDown);
    $(document).on('click','a.changeType', changeType);
    $(document).on('click','.glossaryAdd', addToGlossary);
    $(document).on('click','.glossaryRemove', removeFromGlossary);
	$(document).on('click','.gGlossaryRemove', removeFromGlobalGlossary);

// Fetch categories for a given user, and display it inside a qtip
    $("span.showTextListForGlossaryEntry").each(function()
    {
        $(this).qtip({
            content: {
                url: "ajax_handler.php",
                data: { ajaxcall: "getTextListForGlossaryEntry", entityLing: $(this).find("span").attr("class") },
                method: "get"
            },

            show: "mouseover",
            hide: "mouseout",
            position: {
                corner: {
                    target: "topRight",
                    tooltip: "bottomRight"
                }
            },

            style: {
                maxWidth: 300,
                padding: 5,
                background: "#FFFFCC",
                color: "black",
                fontSize: "0.8em",
                textAlign: "center",
                border: {
                    width: 7,
                    radius: 5,
                    color: "#FFFFCC"
                }
            }
        });
    });
    updateMoveIcons();
}

function initDisplayParams()
{
    $.post( 'ajax_handler.php', { ajaxcall: 'getDisplayParams' },
            function(data){
                jsonObject = data;
        });
}

/*
 * interactions during text reading
 */

//display digest for clicked word and short concordances
function displayDetails()
{
    $('.clicked').removeClass('clicked');
    $('.emphasize').removeClass('emphasize');
    var conceptId = $(this).attr('name');
    var isExpr = $(this).hasClass('expression') ? 1 : 0;
    if( isExpr )
    {
    	$('.expression[name=' + conceptId + ']').addClass('emphasize');
    	$(this).prevAll('span.expression').each(function() {
            if( $(this).attr('name') == conceptId )
            {
            	$(this).removeClass('emphasize');
                $(this).addClass('clicked');
            }
            else
            {
            	return false;
            }
        });
    	$(this).nextAll('span.expression').each(function() {
            if( $(this).attr('name') == conceptId )
            {
            	$(this).removeClass('emphasize');
                $(this).addClass('clicked');
            }
            else
            {
            	return false;
            }
        });
        $(this).removeClass('emphasize');
        $(this).addClass('clicked');
    }
    else
    {
        $('.lemma[name=' + conceptId + ']').addClass('emphasize');
        $(this).removeClass('emphasize');
        $(this).addClass('clicked');
    }
    //$('#card').hide();
    $('#full_concordance').hide();
    var detailsDiv = $('#details');
    var textId = getUrlParam('id_text', document.location.href);

    var isExpr = $(this).hasClass('expression') ? 1 : 0;
    var t = this;

    $.post( 'relay.php', { verb: 'displayEntry', concept_id: conceptId, resource_id: textId, is_expr: isExpr, params : jsonObject  },
        function(data){
            var response = JSON.parse(data);
            detailsDiv.html(response.html);
            var parsed = JSON.parse(jsonObject);
            if('SHOW' == parsed.showGlossary)
            {
                var lingEntitySpan = '<span id="currentLingEntity" class="hidden">' + response.lingEntity + '</span>';
                //uncomment to reactivate glossary remove from digest row
                // var glossaryIcon = (true == response.inGlossary ? '<img height="20px" class="glossaryRemove" src="img/glossary_remove.png" title="Supprimer de mon glossaire"/>' : '<img height="20px" class="glossaryAdd" src="img/glossary_add.png" title="Ajouter &agrave; mon glossaire"/>');
                //var angularClick = (false == response.inGlossary ? 'ng-click="addEntry(' + response.lingEntity + ')"' : 'ng-click="removeEntry(' + response.lingEntity + ')"');
                var glossaryIcon = (true == response.inGlossary ? '<img height="20px" class="inGlossary" src="img/in_glossary.png" title="Pr&eacute;sent dans mon glossaire"/>' : '<img height="20px" class="glossaryAdd" src="img/glossary_add.png" title="Ajouter &agrave; mon glossaire"/>');
                var angularClick = (false == response.inGlossary ? 'ng-click="addEntry(' + response.lingEntity + ')"' : '');
                var tr = $('#displayOnClic').find('tr:first').prepend('<th ' + angularClick + ' class="glossaryIcon">' + lingEntitySpan + glossaryIcon + '</th>').addClass('digestRow');
            }
            else
            {
                $('#glossary').remove();
            }
    });
}

//display full text of clicked concordance
function displayFullCC()
{
    var fullCCDiv = $('#full_concordance');
    var id_cc = $(this).attr('name');
    var bg_color = $(this).parent().parent().css("background-color");
    $.post( 'relay.php', { verb: 'displayCC', id_cc: id_cc, params : jsonObject  },
            function(data){
                fullCCDiv.html(data);
                fullCCDiv.css("background-color", bg_color);
                fullCCDiv.show();
        });
}

//display full text of clicked occurrence
function displayFullOcc()
{
    var fullCCDiv = $('#full_concordance');
    var id_occ = $(this).attr('name');
    var bg_color = $(this).parent().parent().css("background-color");
    $.post( 'relay.php', { verb: 'displayCC', id_occ: id_occ, params : jsonObject  },
            function(data){
                fullCCDiv.html(data);
                fullCCDiv.css("background-color", bg_color);
                fullCCDiv.show();
        });
}

//display syntactic card for clicked word
function displayCard()
{
    var cardDiv = $("#card");
    var entryId = $(this).attr('name');
    var currentConstruction = $("#currentConstruction").text();
    var isExpr = $("#currentConstruction").hasClass('expression') ? 1 : 0;
    $.post( 'relay.php', { verb: 'displayCard', entry_id: entryId, currentConstruction: currentConstruction, is_expr: isExpr, params : jsonObject },
            function(data){
                cardDiv.html(data);
                cardDiv.show();
        });
}

/*
 * interaction functions for text list
 */

//make current text visible for students
function setVisible()
{
    var test = $('.textList');
    var resourceType = test.size() != 0 ? 'text' : 'collection';
    var tableRow = $(this.parentNode.parentNode);
    var rawId = tableRow.attr('id');
    var resourceId = rawId.substring( 0, rawId.indexOf( '#', 0 ) );
    $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=setVisible&resource_id=" + resourceId + '&resource_type=' + resourceType,
        success: function(response){
                if( response == "true" )
                {
                    $('.setVisible', tableRow).hide();
                    $('.setInvisible', tableRow).show();
                }
                else
                    alert(response);
            },
        dataType: 'html'
    });

    return false;
}

//make current text invisible for students
function setInvisible()
{
    var test = $('.textList');
    var resourceType = test.size() != 0 ? 'text' : 'collection';
    var tableRow = $(this.parentNode.parentNode);
    var rawId = tableRow.attr('id');
    var resourceId = rawId.substring( 0, rawId.indexOf( '#', 0 ) );
    $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=setInvisible&resource_id=" + resourceId + '&resource_type=' + resourceType,
        success: function(response){
                if( response == "true" )
                {
                    $('.setVisible', tableRow).show();
                    $('.setInvisible', tableRow).hide();
                }
                else
                    alert(response);
            },
        dataType: 'html'
    });

    return false;
}

//move up current text by one row
function moveUp()
{
    // retrieve parent <tr> tag
    var test = $('.textList');
    var resourceType = test.size() != 0 ? 'text' : 'collection';
    var componentDiv = $(this.parentNode.parentNode);
    var previousSibling = $(componentDiv).prev();
    var rawId = $(componentDiv).attr('id');
    var rawPos = $(componentDiv).attr('name');
    var rawSiblingId = $(previousSibling).attr('id');
    var id = rawId.substring( 0, rawId.indexOf( '#', 0 ) );

    var position = rawPos.substring( 0, rawPos.indexOf( '#', 0 ) );
    var siblingId = rawSiblingId.substring( 0, rawSiblingId.indexOf( '#', 0 ) );
    $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=moveUp&resource_id=" + id + "&position=" + position + "&sibling_id=" + siblingId + '&resource_type=' + resourceType,
        success: function(response){
                if( response == "true" )
                {
                    $(previousSibling).attr('name', position + "#pos" );
                    $(componentDiv).attr('name', --position + "#pos" );
                    $(previousSibling).before( $(componentDiv) );
                    updateMoveIcons();
                }
                else alert(response);
            },
        dataType: 'html'
    });

    return false;
}

//move down current text by one row
function moveDown()
{
    // retrieve parent <tr> tag
    var test = $('.textList');
    var resourceType = test.size() != 0 ? 'text' : 'collection';
    var componentDiv = $(this.parentNode.parentNode);
    var nextSibling = $(componentDiv).next();
    var rawId = $(componentDiv).attr('id');
    var rawPos = $(componentDiv).attr('name');
    var rawSiblingId = $(nextSibling).attr('id');
    var id = rawId.substring( 0, rawId.indexOf( '#', 0 ) );
    var position = rawPos.substring( 0, rawPos.indexOf( '#', 0 ) );
    var siblingId = rawSiblingId.substring( 0, rawSiblingId.indexOf( '#', 0 ) );
    $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=moveDown&resource_id=" + id + "&position=" + position + "&sibling_id=" + siblingId + '&resource_type=' + resourceType,
        success: function(response){
                if( response == "true" )
                {
                    $(nextSibling).attr('name', position + "#pos" );
                    $(componentDiv).attr('name', ++position + "#pos" );
                    $(nextSibling).after( $(componentDiv) );
                    updateMoveIcons();
                }
            },
        dataType: 'html'
    });

    return false;
}

//updates arrow icons on load and on position change
function updateMoveIcons()
{
    // show all
    $('.textList .row a.moveUp').show();
    $('.textList .row a.moveDown').show();

    // hide up command for first component, and down command for the last
    $('.textList .row:first-child a.moveUp').hide();
    $('.textList .row:last-child a.moveDown').hide();

    // show all
    $('#collectionList .row a.moveUp').show();
    $('#collectionList .row a.moveDown').show();

    // hide up command for first component, and down command for the last
    $('#collectionList .row:first-child a.moveUp').hide();
    $('#collectionList .row:last-child a.moveDown').hide();
}

//general purpose functions
function getUrlParam( param, url )
{
    var u = url == undefined ? document.location.href : url;
    var reg = new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
    matches = u.match(reg);
    return matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
}

function changeType ()
{
    var test = $('.textList');
    var tableRow = $(this.parentNode.parentNode);
    var rawId = tableRow.attr('id');
    var resourceId = rawId.substring( 0, rawId.indexOf( '#', 0 ) );
    /*$.post( 'ajax_handler.php', { ajaxcall: 'changeType', resource_id : 'resourceId'},
            function(data){
                alert ('data'+data);
                $('.changeType', tableRow).value();
            }
        );*/
        $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=changeType&resource_id=" + resourceId ,
        success: function(response){
            $('.changeType', tableRow).text(response);
                if( response == "true" )
                {
                    $(nextSibling).attr('name', position + "#pos" );
                    $(componentDiv).attr('name', ++position + "#pos" );
                    $(nextSibling).after( $(componentDiv) );
                    updateMoveIcons();
                }
            },
        dataType: 'html'
    });

}

function addToGlossary()
{
    var lingEntity = $(this).prev().text();
    //$('.glossaryAdd').removeClass('glossaryAdd').addClass('glossaryRemove').attr('src', 'img/glossary_remove.png').attr('title', 'Supprimer de mon glossaire');
    $('.glossaryAdd').removeClass('glossaryAdd').addClass('inGlossary').attr('src', 'img/in_glossary.png').attr('title', 'Pr&eacute;sent dans mon glossaire');
    angular.element('#bottom').scope().addEntry(lingEntity);
    //angular.element('#angContainer').scope().addEntry(lingEntity);
    /*$.post( 'ajax_handler.php', { ajaxcall: 'addToGlossary', lingEntity: lingEntity },
        function(response) {
            if( response == "true" )
            {
                //alert("Entr\351e ajout\351e au glossaire !");
                $('.glossaryAdd').removeClass('glossaryAdd').addClass('glossaryRemove').attr('src', 'img/glossary_remove.png').attr('title', 'Supprimer de mon glossaire');
                //$('.emptyLine').remove();
                //$('#newGlossaryEntries').append('<tr><td style="vertical-align: top;">according to</td><td style="vertical-align: top;">prep</td><td style="vertical-align: top;">d\'après, selon</td><td style="vertical-align: top;" class="glossaryIcon inTextGlossary"><span id="currentLingEntity" class="hidden">44150</span><img height="20px" class="glossaryRemove inDisplay" src="img/glossary_remove.png" title="Supprimer de mon glossaire"></td></tr>');
                //angular.element('#ngctrl').scope().addEntry(lingEntity);
            }
        }).then(function(){
            angular.element('#bottom').scope().addEntry(lingEntity);

        })*/
}

function removeFromGlossary()
{
    //alert($(this).prev().text());
    //var currentElement = $(this);
    var lingEntity = $(this).prev().text();
    //$('.glossaryRemove').removeClass('glossaryRemove').addClass('glossaryAdd').attr('src', 'img/glossary_add.png').attr('title', 'Ajouter &agrave; mon glossaire');
    $('.inGlossary').removeClass('inGlossary').addClass('glossaryAdd').attr('src', 'img/glossary_add.png').attr('title', 'Ajouter &agrave; mon glossaire');
    angular.element('#bottom').scope().removeEntry(lingEntity);

    /*$.post( 'ajax_handler.php', { ajaxcall: 'removeFromGlossary', lingEntity: lingEntity },
        function(response) {
            if( response == "true" )
            {
                //alert("Entr\351e supprim\351e du glossaire !");
                if($(currentElement).hasClass('inDisplay'))
                {
                    $(currentElement).parent().parent().remove();
                }
                $(currentElement).removeClass('glossaryRemove').addClass('glossaryAdd').attr('src', 'img/glossary_add.png').attr('title', 'Ajouter &agrave; mon glossaire');
                if($(currentElement).parent().hasClass('inTextGlossary'))
                {
                    $('th.glossaryIcon > img').removeClass('glossaryRemove').addClass('glossaryAdd').attr('src', 'img/glossary_add.png').attr('title', 'Ajouter &agrave; mon glossaire');
                }
            }
        });*/
}

function removeFromGlobalGlossary()
{
    var lingEntity = $(this).prev().text();
    var currentElement = $(this);
    $.post( 'ajax_handler.php', { ajaxcall: 'removeFromGlossary', lingEntity: lingEntity },
        function(response) {
            if( response == "true" )
            {
                //alert("Entr\351e supprim\351e du glossaire !");
                if($(currentElement).hasClass('inDisplay'))
                {
                    $(currentElement).parent().parent().remove();
                }
                //$(currentElement).removeClass('glossaryRemove').addClass('glossaryAdd').attr('src', 'img/glossary_add.png').attr('title', 'Ajouter &agrave; mon glossaire');
                /*if($(currentElement).parent().hasClass('inTextGlossary'))
                {
                    $('th.glossaryIcon > img').removeClass('glossaryRemove').addClass('glossaryAdd').attr('src', 'img/glossary_add.png').attr('title', 'Ajouter &agrave; mon glossaire');
                }*/
            }
        });
}


