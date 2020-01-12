/*
const $ = require('jquery');

$(document).ready(function() {

	var search = $(".search").attr("data");
	console.log("toto");
	$.ajax({
        type        : 'GET',
        url         : 'http://www.jeuxdemots.org/rezo-dump.php?gotermsubmit=Chercher&gotermrel='+ search +'&rel=',
        success: function(data) {
            console.log(data);
        },
        error: function(data) {
        	console.log("tata");
            console.log(data);
        }
    });


});

const https = require('http');
const parse = require('node-html-parser').parse;

https.get('http://www.jeuxdemots.org/rezo-dump.php?gotermsubmit=Chercher&gotermrel=stylo&rel=', (resp) => {
  let data = '';

  // A chunk of data has been recieved.
  resp.on('data', (chunk) => {
    data += chunk;
  });

  // The whole response has been received. Print out the result.
  resp.on('end', () => {
    const root = parse(data);
    console.log(root.querySelector("CODE").outerHTML);
  });

}).on("error", (err) => {
  console.log("Error fetch JeuxdeMots.org : " + err.message);
});
*/