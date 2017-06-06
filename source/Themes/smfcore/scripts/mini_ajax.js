/*
ajax.x - The XMLHttpRequest object (or MS equivalent) used for communication

ajax.serialize(f)
f = the form element you wish to be serialized
This function serializes all the fields in a form so that they can be passed as a query string in the form 'arg1=val1&arg2=val2'.

ajax.get(url, func)
url = the url to query (can contain arguments after a '?')
func = the function to call once the response is returned
This function uses a GET request to query the specified url and return a response to the specified function.

ajax.gets(url)
url = the url to query (can contain arguments after a '?')
This function uses a GET request to query the specified url and return a response synchronously. Use this sparingly, as synchronous calls can lock up the browser.

ajax.post(url, func, args)
url = the url to query
func = the function to call once the response is returned
args = a string containing arguments to be passed to the url
This function uses a POST request to query the specified url and return a response to the specified function.

ajax.update(url, elm)
url = the url to query
elm = the (name of the) element to update
This function uses a GET request to query the specified url and insert the result into the specified element.

ajax.submit(url, elm, frm)
url = the url to query
elm = the (name of the) element to update
frm = the form element to submit
This function is typically used in the onsubmit handler of a function. The form is not submitted the usual way; the form is instead serialized using 'ajax.serialize' and submitted using 'ajax.post'. The result is then inserted into the specified element.

ajax.append(url, elm)
url = the url to query
elm = the (name of the) element to append the contents of the url to
This function will append the contents of the url to the contents of the element.

ajax.getnf(url)
url = the url to query
This function will query a URL, but will not do anything with the returned information.
*/
function $(e){if(typeof e=='string')e=document.getElementById(e);return e};
function collect(a,f){var n=[];for(var i=0;i<a.length;i++){var v=f(a[i]);if(v!=null)n.push(v)}return n};
ajax={};
ajax.x=function(){try{return new ActiveXObject('Msxml2.XMLHTTP')}catch(e){try{return new ActiveXObject('Microsoft.XMLHTTP')}catch(e){return new XMLHttpRequest()}}};
ajax.serialize=function(f){var g=function(n){return f.getElementsByTagName(n)};var nv=function(e){if(e.name)return encodeURIComponent(e.name)+'='+encodeURIComponent(e.value);else return ''};var i=collect(g('input'),function(i){if((i.type!='radio'&&i.type!='checkbox')||i.checked)return nv(i)});var s=collect(g('select'),nv);var t=collect(g('textarea'),nv);return i.concat(s).concat(t).join('&');};
ajax.send=function(u,f,m,a){var x=ajax.x();x.open(m,u,true);x.onreadystatechange=function(){if(x.readyState==4)f(x.responseText)};if(m=='POST')x.setRequestHeader('Content-type','application/x-www-form-urlencoded');x.send(a)};
ajax.get=function(url,func){ajax.send(url,func,'GET')};
ajax.gets=function(url){var x=ajax.x();x.open('GET',url,false);x.send(null);return x.responseText};
ajax.post=function(url,func,args){ajax.send(url,func,'POST',args)};
ajax.update=function(url,elm){var e=$(elm);var f=function(r){e.innerHTML=r};ajax.get(url,f)};
ajax.submit=function(url,elm,frm){var e=$(elm);var f=function(r){e.innerHTML=r};ajax.post(url,f,ajax.serialize(frm))};
ajax.dosubmit=function(url,elm,frmnm){ajax.submit(url,elm,getObj(frmnm))};
ajax.append=function(url,elm){ajax.get(url,function(r){elm.innerHTML=elm.innerHTML+r;});};
ajax.getnf=function(url){ajax.send(url,function(r){},'GET')};

function getObj(n){if(document.getElementById){return document.getElementById(n);}else if(document.all){return document.all[n];}else if(document.layers){return document.layers[n];}}
