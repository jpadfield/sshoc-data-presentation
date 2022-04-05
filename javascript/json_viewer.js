function parseJSON() {
  
  hljs.highlightAll();			
	$('pre.json-renderer').each(function(index, value) {			
	
  var vtext = $(value).text()
	var jsonResult;
			
	try {jsonResult = JSON.parse(vtext);}
	catch (e) {
		//console.log("\""+vtext+"\" " + "is not valid JSON: "+e);
		};
		
  if (jsonResult) {$(value).jsonViewer(jsonResult);}
	else
		{
    let vurl = new URL(vtext);
		if (vurl.origin != "null") {
			$.getJSON({
				url: vtext
				}).done(function (result, status, xhr) {
					$(value).jsonViewer(result)
          }).fail(function (xhr, status, error) {									
            $(value).jsonViewer(JSON.parse('{"string":"'+vtext+
              '","error":"The supplied text does not seems to be' +
							' valid JSON or a valid URL"}'));
            });
      }
    else
			{$(value).jsonViewer(JSON.parse('{"string":"'+vtext+
			 '","error":"The supplied text does not seems to be valid '+
			 'JSON or a valid URL"}'));}
		}		
  });
  }
