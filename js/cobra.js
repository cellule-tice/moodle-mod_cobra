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
    function init() {
        $('small').hide();
        $(document).on('click', 'a.changeType', changeType);
        updateMoveIcons();
    }
}

M.mod_cobra.init_no_blocks = function() {
    initNoBlocks();
    function initNoBlocks() {
        $('small').hide();
        $('.hbl').hide();
        $('.sbl').hide();
    }
}

M.mod_cobra.text_change_type = function() {
    $(document).on('click', 'a.changeType', changeType);
}

M.mod_cobra.text_move = function() {
     $(document).on('click', 'a.moveUp', moveUp);
     $(document).on('click', 'a.moveDown', moveDown);
     updateMoveIcons();
};

M.mod_cobra.text_visibility = function() {
     $(document).on('click', 'a.setVisible', setVisible);
     $(document).on('click', 'a.setInvisible', setInvisible);
};

M.mod_cobra.select_all = function() {
    $('#selectall').on('click', function() {
        if (this.checked) {
            $('.checkbox').each(function() {
                this.checked = true;
            });
        } else {
             $('.checkbox').each(function() {
                this.checked = false;
             });
        }
    });
    $('.checkbox').on('click', function() {
        if ($('.checkbox:checked').length == $('.checkbox').length) {
            $('#select_all').prop('checked', true);
        } else {
            $('#select_all').prop('checked', false);
        }
    });
}

M.mod_cobra.show_full_concordance = function() {
    $(document).on('click', '.cc_source', displayFullConcordance);
};

M.mod_cobra.lemma_on_click = function() {
    $(document).on('click', '.lemma', function() {
        $('.clicked').removeClass('clicked');
        $('.emphasize').removeClass('emphasize');
        var conceptId = $(this).attr('name');
        $('.lemma[name=' + conceptId + ']').addClass('emphasize');
        $(this).removeClass('emphasize');
        $(this).addClass('clicked');
        displayDetails(conceptId, false);
    });
};

M.mod_cobra.expression_on_click = function() {
    $(document).on('click', '.expression', function() {
        $('.clicked').removeClass('clicked');
        $('.emphasize').removeClass('emphasize');
        var conceptId = $(this).attr('name');

        $('.expression[name=' + conceptId + ']').addClass('emphasize');
        $(this).prevAll('span.expression').each(function() {
            if ($(this).attr('name') == conceptId) {
                $(this).removeClass('emphasize');
                $(this).addClass('clicked');
            } else {
                return false;
            }
        });
        $(this).nextAll('span.expression').each(function() {
            if( $(this).attr('name') == conceptid) {
                $(this).removeClass('emphasize');
                $(this).addClass('clicked');
            } else {
                return false;
            }
        });
        $(this).removeClass('emphasize');
        $(this).addClass('clicked');
        displayDetails(conceptId, true);
    });
};

M.mod_cobra.add_to_glossary = function() {
    $(document).on('click', '.glossaryAdd', function() {
        var lingEntity = $(this).prev().text();
        $('.glossaryAdd').removeClass('glossaryAdd')
                .addClass('inGlossary')
                .attr('src', 'pix/inglossary.png')
                .attr('title', 'Pr&eacute;sent dans mon glossaire');
        angular.element('#bottom').scope().addEntry(lingEntity);
    });
}

M.mod_cobra.remove_from_glossary = function() {
    $(document).on('click', '.glossaryRemove', function() {
        var lingEntity = $(this).prev().text();
        $('.inGlossary').removeClass('inGlossary')
                .addClass('glossaryAdd')
                .attr('src', 'pix/glossaryadd.png')
                .attr('title', 'Ajouter &agrave; mon glossaire');
        angular.element('#bottom').scope().removeEntry(lingEntity);
    });
}

M.mod_cobra.remove_from_global_glossary = function() {
    $(document).on('click', '.gGlossaryRemove', function() {
        var courseId = $('#courseid').attr('name');
        var lingEntity = $(this).prev().text();
        var currentElement = $(this);
        $.post('ajax_handler.php',
            {
                ajaxcall: 'removeFromGlossary',
                lingentity: lingEntity,
                courseid: courseId
            },
            function(response) {
                if (response == "true")
                {
                    if ($(currentElement).hasClass('inDisplay'))
                    {
                        $(currentElement).parent().parent().remove();
                    }
                }
            }
        );
    });
}

