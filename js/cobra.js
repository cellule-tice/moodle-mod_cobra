M.mod_cobra = M.mod_cobra || {};

/**
 * Init ajax based Chat UI.
 * @namespace M.mod_chat_ajax
 * @function
 * @param {YUI} Y
 * @param {Object} cfg configuration data
 */
M.mod_cobra.init = function() {   

init();
    function init()
    {    
        //hide left menu in claroline 1.10
        $('#courseLeftSidebar').hide();
        $('#courseRightContent').css('border','none');
        $('#courseRightContent').css('padding-left','0px');
        if ( $('#toggleLeftMenu') ) $('#toggleLeftMenu').trigger('click');
        $('small').hide();
        
        //$(document).on('click','.cc_source',displayFullCC);
       /* $(document).on('click','.occ_source',displayFullOcc);
        $(document).on('click','#showCard',displayCard);
        $(document).on('click','a.moveUp', moveUp);
        $(document).on('click','a.moveDown', moveDown); */
        
        $(document).on('click','a.changeType', changeType);
        

        updateMoveIcons();
    }    
}
M.mod_cobra.TextChangeType = function(){
    $(document).on('click','a.changeType', changeType);
}

M.mod_cobra.TextMove = function(){
     $(document).on('click','a.moveUp', moveUp);
     $(document).on('click','a.moveDown', moveDown);
     updateMoveIcons();
};

M.mod_cobra.TextVisibility = function(){
     $(document).on('click','a.setVisible', setVisible);
     $(document).on('click','a.setInvisible', setInvisible);
};


M.mod_cobra.SelectAll = function(){
  
   $('#selectall').on('click',function(){
       if(this.checked){
            $('.checkbox').each(function(){
                this.checked = true;
            });
        }else{
             $('.checkbox').each(function(){
                this.checked = false;
            });
        }
   });
   $('.checkbox').on('click',function(){
        if($('.checkbox:checked').length == $('.checkbox').length){
            $('#select_all').prop('checked',true);
        }else{
            $('#select_all').prop('checked',false);
        }
    });
}

M.mod_cobra.showCard = function(){
    $(document).on('click','#showCard',displayCard);
};

M.mod_cobra.showFullConcordance = function(){
    $(document).on('click','.cc_source',displayFullCC);    
};

M.mod_cobra.lemma_on_click = function(){
  
    $('.lemma').on('click', function(){
       $('.clicked').removeClass('clicked');
        $('.emphasize').removeClass('emphasize'); 
        var conceptId = $(this).attr("name");     
        $('.lemma[name=' + conceptId + ']').addClass('emphasize');
        $(this).removeClass('emphasize');
        $(this).addClass('clicked');
        $('#card').hide();
        $('#full_concordance').hide();
        var detailsDiv = $('#details');      
        var textId = getUrlParam('id_text', document.location.href);
        var encodeClic = $('#encode_clic').attr('name');
        var courseId = $('#courseLabel').attr('name');
        var userId = $('#userId').attr('name');
        var sizePref = $("#preferencesNb").attr('name');
        var nb = parseInt(sizePref);
        var pref = new Array(); 
        for (var i=0; i< nb; i++)
        {
            var key = $("#preferences_"+i+"_key").attr('name');           
            var value = $("#preferences_"+i+"_value").attr('name');
            pref[key] = value;
        }

        var json = JSON.stringify(pref);      
        var isExpr =  0;
      //  var t = this;
      $.post( 'relay.php', { verb: 'displayEntry', concept_id: conceptId, resource_id: textId, is_expr: isExpr , encodeClic : encodeClic, courseId : courseId, userId : userId, 
            params : json  },
            function(data){
                detailsDiv.html(data);
        });                 
   });   
};

