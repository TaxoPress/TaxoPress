[TextboxList][2]: A [Prototype.js][1] based Text Box List
========================================

Create a new instance:
----------------------

>     new TextboxList(Input, Options, [Data]);

>  - **Input** - the Text Box to convert into a TextboxList (can be the id or the DOM Element)
>  - **Options** - An object containing all options for the TextboxList
>  - **Data**[*optional*] - An Array of data to be used for the auto completer.

Auto completer data format:
---------------------------

>     [{
>       "caption": "Caption 1",
>       "value": "Value 1"
>     },
>     {
>       "caption": "Caption 2",
>       "value": "Value 2"
>     }]

>  - **caption** is the value displayed in both the auto completer and what is displayed in the bubble.
>  - **value** is a hidden value that will be linked with the **caption**.  Think name/id pairs
>  - You may also include any other additional properties and they will be posted as well.

Posted Values:
--------------

> Values for will be loaded from/posted to the server in the form of serialized JSON in the input field's value property

Options:
--------

> Data Type and Default value are shown in parenthesis. 

  - **autoCompleteActive** (Boolean = *true*) - Whether to use the auto complete functionality
    - When set to false there will be no lookup so you can use the control to simply display or manually enter values
  - **url** (String = *null*) - The url to use for auto complete. 
    - An AJAX request will be sent to this address whenever the user types more then **minchars**
  - **opacity** (Number = *0.8*) - The Opacity to use for the auto complete/message drop down 
  - **maxresults** (Integer = *Infinity*) - The Maximum number of items to display in the auto complete drop down.
    - By default the auto complete does not use a scroll bar and instead uses this property to decide how many items to display
    - If you want a scroll bar you can adjust your css styles.
  - **minchars** (Integer = *1*) - The minimum number of character for activating the auto complete dropdown 
    - Very useful for limiting the number of requests to your server when using the AJAX auto complete.
  - **noResultsMessage** (String = *"No values found"*) - Message to display when the search string is not found in the auto complete data. 
  - **hintMessage** (String = *null*) - Message to display the text box list receives focus.
    - Can be used to give the user some additional info.
  - **requestDelay** (Number = *0.3*) - The delay (in seconds) after last keypress to wait before sending an AJAX request.
    - Very useful for limiting the number of requests to your server when using the AJAX auto complete.
  - **parent** (String/Element = *document.body*) - The id or DOM Element that the auto complete/message div will be added to.
    - Depending on your page layout and/or css you may need to change this.
  - **startsWith** (Boolean = *false*) - Limit the auto complete matches to only items that start with the search value
  - **regExp** (String = *"^{0}" or "{0}" depending on **startsWith***) - The regular expression to use for matching/highlighting auto complete values.
    - Can be changed to any valid regular expression string
  - **secondaryRegExp** (String = *null*) - A Secondary regular expression to use for matching/highlighting auto complete values.
    - Can be changed to any valid regular expression string
    - Useful in combination with **regExp** if you want to match on two different items.  For example you want to search the same data set twice.  First for items that start with, and then for items that just contain.
    - The results of **secondaryRegExp** are appended to the results of **regExp**
  - **selectKeys** (Array = *[{ keyCode: Event.KEY_RETURN }, { keyCode: Event.KEY_TAB }]*) - An Array of keys to be used to select an item from the auto complete dropdown.
    - If the key is non-printable you simple add an Object to the Array with one property "keyCode" with it's value set to the ascii keyCode.
    - *Still needs to be implemented* - If the key is printable then you would add an Object to the Array with two properties "character" set to the actual character to match AND "printable" set to true.
  - **customTagKeys** (Array = *[}]*) - An Array of keys to be used to add the current text as a value (not from auto complete).
    - *Still needs to be tested* - If the key is non-printable you simple add an Object to the Array with one property "keyCode" with it's value set to the ascii keyCode.
    - If the key is printable then you would add an Object to the Array with two properties "character" set to the actual character to match AND "printable" set to true.
      - Also supports requiring two instances of "character" for a match.  One at the beginning of the string and one at the end. Example: customTagKeys = [{ character: '"', printable: true, isPair: true }, { character: ' ', printable: true }]
        - In this example if the user is typing and hits "space" then it would add the current text, unless the text started with " then it would wait for another " to be entered to add the item.
  - **disabledColor** (String = *"silver"*) - The color of a div that will be placed over top of the control when it is disabled.
  - **disabledOpacity** (Number= *0.3*) - The opacity of a div that will be placed over top of the control when it is disabled.
  - **className** (String= *"bit"*) - A string to pre-pend to the css class name for each selected item
  - **uniqueValues** (Boolean= *true*) - Force selected items to be unique.
    - Any previously selected items will be excluded from the auto complete list.
  - **callbacks** (Object= *see **Callback Functions** section*) - See below.