function displayDetails(conceptId, isExpression) {
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
    for (var i = 0; i < nb; i++) {
        var key = $("#preferences_" + i + "_key").attr('name');
        var value = $("#preferences_" + i + "_value").attr('name');
        pref[key] = value;
    }

    var json = JSON.stringify(pref);
    $.post('relay.php',
        {
            verb: 'displayEntry',
            conceptid: conceptId,
            resourceid: textId,
            isexpression: isExpression,
            encodeclic : encodeClic,
            courseid : courseId,
            userid : userId,
            params : json
        },
        function(data) {
            var response = JSON.parse(data);
            if (response.error) {
                detailsDiv.html(response.error);
            } else {
                var str = response.html.replace(/class="label"/g, 'class="cobralabel"')
                    .replace(/img\//g, 'pix\/');
                detailsDiv.html(str);
                if ('SHOW' == $('#showglossary').text()) {
                    var lingEntitySpan = '<span id="currentlingentity" class="hidden">' +
                                         response.lingentity +
                                         '</span>';
                    var glossaryIcon = '';
                    var angularClick = '';
                    if ('1' == response.inglossary) {
                        glossaryIcon = '<img height="20px" ' +
                                       'class="inGlossary" ' +
                                       'src="pix/inglossary.png" ' +
                                       'title="Pr&eacute;sent dans mon glossaire"/>';
                        angularClick = 'ng-click="addEntry(' + response.lingentity + ')"';
                    } else {
                        glossaryIcon = '<img height="20px" ' +
                                       'class="glossaryAdd" ' +
                                       'src="pix/glossaryadd.png" ' +
                                       'title="Ajouter &agrave; mon glossaire"/>';
                    }
                    var tr = $('#displayOnClic').find('tr:first')
                        .prepend('<th ' + angularClick + ' class="glossaryIcon">' + lingEntitySpan + glossaryIcon + '</th>')
                        .addClass('digestRow');
                } else {
                    $('#glossary').remove();
                }
            }
        }
    );
}

// Display full text of clicked concordance.
function displayFullConcordance()
{
    var fullConcordanceDiv = $('#full_concordance');
    var idConcordance = $(this).attr('name');
    var backgroundColor = $(this).parent().parent().css('background-color');
    var sizePref = $("#preferencesNb").attr('name');
    var nb = parseInt(sizePref);
    var pref = new Array();
    for (var i = 0; i < nb; i++) {
        var key = $("#preferences_" + i + "_key").attr('name');
        var value = $("#preferences_" + i + "_value").attr('name');
        pref[key] = value;
    }

    var json = JSON.stringify(pref);
    $.post('relay.php',
        {
            verb: 'displayCC',
            concordanceid: idConcordance,
            params : json
        },
        function(data) {
            fullConcordanceDiv.html(data);
            fullConcordanceDiv.css('background-color', backgroundColor);
            fullConcordanceDiv.show();
        }
    );
}

// Display full text of clicked occurrence.
function displayFullOcc()
{
    var fullConcordanceDiv = $('#full_concordance');
    var idOccurrence = $(this).attr('name');
    var backgroundColor = $(this).parent().parent().css('background-color');
    var sizePref = $("#preferencesNb").attr('name');
    var nb = parseInt(sizePref);
    var pref = new Array();
    for (var i = 0; i < nb; i++) {
        var key = $("#preferences_" + i + "_key").attr('name');
        var value = $("#preferences_" + i + "_value").attr('name');
        pref[key] = value;
    }

    var json = JSON.stringify(pref);
    $.post('relay.php',
        {
            verb: 'displayCC',
            id_occ: idOccurrence,
            params : json
        },
        function(data) {
            fullConcordanceDiv.html(data);
            fullConcordanceDiv.css('background-color', backgroundColor);
            fullConcordanceDiv.show();
        }
    );
}

/*
 * Interaction functions for text list.
 */
// Make current text visible for students.
function setVisible()
{
    var test = $('#textlist');
    var resourceType = test.size() != 0 ? 'text' : 'collection';
    var tableRow = $(this.parentNode.parentNode);
    var rawId = tableRow.attr('id');
    var resourceId = rawId.substring(0, rawId.indexOf('#', 0));
    var courseId = getUrlParam('id', document.location.href);
    courseId = parseInt(id.replace('#',''));

    $.post('ajax_handler.php',
        {
            ajaxcall: 'setVisible',
            resource_id: resourceId,
            resource_type: resourceType,
            courseId: courseId
        },
        function(response) {
            if (response == 'true') {
                $('.setVisible', tableRow).hide();
                $('.setInvisible', tableRow).show();
                $(tableRow).removeClass('dimmed_text');
            } else {
                alert(response);
            }
        }
    );

    return false;
}

// Make current text invisible for students.
function setInvisible() {
    var test = $('#textlist');
    var resourceType = test.size() != 0 ? 'text' : 'collection';
    var tableRow = $(this.parentNode.parentNode);
    var rawId = tableRow.attr('id');
    var resourceId = rawId.substring( 0, rawId.indexOf('#', 0));
    var courseId = getUrlParam('id', document.location.href);
    courseId = parseInt(id.replace('#',''));

    $.post('ajax_handler.php',
        {
            ajaxcall: 'setInvisible',
            resource_id: resourceId,
            resource_type: resourceType,
            courseId: courseId
        },
        function(response) {
            if (response == 'true') {
                $('.setVisible', tableRow).show();
                $('.setInvisible', tableRow).hide();
                $(tableRow).addClass('dimmed_text');
            } else {
                alert(response);
            }
        }
    );
    return false;
}

// Move up current text by one row.
function moveUp()
{
    // Retrieve parent <tr> tag.
    var test = $('#textlist');
    var resourceType = test.size() != 0 ? 'text' : 'collection';
    var componentDiv = $(this.parentNode.parentNode);
    var previousSibling = $(componentDiv).prev();
    var rawId = $(componentDiv).attr('id');
    var rawPos = $(componentDiv).attr('name');
    var rawSiblingId = $(previousSibling).attr('id');
    var resourceId = rawId.substring(0, rawId.indexOf('#', 0));
    var position = rawPos.substring(0, rawPos.indexOf('#', 0));
    var siblingId = rawSiblingId.substring(0, rawSiblingId.indexOf('#', 0));
    var courseId = getUrlParam('id', document.location.href);
    courseId = parseInt(courseId.replace('#',''));

    $.post('ajax_handler.php',
        {
            ajaxcall: 'moveUp',
            resource_id: resourceId,
            position: position,
            sibling_id: siblingId,
            resource_type: resourceType,
            courseId: courseId
        },
        function(response) {
            if (response == 'true') {
                $(previousSibling).attr('name', position + '#pos');
                $(componentDiv).attr('name', --position + '#pos');
                $(previousSibling).before($(componentDiv));
                updateMoveIcons();
            } else {
                alert(response);
            }
        }
    );

    return false;
}

// Move down current text by one row.
function moveDown() {
    // Retrieve parent <tr> tag.
    var test = $('#textlist');
    var resourceType = test.size() != 0 ? 'text' : 'collection';
    var componentDiv = $(this.parentNode.parentNode);
    var nextSibling = $(componentDiv).next();
    var rawId = $(componentDiv).attr('id');
    var rawPos = $(componentDiv).attr('name');
    var rawSiblingId = $(nextSibling).attr('id');
    var resourceId = rawId.substring(0, rawId.indexOf('#', 0));
    var position = rawPos.substring(0, rawPos.indexOf('#', 0));
    var siblingId = rawSiblingId.substring(0, rawSiblingId.indexOf('#', 0));
    var courseId = getUrlParam('id', document.location.href);
    courseId = parseInt(courseId.replace('#',''));

    $.post('ajax_handler.php',
        {
            ajaxcall: 'moveDown',
            resource_id: resourceId,
            position: position,
            sibling_id: siblingId,
            resource_type: resourceType,
            courseId: courseId
        },
        function(response) {
            if (response == 'true') {
                $(nextSibling).attr('name', position + "#pos" );
                $(componentDiv).attr('name', ++position + "#pos" );
                $(nextSibling).after( $(componentDiv) );
                updateMoveIcons();
            } else {
                alert(response);
            }
        }
    );
    return false;
}

// Updates arrow icons on load and on position change.
function updateMoveIcons()
{
    // Show all.
    $('#textlist .row a.moveUp').show();
    $('#textList .row a.moveDown').show();

    // Hide up command for first component, and down command for the last.
    $('#textlist .row:first-child a.moveUp').hide();
    $('#textlist .row:last-child a.moveDown').hide();

    // Show all.
    $('#collectionList .row a.moveUp').show();
    $('#collectionList .row a.moveDown').show();

    // Hide up command for first component, and down command for the last.
    $('#collectionList .row:first-child a.moveUp').hide();
    $('#collectionList .row:last-child a.moveDown').hide();
}

// General purpose functions.
function getUrlParam(param, url)
{
    var u = url == undefined ? document.location.href : url;
    var reg = new RegExp('(\\?|&|^)' + param + '=(.*?)(&|$)');
    matches = u.match(reg);
    return matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
}

function changeType()
{
    var tableRow = $(this.parentNode.parentNode);
    var rawId = tableRow.attr('id');
    var resourceId = rawId.substring(0, rawId.indexOf('#', 0));
    var courseId = getUrlParam('id', document.location.href);
    courseId = parseInt(courseId.replace('#',''));

    $.post('ajax_handler.php',
        {
            ajaxcall: 'changeType',
            resource_id: resourceId,
            courseId: courseId
        },
        function(response) {
            $('.changeType', tableRow).text(response);
        }
    );
}

// Convert array to object.
var convArrToObj = function(array){
    var thisEleObj = new Object();
    if (typeof array == "object") {
        for(var i in array){
            var thisEle = convArrToObj(array[i]);
            thisEleObj[i] = thisEle;
        }
    } else {
        thisEleObj = array;
    }
    return thisEleObj;
};
var oldJSONStringify = JSON.stringify;
JSON.stringify = function(input){
    if(oldJSONStringify(input) == '[]') {
        return oldJSONStringify(convArrToObj(input));
    } else {
        return oldJSONStringify(input);
    }
};