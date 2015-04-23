<form $AttributesHTML>
    <div class="kapost-convert-dialog-header north">
        <h2><%t KapostAdmin.CONVERT_POST "_Convert Post" %></h2>
        
        <p><%t KapostAdmin.CONVERT_DESC "_This will convert this Kapost object to a normal object, you must choose from one of the following options." %></p>
    </div>
    
    <div class="kapost-convert-dialog-content center">
        <% if $Message %>
            <p id="{$FormName}_error" class="message $MessageType">$Message</p>
        <% else %>
            <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
        <% end_if %>
        
        <fieldset>
            <% if $Legend %><legend>$Legend</legend><% end_if %> 
            <% loop $Fields %>
                $FieldHolder
            <% end_loop %>
            <div class="clear"><!-- --></div>
        </fieldset> 
    </div>
    
    <div class="kapost-convert-dialog-footer south">
        <% if $Actions %>
            <div class="Actions">
                <% loop $Actions %>
                    $Field
                <% end_loop %>
            </div>
        <% end_if %>
    </div>
</form>