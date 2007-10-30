RegExp.escape = function(text)
{
    if (!arguments.callee.sRE)
    {
        var specials = [
            '/', '.', '*', '+', '?', '|',
            '(', ')', '[', ']', '{', '}', '\\'
        ];

        arguments.callee.sRE = new RegExp(
            '(\\' + specials.join('|\\') + ')', 'g'
        );
    }

    return text.replace(arguments.callee.sRE,'\\$1');
};

var BComplete = Class.create();
BComplete.prototype =
{
    MAX_VISIBLE : 8,
    TIMER_TICK : 10,
    CANCEL_SUBMISSION_TIMEOUT : 10,

    initialize : function(element,max)
    {
        if(max)
        {
            this.MAX_VISIBLE = max;
        }

        this.data = new Array();
        this.element = $(element);
        if(!this.element)
        {
            throw("BComplete: The specified <input> element does not exist.");
        }

        this.element.setAttribute("autocomplete","off");
        Element.addClassName(this.element,"bcomplete-field");

        this.visible = false;
        this.cancelSubmit = false;
        this.scroll = 0;
        this.selectedIndex = -1;
        this.matches = new Array();

        this.popup = document.createElement("div");
        Element.hide(this.popup);
        this.popup.className = "bcomplete-popup";
        document.body.appendChild(this.popup);

        this.upButton = document.createElement("div");
        this.upButton.className = "up-button";
        this.popup.appendChild(this.upButton);

        this.listItems = new Array();
        for(var i=0;i<this.MAX_VISIBLE;i++)
        {
            var item = document.createElement("div");
            this.listItems[i] = item ;
            item.className = "item";
            this.popup.appendChild(item);
            item.autocomplete = this;
            item.number = i;

            item.onclick = this.onItemClick;
            item.onmouseover = this.onItemOn;
            item.onmouseout = this.onItemOff;
        }

        this.downButton = document.createElement("div");
        this.downButton.className = "down-button";
        this.popup.appendChild(this.downButton);

        Event.observe(this.element,"keypress",
            this.onKeyPress.bindAsEventListener(this));
        Event.observe(this.element,"keydown",
            this.onKeyDown.bindAsEventListener(this));
        Event.observe(this.upButton,"click",
            this.onUpButton.bindAsEventListener(this));
        Event.observe(this.downButton,"click",
            this.onDownButton.bindAsEventListener(this));
        Event.observe(document,"click",
            this.onWindowClick.bindAsEventListener(this));

        this.onTick = this.onTick.bind(this);
        this.onSubmit = this.onSubmit.bind(this);

        // look for parent <form>
        var parentForm = this.element.parentNode;
        while(parentForm)
        {
            if(parentForm.tagName.toLowerCase() == "form")
                break;

            parentForm = parentForm.parentNode;
        }

        // capture submit event for parent <form>
        var me = this;
        if(parentForm)
        {
            var oldHandler = parentForm.onsubmit;
            parentForm.onsubmit = function()
            {
                if(oldHandler)
                    return (oldHandler() && me.onSubmit());
                else
                    return me.onSubmit();
            };
        }
    },

    addItem : function(item)
    {
        this.data[this.data.length] = item;
        this.data.sort();
    },

    setData : function(data)
    {
        this.data = data;
        data.sort();
    },

    loadData : function(url)
    {
        var me = this;
        var success = function(request)
        {
            try
            {
                var data = eval(request.responseText);
                if(typeof data == "object")
                {
                    me.setData(data);
                }
            }
            catch(exception)
            {
                throw("BComplete: Invalid data format.");
            }
        };

        var request = new Ajax.Request(url,
            { method: 'get', onSuccess: success });
    },

    findMatches : function(text)
    {
        var matches = new Array();

        // Modifications for multiples values
        var exp = new RegExp(",?[^,]*$" , "i");
        m = text.match(exp);
        text = m[0].replace(/^[,\s]+|\s+$/g,"");

        // Strict search (old) (must start with...)
        // var expression = new RegExp("^"+ RegExp.escape(text),"i");
        // Cool search
        var expression = new RegExp(RegExp.escape(text),"i");

        for(var i=0;i<this.data.length;i++) {
            if(this.data[i].match(expression)) {
                matches[matches.length] = this.data[i];
            }
        }

        return matches;
    },

    temporarilyDisableSubmission : function()
    {
        this.cancelSubmit = true;
        var me = this;
        var revert = function()
        {
            me.cancelSubmit = false;
        };

        setTimeout(revert,this.CANCEL_SUBMISSION_TIMEOUT);
    },

    onWindowClick : function(event)
    {
        var element = Event.element(event);

        var parent = element;
        while(parent)
        {
            if(parent == this.element || parent == this.popup ||
               parent == this.showAllButton)
            {
                return;
            }

            parent = parent.parentNode;
        }

        this.hide();
    },

    onUpButton : function(event)
    {
        this.selectedIndex = -1;
        this.scroll--;
        if(this.scroll < 0)
            this.scroll = 0;

        this.show();
        Event.stop(event);
        this.element.focus();
    },

    onDownButton : function(event)
    {
        this.selectedIndex = -1;
        this.scroll++;
        if(this.scroll > (this.matches.length - this.MAX_VISIBLE))
            this.scroll = (this.matches.length - this.MAX_VISIBLE);

        this.show();
        Event.stop(event);
        this.element.focus();
    },

    onKeyDown : function(event)
    {
        if(event.keyCode == 13 && this.visible)
        {
            this.temporarilyDisableSubmission();
            this.select();
            Event.stop(event);
            return false;
        }
    },

    showAll : function()
    {
        this.matches = this.findMatches('');
        this.element.focus();
        this.show();
    },

    onKeyPress : function(event)
    {
        if(event.keyCode == Event.KEY_TAB)
        {
            if(this.visible)
            {
                this.select();
                Event.stop(event);
                return false;
            }
        }
        else if(event.keyCode == Event.KEY_DOWN)
        {
            this.selectedIndex++;
            if(this.selectedIndex < this.scroll)
                this.selectedIndex = this.scroll;

            if(this.selectedIndex >= this.matches.length)
                this.selectedIndex = this.matches.length - 1;

            if(this.scroll <= (this.selectedIndex - this.MAX_VISIBLE))
                this.scroll++;

            if(this.matches.length == 0)
            {
                this.matches = this.findMatches(this.element.value);
            }

            this.show();
            Event.stop(event);
            return;
        }
        else if(event.keyCode == Event.KEY_UP)
        {
            this.selectedIndex--;
            if(this.selectedIndex <= -1 && this.scroll <= 0)
            {
                this.selectedIndex = -1;
                this.hide();
                Event.stop(event);
                return;
            }

            if(this.selectedIndex <= -1)
                this.selectedIndex = this.scroll + (this.MAX_VISIBLE - 1);

            if(this.scroll > this.selectedIndex)
                this.scroll--;

            this.show();
            Event.stop(event);
            return;
        }
        else if(event.keyCode != 13)
        {
            if(this.timerId)
                clearTimeout(this.timerId);

            this.timerId = setTimeout(this.onTick,this.TIMER_TICK);
        }
    },

    onTick : function()
    {
        this.selectedIndex = -1;
        this.scroll = 0;
        if(this.element.value != '')
        {
            this.matches = this.findMatches(this.element.value);
            if(this.matches.length > 0)
                this.show();
            else
                this.hide();
        }
        else
        {
            this.hide();
        }
    },

    onSubmit : function()
    {
        if(this.cancelSubmit)
        {
            this.cancelSubmit = false;
            return false;
        }
        else
        {
            return true;
        }
    },

    onItemOn : function()
    {
        for(var i=0;i<this.autocomplete.MAX_VISIBLE;i++)
            Element.removeClassName(this.autocomplete.listItems[i],"selected");

        Element.addClassName(this,"selected");
        this.autocomplete.selectedIndex = this.number;
    },

    onItemOff : function()
    {
        Element.removeClassName(this,"selected");
        this.autocomplete.selectedIndex = -1;
    },

    onItemClick : function()
    {
        this.autocomplete.selectedIndex = this.number;
        this.autocomplete.select();
    },

    show : function()
    {
        if(this.matches.length <= 0)
            return ;

        var text = this.element.value;

	    // Modifications for multiples values
		var exp = new RegExp(",?[^,]*$" , "i");
		m = text.match(exp);
		text = m[0].replace(/^[,\s]+|\s+$/g,"");

        var expression = new RegExp("("+RegExp.escape(text)+")","i");

        if(this.scroll > 0)
            Element.removeClassName(this.upButton,"disabled")
        else
            Element.addClassName(this.upButton,"disabled")

        if((this.scroll + this.MAX_VISIBLE) < this.matches.length)
            Element.removeClassName(this.downButton,"disabled")
        else
            Element.addClassName(this.downButton,"disabled")

        for(var i=0;i<this.MAX_VISIBLE;i++)
        {
            if(this.matches[i+this.scroll])
            {
                var text = this.matches[i+this.scroll];
                text = text.replace(expression,"<strong>$1</strong>");
                this.listItems[i].innerHTML = text;
                this.listItems[i].number = i + this.scroll;
                this.listItems[i].value = this.matches[i+this.scroll];

                if(this.selectedIndex == (this.scroll + i))
                    Element.addClassName(this.listItems[i],"selected");
                else
                    Element.removeClassName(this.listItems[i],"selected");

                Element.show(this.listItems[i]);
            }
            else
            {
                Element.hide(this.listItems[i]);
            }
        }

        this.visible = true;
        Element.show(this.popup);
        this.setPopupPosition();
    },

    setPopupPosition : function()
    {
        var position = Position.cumulativeOffset(this.element);
        var scrollY = document.body.scrollTop ?
            document.body.scrollTop : document.documentElement.scrollTop;
        var viewHeight = (navigator.userAgent.toLowerCase().indexOf("safari") != -1 &&
            window.innerHeight) ? window.innerHeight :
                document.documentElement.clientHeight;

        this.popup.style.width = (this.element.offsetWidth - 2) + "px";
        this.popup.style.left = position[0] + "px";

        var popupTop = position[1] + Element.getHeight(this.element);
        if((popupTop + this.popup.offsetHeight > scrollY + viewHeight) &&
           (position[1] - this.popup.offsetHeight > scrollY))
        {
            popupTop = position[1] - this.popup.offsetHeight;
        }

        this.popup.style.top = popupTop + "px";
    },

    hide : function()
    {
        this.matches = new Array();
        this.selectedIndex = -1;
        this.scroll = 0;
        this.visible = false;
        Element.hide(this.popup);
    },

    select : function()
    {
        if(this.selectedIndex != -1)
        {
            // Modifications for multiples values
            //this.element.value += this.matches[this.selectedIndex]+', ';
            this.element.value = this.element.value.replace(/(,?\s?)[^,]*$/i, "$1" + this.matches[this.selectedIndex] + ', ');
        }
        setCaretToEnd(this.element);
        this.hide();
    }
};

function setSelectionRange(input, selectionStart, selectionEnd) {
  if (input.setSelectionRange) {
    input.focus();
    input.setSelectionRange(selectionStart, selectionEnd);
  }
  else if (input.createTextRange) {
    var range = input.createTextRange();
    range.collapse(true);
    range.moveEnd('character', selectionEnd);
    range.moveStart('character', selectionStart);
    range.select();
  }
}

function setCaretToEnd (input) {
  setSelectionRange(input, input.value.length, input.value.length);
}