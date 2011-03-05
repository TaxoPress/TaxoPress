var demos = {};
var demoData = null;
function autocompleteUrlDemo(){
  demos.autocompleteUrlDemo = new TextboxList('autocompleteUrlDemo', {
    url: '../demo/demoData.js',
    hintMessage: 'This is a simple Auto Complete Textbox List searching/loading data from a url',
    opacity: 1
  });
}

function autocompleteLocalDemo(){
  demos.autocompleteLocalDemo = new TextboxList('autocompleteLocalDemo', {
    hintMessage: 'This is a simple Auto Complete Textbox List with it\'s data provided in an array and some values automatically selected',
    opacity: 1
  }, demoData);
}

function autocompleteAdvancedDemo(){
  demos.autocompleteAdvancedDemo = new TextboxList('autocompleteAdvancedDemo', {
    maxresults: 4,
    startsWith: true,
    secondaryRegExp: '{0}',
    selectKeys: [{
      keyCode: Event.KEY_TAB
    }],
    customTagKeys: [{
      character: '"',
      printable: true,
      isPair: true
    }, {
      character: ' ',
      printable: true
    }],
    noResultsMessage: 'There were no results found matching your search string.',
    hintMessage: 'This is an advanced Auto Complete Textbox List with it\'s data provided in an array and some values automatically selected.  It has a log of different options set.',
    opacity: 1
  }, demoData);
}

function typingListDemo(){
  demos.typingListDemo = new TextboxList('typingListDemo', {
    autoCompleteActive: false,
    customTagKeys: [{
      character: '"',
      printable: true,
      isPair: true
    }, {
      character: ' ',
      printable: true
    }],
    hintMessage: 'This list has no auto complete.  It is just a free typing list.',
    opacity: 1
  });
}

function activateDemos(){
  autocompleteUrlDemo();
  autocompleteLocalDemo();
  autocompleteAdvancedDemo();
  typingListDemo();
}

document.observe('dom:loaded', function(){
  new Ajax.Request('../demo/demoData.js', {
    method: 'get',
    onSuccess: function(transport){
      demoData = transport.responseText.evalJSON(true);
      activateDemos.defer();
      $('submitBtn').observe('click', function(){
        $H(demos).each(function(tbl){
          tbl.value.container.next('.SubmitCode').update($F(tbl.value.input)).setStyle({
            display: 'block'
          }).previous('.SubmitInfo').update('Form Value:').setStyle({
            display: 'block'
          });
        });
      });
    }
  });
});
