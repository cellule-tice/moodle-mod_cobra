/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {

    //"use strict";
    var jsonparams;
    var objparams;
    return {
        init: function(args) {
            // Gather display and corpus params
            jsonparams = args;
            objparams = JSON.parse(jsonparams);
            // Disable blocks toggle (hidden by default in Cobra context.
            $('small').hide();
            $('.hbl').hide();
            $('.sbl').hide();
            log.debug('CoBRA module init');
        },
        mod_form_triggers: function() {
            var langbutton = $('#id_updatelanguage');
            var langselect = $('#id_language');
            var corpusorder = $('#id_corpusorder');
            var collbutton = $('#id_selectcollection');
            var collselect = $('#id_collection');
            var textselect = $('#id_text');
            var textlocalname = $('#id_name');
            var defaultdisplaybutton = $('#id_updatedefaultdisplayprefs');
            var defaultdisplaycheckbox = $('#id_isdefaultdisplayprefs');
            var defaultcorpusbutton = $('#id_updatedefaultcorpusorder');
            var defaultcorpuscheckbox = $('#id_isdefaultcorpusorder');

            // Hide trigger buttons.
            if($(langbutton)) {
                $(langbutton).css('display', 'none');
            }
            if(collbutton) {
                collbutton.css('display', 'none');
            }

            langselect.on('change', function() {
                var lang = langselect.find('option:selected').text();
                if (lang == 'EN') {
                    corpusorder.val(objparams.en);
                } else if (lang == 'NL') {
                    corpusorder.val(objparams.nl);
                }
                langbutton.trigger('click');
            });

            collselect.on('change', function() {
                collbutton.trigger('click');
            });

            textselect.on('change', function() {
                if (textlocalname.val() == '') {
                    textlocalname.val($('#id_text :selected').text());
                }
            });

            defaultdisplaycheckbox.on('change', function() {
                defaultdisplaybutton.trigger('click');
            });

            defaultcorpuscheckbox.on('change', function() {
                defaultcorpusbutton.trigger('click');
            });
        },
        entry_on_click: function() {
            $('.lemma').on('click', function() {
                $('.clicked').removeClass('clicked');
                $('.emphasize').removeClass('emphasize');
                var conceptId = $(this).attr('name');
                $('.lemma[name=' + conceptId + ']').addClass('emphasize');
                $(this).removeClass('emphasize');
                $(this).addClass('clicked');
                displayDetails(conceptId, false);

            });
            $('.expression').on('click', function() {
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
                    if( $(this).attr('name') == conceptId) {
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

        },
        concordance_on_click: function() {
            $('#details').on('click', '.cc_source', function() {
                displayFullConcordance($(this));
            });
        },
        glossary_actions: function() {
            $('#details').on('click', '.glossaryAdd', function() {
                var lingEntity = $(this).prev().text();
                $('.glossaryAdd').removeClass('glossaryAdd')
                    .addClass('inGlossary')
                    .attr('src', 'pix/inglossary.png')
                    .attr('title', 'Pr&eacute;sent dans mon glossaire');
                /* eslint-disable */
                angular.element('#bottom').scope().addEntry(lingEntity);
                /* eslint-enable */
            });

            $('#glossary').on('click', '.glossaryRemove', function() {
                var lingEntity = $(this).prev().text();
                $('.inGlossary').removeClass('inGlossary')
                    .addClass('glossaryAdd')
                    .attr('src', 'pix/glossaryadd.png')
                    .attr('title', 'Ajouter &agrave; mon glossaire');
                /* eslint-disable */
                angular.element('#bottom').scope().removeEntry(lingEntity);
                /* eslint-enable */
            });
        },
    };
    function getUrlParam(param, url)
    {
        var u = url == undefined ? document.location.href : url;
        var reg = new RegExp('(\\?|&|^)' + param + '=(.*?)(&|$)');
        var matches = u.match(reg);
        return matches[2] != undefined ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
    }
    function displayDetails(conceptId, isExpression) {
        $('#full_concordance').hide();
        var detailsDiv = $('#details');
        var textId = getUrlParam('id_text', document.location.href);
        var encodeClic = $('#encode_clic').attr('name');
        var userId = $('#userId').attr('name');

        var moduleId = getUrlParam('id', document.location.href);
        moduleId = parseInt(moduleId.replace('#',''));

        $.post('relay.php', {
                verb: 'displayEntry',
                conceptid: conceptId,
                resourceid: textId,
                isexpression: isExpression,
                encodeclic : encodeClic,
                userid : userId,
                params : jsonparams,
                id: moduleId
            },
            function(data) {

                var response = JSON.parse(data);
                if (response.error) {
                    detailsDiv.html(response.error);
                } else {
                    var str = response.html.replace(/class="label"/g, 'class="cobratextlabel"')
                        .replace(/img\//g, 'pix\/');
                    //console.log(str);
                    detailsDiv.html(str);
                    if(objparams.userglossary == 1) {
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
                        $('#displayOnClic').find('tr:first')
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
    function displayFullConcordance(quickindexitem)
    {
        var fullConcordanceDiv = $('#full_concordance');
        var idConcordance = quickindexitem.attr('name');
        var backgroundColor = quickindexitem.parent().parent().css('background-color');
        var moduleId = getUrlParam('id', document.location.href);
        moduleId = parseInt(moduleId.replace('#',''));

        $.post('relay.php',
            {
                verb: 'displayCC',
                concordanceid: idConcordance,
                params : jsonparams,
                id: moduleId
            },
            function(data) {
                fullConcordanceDiv.html(data);
                fullConcordanceDiv.css('background-color', backgroundColor);
                fullConcordanceDiv.show();
            }
        );
    }

});
/* jshint ignore:end */
