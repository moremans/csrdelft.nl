var arVersion = navigator.appVersion.split("MSIE")
var version = parseFloat(arVersion[1])

function fixPNG(imageID) 
{
	myImage = document.getElementById(imageID)
	if ((version >= 5.5) && (version < 7) && (document.body.filters)) 
	{
		var imgID = (myImage.id) ? "id='" + myImage.id + "' " : ""
		var imgClass = (myImage.className) ? "class='" + myImage.className + "' " : ""
		var imgTitle = (myImage.title) ? 
		             "title='" + myImage.title  + "' " : "title='" + myImage.alt + "' "
		var imgStyle = "display:inline-block;" + myImage.style.cssText
		var strNewHTML = "<span " + imgID + imgClass + imgTitle
                  + " style=\"" + "width:" + myImage.width 
                  + "px; height:" + myImage.height 
                  + "px;" + imgStyle + ";"
                  + "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader"
                  + "(src=\'" + myImage.src + "\', sizingMethod='scale');\"></span>"
		myImage.outerHTML = strNewHTML	  
	}
}