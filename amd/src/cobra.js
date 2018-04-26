/* jshint ignore:start */
define(['jquery', 'core/log', 'core/ajax', 'core/templates', 'core/notification'], function($, log, ajax, templates, notification) {

    var jsonparams;
    var objparams;
    var glossaryentries;
    return {
        initui: function() {

            if ($('body').hasClass('drawer-open-left')) {
                $('body').removeClass('drawer-open-left');
                $('#nav-drawer').attr('aria-hidden', 'true');
                $('#nav-drawer').addClass('closed');
                $('button[aria-controls="nav-drawer"]').trigger('click');
            }

            log.debug('CoBRA module init');
        },

        initdata: function(args){
            jsonparams = args;
            objparams = JSON.parse(jsonparams);
        },

        mod_form_triggers: function() {
            var langbutton = $('#id_updatelanguage');
            var langselect = $('#id_language');
            var corpusorder = $('#id_corpusorder');
            var collbutton = $('#id_selectcollection');
            var collselect = $('#id_collection');
            var textselect = $('#id_text');
            var textlocalname = $('#id_name');

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
                /*$('html, body').animate({
                    scrollTop: $('#details').offset().top,
                }, 1000);*/
            });

        },
        concordance_on_click: function() {
            $('#details').on('click', '.cc_source', function() {
                displayFullConcordance($(this));
            });
        },
        text_glossary_actions: function() {
            // Load personal glossary entries.
            var promises = ajax.call([{
                methodname: 'mod_cobra_load_glossary',
                args: {
                    textid: objparams.text,
                    courseid: objparams.course,
                    userid: objparams.user
                }
            }]);
            promises[0]
                .done(function(response) {
                    glossaryentries = response;
                    updateglossarydisplay();
                }).fail(notification.exception);

            $('#details').on('click', '.glossaryadd', function() {
                var lingEntity = $(this).prev().text();

                var promises = ajax.call([{
                    methodname: 'mod_cobra_add_to_glossary',
                    args: {
                        lingentity: lingEntity,
                        textid: objparams.text,
                        courseid: objparams.course,
                        userid: objparams.user
                    }
                }]);

                promises[0]
                    .done(function(response) {
                        // Add new entry to user personal glossary and sort glossary.
                        glossaryentries.push(response);
                        glossaryentries.sort(function(a, b) {
                            if (a.entry.toLowerCase() < b.entry.toLowerCase()) {
                                return -1;
                            }
                            if (a.entry.toLowerCase() > b.entry.toLowerCase()) {
                                return 1;
                            }
                            return 0;
                        });

                        // Change icon in digest row.
                        var datafortpl = new Array;
                        datafortpl['lingentity'] = lingEntity;
                        datafortpl['iconclass'] = 'inglossary';
                        datafortpl['add'] = false;

                        templates.render('mod_cobra/glossaryiconcell', datafortpl).done(function(html) {
                            $('#displayOnClic').find('tr:first th:first').replaceWith(html);
                        }).fail(notification.exception);
                        updateglossarydisplay();
                    }).fail(notification.exception);
            });

            $('#myglossary').on('click', '.glossaryremove', function() {
                var lingEntity = $(this).prev().text();

                var promises = ajax.call([{
                    methodname: 'mod_cobra_remove_from_glossary',
                    args: {
                        lingentity: lingEntity,
                        courseid: objparams.course,
                        userid: objparams.user
                    }
                }]);

                promises[0]
                    .done(function(response) {
                        // Remove entry from displayed glossary and refresh view.
                        glossaryentries.forEach(function(result, index) {
                            if (parseInt(result.lingentity) === response.lingentity) {
                                glossaryentries.splice(index, 1);
                            }
                        });

                        // Change icon in digest row.
                        var datafortpl = new Array;
                        datafortpl['lingentity'] = lingEntity;
                        datafortpl['iconclass'] = 'glossaryadd';
                        datafortpl['add'] = true;

                        templates.render('mod_cobra/glossaryiconcell', datafortpl).done(function(html) {
                            $('#displayOnClic').find('tr:first th:first').replaceWith(html);
                        }).fail(notification.exception);

                        updateglossarydisplay();
                        /*var datafortpl = new Array;
                        datafortpl['entries'] = glossaryentries;
                        datafortpl['cmid'] = objparams.cmid;
                        templates.render('mod_cobra/intextglossary', datafortpl).done(function(html) {
                            $('#glossary').replaceWith(html);
                        }).fail(notification.exception);*/
                    }).fail(notification.exception);
            });

        },
        global_glossary_actions : function() {
            $('#myglossary').on('click', '.glossaryremove', function () {
                var lingEntity = $(this).find('span:first').text();
                var currentElement = $(this);
                var promises = ajax.call([{
                    methodname: 'mod_cobra_remove_from_glossary',
                    args: {
                        lingentity: lingEntity,
                        courseid: objparams.course,
                        userid: objparams.user
                    }
                }]);
                promises[0]
                    .done(function(response) {
                        // Remove entry from displayed glossary and refresh view.
                        if (response.lingentity == lingEntity) {
                            if ($(currentElement).hasClass('inDisplay')) {
                                $(currentElement.parent().remove());
                            }
                        }
                    }).fail(notification.exception);
            });
        }
    };

    function displayDetails(conceptId, isExpression) {
        $('#full_concordance').hide();

        var promises = ajax.call([{
            methodname: 'mod_cobra_get_entry',
            args: {
                concept_id: conceptId,
                is_expr: isExpression,
                params: jsonparams
            }
        }]);

        promises[0]
            .done(function(response) {
                if(objparams.examples == 'bilingual') {
                    response.bilingual = true;
                }
                templates.render('mod_cobra/entrydetails', response).done(function(html) {
                    $('#details').html(html);

                }).fail(notification.exception);
            }).fail(notification.exception);
    }

    // Display full text of clicked concordance.
    function displayFullConcordance(quickindexitem)
    {
        var fullConcordanceDiv = $('#full_concordance');
        var idConcordance = quickindexitem.attr('name');

        var promises = ajax.call([{
            methodname: 'mod_cobra_get_full_concordance',
            args: {
                id_concordance: idConcordance
            }
        }]);

        promises[0]
            .done(function(response) {

                if(objparams.examples == 'bilingual') {
                    response.bilingual = true;
                }
                templates.render('mod_cobra/fullconcordance', response).done(function (html) {
                    fullConcordanceDiv.html(html);
                    fullConcordanceDiv.removeClass();
                    fullConcordanceDiv.addClass(response.type);
                    fullConcordanceDiv.show();
                }).fail(notification.exception);
            }).fail(notification.exception);
    }

    function updateglossarydisplay() {
        var datafortpl = new Array;
        datafortpl['entries'] = glossaryentries;
        datafortpl['cmid'] = objparams.cmid;
        datafortpl['course'] = objparams.course;
        templates.render('mod_cobra/intextglossary', datafortpl).done(function (html) {
            $('#glossary').replaceWith(html);
            // Adapt glossary height to text height.
            $('#glossary').css('height', $('#cobratext').css('height'));

        }).fail(notification.exception);
    }
});
/* jshint ignore:end */