M.mod_cobra.expression_on_click = function(){
  
    $('.expression').on('click', function(){
      $('.clicked').removeClass('clicked');
        $('.emphasize').removeClass('emphasize');
        var conceptId = $(this).attr('name');
        
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

        $('#card').hide();
        $('#full_concordance').hide();
        var detailsDiv = $('#details');
        var textId = getUrlParam('id_text', document.location.href);
        var isExpr =  1 ;        
        var encodeClic = $('#encode_clic').attr('name');
        var courseId = $('#courseLabel').attr('name');
        var userId = $('#userId').attr('name');
        var sizePref = $("#preferencesNb").attr('name');      
        var nb = parseInt(sizePref);
        var pref = new Array(); 
        for (var i=0; i< nb; i++)
        {
            var key = $("#preferences_"+i+"_key").attr('name');           
            var value = $("#preferences_"+i+"_value").attr('name');
            pref[key] = value;
        }  
        var json = JSON.stringify(pref);
      //  var t = this;
        $.post( 'relay.php', { verb: 'displayEntry', concept_id: conceptId, resource_id: textId, is_expr: isExpr, encodeClic : encodeClic, courseId : courseId, userId : userId, params : json  },
            function(data){
                detailsDiv.html(data);
        });
   });   
};


//display full text of clicked concordance
function displayFullCC()
{
    var fullCCDiv = $('#full_concordance');
    var id_cc = $(this).attr('name');
    var bg_color = $(this).parent().parent().css("background-color");
    var sizePref = $("#preferencesNb").attr('name');
    var nb = parseInt(sizePref);
    var pref = new Array(); 
    for (var i=0; i< nb; i++)
    {
        var key = $("#preferences_"+i+"_key").attr('name');           
        var value = $("#preferences_"+i+"_value").attr('name');
        pref[key] = value;
    }

    var json = JSON.stringify(pref);   
    $.post( 'relay.php', { verb: 'displayCC', id_cc: id_cc, params : json },
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
     var sizePref = $("#preferencesNb").attr('name');
    var nb = parseInt(sizePref);
    var pref = new Array(); 
    for (var i=0; i< nb; i++)
    {
        var key = $("#preferences_"+i+"_key").attr('name');           
        var value = $("#preferences_"+i+"_value").attr('name');
        pref[key] = value;
    }

    var json = JSON.stringify(pref);   
    $.post( 'relay.php', { verb: 'displayCard', entry_id: entryId, currentConstruction: currentConstruction, is_expr: isExpr, params : json },
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
    var Id = getUrlParam('id', document.location.href);
    Id = parseInt(Id.replace('#',''));
    $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=setVisible&resource_id=" + resourceId + '&resource_type=' + resourceType + '&courseId=' + Id,
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
    var Id = getUrlParam('id', document.location.href);
    Id = parseInt(Id.replace('#',''));
    
    $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=setInvisible&resource_id=" + resourceId + '&resource_type=' + resourceType + '&courseId=' +Id,
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
      var courseId = getUrlParam('id', document.location.href);
    courseId = parseInt(courseId.replace('#',''));
    $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=moveUp&resource_id=" + id + "&position=" + position + "&sibling_id=" + siblingId + '&resource_type=' + resourceType + '&courseId=' +courseId,
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
    var courseId = getUrlParam('id', document.location.href);
    courseId = parseInt(courseId.replace('#',''));
    $.ajax({
        url: "ajax_handler.php",
        data: "ajaxcall=moveDown&resource_id=" + id + "&position=" + position + "&sibling_id=" + siblingId + '&resource_type=' + resourceType + '&courseId=' +courseId,
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
    var courseId = getUrlParam('id', document.location.href);
    courseId = parseInt(courseId.replace('#',''));  
        $.ajax({            
        url: "ajax_handler.php",
        data: "ajaxcall=changeType&resource_id=" + resourceId + '&courseId=' +courseId,
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
   
         // Convert array to object
    var convArrToObj = function(array){
        var thisEleObj = new Object();
        if(typeof array == "object"){
            for(var i in array){
                var thisEle = convArrToObj(array[i]);
                thisEleObj[i] = thisEle;
            }
        }else {
            thisEleObj = array;
        }
        return thisEleObj;
    };
    var oldJSONStringify = JSON.stringify;
    JSON.stringify = function(input){
        if(oldJSONStringify(input) == '[]')
            return oldJSONStringify(convArrToObj(input));
        else
            return oldJSONStringify(input);
    };