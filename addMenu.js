/**
 * Creates a menu entry in the Google Docs UI when the document is opened.
 *
 * @param {object} e The event parameter for a simple onOpen trigger. To
 *     determine which authorization mode (ScriptApp.AuthMode) the trigger is
 *     running in, inspect e.authMode.
 */
function onOpen(e) {
  var menu = DocumentApp.getUi().createAddonMenu(); // Or DocumentApp or FormApp.
  if (e && e.authMode == ScriptApp.AuthMode.NONE) {
    // Add a normal menu item (works in all authorization modes).
    menu.addItem('Start workflow', 'startWorkflow');
  } 
  else {
    // Add a menu item based on properties (doesn't work in AuthMode.NONE).
    var properties = PropertiesService.getDocumentProperties();
    var workflowStarted = properties.getProperty('workflowStarted');
    if (workflowStarted) {
      menu.addItem('Check workflow status', 'checkWorkflow');
    } else {
      menu.addItem('Start workflow', 'startWorkflow');
    }
  }
  menu.addToUi();
}

/**
 * Opens a sidebar in the document containing the add-on's user interface.
 */
  function startWorkflow() {
  //ectract the content into html
  extractDoc();
  //pop up the login interface
  var html = HtmlService.createHtmlOutputFromFile('Page')
      .setSandboxMode(HtmlService.SandboxMode.IFRAME)
      .setTitle("WordPress Login")
      .setWidth(300);
  DocumentApp.getUi() // Or DocumentApp or FormApp.
      .showSidebar(html);
}

/**
 * extract all elements in the google docs into html format
 * for testing purpose, send email using html as content
 *                      cerate a new doc in drive using html as content
 */
function extractDoc(){
    var body = DocumentApp.getActiveDocument().getBody();
    var numChild=body.getNumChildren();
    // records all the content info of docs
    var content=[];

    //iterate all elements of the body, extract the information
    for(var index=0;index<numChild;index++)
    {
      content.push(process(body.getChild(index)));
    }
    sendEmail(content);
    createDocumentForHtml(content);

}

function createDocumentForHtml(content) {
  var html=content.join('\r');
  var name = DocumentApp.getActiveDocument().getName()+".html";
  var newDoc = DocumentApp.create(name);
  newDoc.getBody().setText(html);
  newDoc.saveAndClose();
}

//logic parse the tags as <p> <text> <text> <img> </image></p>


/**
 * parse the docs paragraph by paragraph, also keep the original format
 *
 * @param element: each element of the body
 */
function process(element){
  var openTag="";
  var closeTag="";
  var result=[];
  
  //if (element.getNumChildren() == 0)
     //   return "";
  //keep original heading format of the element
  if(element.getType()==DocumentApp.ElementType.PARAGRAPH){
    switch (element.getHeading()) {
    case DocumentApp.ParagraphHeading.HEADING6:
         openTag = "<h6>", closeTag = "</h6>"; 
         break;
    case DocumentApp.ParagraphHeading.HEADING5: 
         openTag = "<h5>", closeTag = "</h5>"; 
         break;
    case DocumentApp.ParagraphHeading.HEADING4:
         openTag = "<h4>", closeTag = "</h4>"; 
         break;
    case DocumentApp.ParagraphHeading.HEADING3:
         openTag = "<h3>", closeTag = "</h3>"; 
         break;
    case DocumentApp.ParagraphHeading.HEADING2:
         openTag = "<h2>", closeTag = "</h2>"; 
         break;
    case DocumentApp.ParagraphHeading.HEADING1:
         openTag = "<h1>", closeTag = "</h1>"; 
         break;
    default: 
         openTag = "<p>", closeTag = "</p>";
    }
  }
  result.push(openTag);
    //if(element.getNumChildren()==1){
    //if element is plian text, then parse the text
    if (element.getType() == DocumentApp.ElementType.TEXT){
      parseText(element,result);
    }
    else if (item.getType()===DocumentApp.ElementType.LIST_ITEM){
    	parseListedItem(element, result);
    }
    //check for image

    //check for video

    //check for code

    //}
    else{
      
    var numChildren = element.getNumChildren();

    // Walk through all the child elements of the doc.
    for (var i = 0; i < numChildren; i++) {
      var child = element.getChild(i);
      result.push(process(child));
    } 
      
    }
    result.push(closeTag);
    return result.join('');
}

//parse logic <ul><li></li></ul>
function parseListedItem(element,result){
	// first get the listed style: An enumeration of the supported glyph types.
	var openTag="";
	var closeTag="";
	var numChild=element.getNumChildren();

	// if the listed items are not in order using tags<ul>
	if(element.getGlyphType()!=NUMBER){
		openTag="<ul>";
		closeTag="</ul>";
	}
	//Else if the listed items are not in order using tags<ol>
	else{
	    openTag="<ol>";
	    closeTag="</ol>";
	}
	//


}

function parseText(element,result){
    var text = element.getText();
    //Retrieves the set of text indices that correspond to the start of distinct text formatting runs
    //the set of text indices at which text formatting changes
    var indices = element.getTextAttributeIndices();

    //if the entire paragraph is the same format, then indice is 0
    if(indices.length<1)
      getTextFormat(element.getAttributes(),0,element,text,result);

    else{
    for (var i=0; i < indices.length; i ++) {
      //get the text format of current part of elements
      var partAtts = element.getAttributes(indices[i]);
      var startPos = indices[i];
      //check if it is at end of the string
      var endPos = i+1 < indices.length ? indices[i+1]: text.length;
      //find the substring within text indice
      var partText = text.substring(startPos, endPos)
      getTextFormat(partAtts,startPos,element,partText,result);
    }
  }
}

function getTextFormat(attributes,startPos,element,text,result){
  var openTag="";
  var closeTag="";
  var pass=true;
  if (attributes.ITALIC) {
        openTag='<i>';
        closeTag='</i>';
      }
      if (attributes.BOLD) {
        openTag='<b>';
        closeTag='</b>';
      }
      if (attributes.UNDERLINE) {
      	//get the url link
        if(element.getLinkUrl(startPos)){
        	result.push('<a href="' + element.getLinkUrl(startPos) + '">' + text + '</a>');
            pass=false;
        }
        //get the undeline text
        else{
        	openTag='<u>';
            closeTag='</u>';
        }
    }
    if(pass){
    result.push(openTag);
    result.push(text);
    result.push(closeTag);
  }
}


function sendEmail(content){
  var html=content.join('\r');
  var name = DocumentApp.getActiveDocument().getName()+".html";
  MailApp.sendEmail({
     to: Session.getActiveUser().getEmail(),
     subject: name,
     htmlBody: html,
   });
}