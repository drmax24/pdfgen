## pdfgen microservice
> **Note:** This project is in alpha phase. However it is workable and safe.

Http microservice that renders html into pdf.  
You will not need a back-end developer assistance to create dynamic pdf documents.
PDF is a visual thing so it should be done on html or react.


## Installation
1. You need to install wkhtmltopdf.
You can install it on freebsd / ubuntu / mac os.
2. Your php running on a web-server should be able to execute CLI


## Under the hood
This project provides an API wrapper around  
https://github.com/mikehaertl/phpwkhtmltopdf  
which is in turn a laravel wrapper around  
wkhtmltopdf console utility  
https://wkhtmltopdf.org/  
which is in turn a wrapper around qtwebbrowser
http://www.qtweb.net/  
Qtweb has similar capabilities to IE11. In the same time qtweb does not render anything in a strange way.  
It it is very good. Will give you no problems.