Callback Functions:
-------------------

> All callbacks default to an empty function.  When specifying callback you only need to add the ones you need as properties of a **callbacks** options specified in the options of the Textbox List constructor.


> **onMainFocus**(event) - Occurs after the Textbox List's focus event has completed.

>  - Receives the focus Event as it's only argument

> **onMainBlur**(event) - Occurs after the Textbox List's blur event has completed.

>  - Receives the focus Event as it's only argument

> **onBeforeAddItem**(selectedValues, value, element)- Occurs prior to an item being added to the Textbox List.  Return *true* from your function to stop the item from being added.

>  - **selectedValues** (Array) - An Array of the currently selected items in the Textbox List.
>  - **value** (Object) - An Object of the item to be added to the Textbox List.
>  - **element** (Element) - The HTML Element to be added to the Textbox List.

> **onAfterAddItem**(selectedValues, value, element) - Occurs after the an item was added to the Textbox List.

>  - **selectedValues** (Array) - An Array of the currently selected items in the Textbox List.
>  - **value** (Object) - An Object of the item that was added to the Textbox List.
>  - **element** (Element) - The HTML Element that was added to the Textbox List.

> **onBeforeUpdateValues** - Occurs before the currently selected values are written to the source input box

>  - **selectedValues** (Array) - An Array of the currently selected items in the Textbox List.
>  - **element** (Element) - The source HTML Input Element for the Textbox List.

> **onAfterUpdateValues**(selectedValues, value, element) - Occurs after the currently selected values have been written to the source input box

>  - **selectedValues** (Array) - An Array of the currently selected items in the Textbox List.
>  - **element** (Element) - The source HTML Input Element for the Textbox List.

> **onControlLoaded**() - Occurs only once when the Textbox List control has first been created and is ready for use.


Useful function calls:
----------------------

>     var instance = new TextboxList(Input, Options, [Data]);

>  - *instance.disable();*
>    - Will disable the TextBox List
>    - **Note** - The Textbox List will automatically disable itself based on the *disabled* property of the underlying text input

>  - *instance.enable();*
>    - Will enable the TextBox List

>  - *instance.isDisabled([disable]);*
>    - Returns true if the Textbox List is currently disabled.
>    - *optional* **disable** (Boolean) - pass a boolean value to disable/enable the Textbox List as part of the call.

>  - *instance.addItem(value);*
>    - Adds a new item to the Textbox List
>    - Returns *true* if item was added successfully, *null* if it wasn't
>    - **value** (Object) - Pass an object containing any number of properties to add it to the Textbox List.  A property named **caption** is required.

>  - *instance.removeElement(element);*
>    - Removes an item from the list
>    - **element** (Element) - Pass the HTML Element of the item to be removed.

>  - *instance.removeItem(value, [replaceAll]);*
>    - Removes an item(s) from the list
>    - **value** (Object) - Pass an Object containing the to search the selected items by.  A property named **caption** and/or **value** is required or no item(s) will be removed,
>    - *optional* **replaceAll** (Boolean) - whether to remove all items matching the value, or just the first one.

>  - ***TextboxList.autoCompleteItemHTML***
>> TextboxList.addMethods({
>>> autoCompleteItemHTML: function(value, highlight, [secondaryHighlight]) {
>>>> // return the HTML/DOM Element for an auto complete item

>>>> }

>>> });

>>   - Override the TextboxList.autoCompleteItemHTML function if you wish to change the HTML of the auto complete items.
>>   - This function must return a value compatible with Element.update
>>   - **value** (Object) - The value for the current auto complete item
>>   - **highlight** (RegExp) - The regular expression object that was used for the search.
>>   - *optional* **secondaryHighlight** (RegExp) - The secondary regular expression object that was used for the search.

  [1]: http://www.prototypejs.org/
  [2]: index.html

