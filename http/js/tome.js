/*
    Script functions that create effects which simulate the reading of a tome.
    This includes things like flipping pages when the next page is successfully loaded, etc.
    We are using jQuery.
*/

function showPage(pageNum, bookNum) {
    var xmlFilePath = "./src/xml/testtome.xml";
    fetch(xmlFilePath)
    .then(response => response.text())
    .then(xmlString => {
      var parser = new DOMParser();
      var xmlDoc = parser.parseFromString(xmlString, "text/xml");
  
      // Get the book and page elements
      var bookId = bookNum;
      var pageId = pageNum;
      var bookElement = xmlDoc.querySelector("book[id='" + bookId + "']");
      var pageElement = bookElement.querySelector("page[id='" + pageId + "']");
      var title = bookElement.getAttribute("title");
  
      // Update the div named "text"
        var textDiv = document.getElementById("text");
        var titleDiv = document.getElementById("title");
        textDiv.innerHTML = pageElement.innerHTML;
        var titleElement = bookElement.querySelector("title");
        var title = titleElement.textContent;
        titleDiv.textContent = title;
    })
    .catch(error => console.error(error));
}

// Run showPage(1) when page is ready.
$(document).ready(function() {
var tomeDataAttr = $("#tome-script").attr("data-tome");
  if (typeof tomeDataAttr !== "undefined") {
    var tomeData = JSON.parse(tomeDataAttr);
    var book = tomeData.book;
    var page = tomeData.page;
    showPage(tomeData.page, tomeData.book);
  } else {
    console.error("data-tome attribute is not defined on script tag");
  }
}
);