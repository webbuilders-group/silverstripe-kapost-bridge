if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
    if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
}else {
    ss.i18n.addDictionary('en', {
        'KapostAdmin.REPLACE_PAGE': 'Replace this page',
        'KapostAdmin.USE_AS_PARENT': 'Use this page as the parent for the new page, leave empty for a top level page'
    });
}