---
customlog:
  -
    format: combined
    target: /etc/apache2/logs/domlogs/raspinaclothing.com
  -
    format: "\"%{%s}t %I .\\n%{%s}t %O .\""
    target: /etc/apache2/logs/domlogs/raspinaclothing.com-bytes_log
documentroot: /home/hyxfvjbq/public_html
group: hyxfvjbq
hascgi: 0
homedir: /home/hyxfvjbq
ip: 185.94.99.249
owner: rescpanel80p
phpopenbasedirprotect: 1
phpversion: ea-php82
port: 80
scriptalias:
  -
    path: /home/hyxfvjbq/public_html/cgi-bin
    url: /cgi-bin/
serveradmin: webmaster@raspinaclothing.com
serveralias: mail.raspinaclothing.com www.raspinaclothing.com
servername: raspinaclothing.com
usecanonicalname: 'Off'
user: hyxfvjbq
