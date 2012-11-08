#!/usr/bin/expect -f
set password ki55m3
set ip [lindex $argv 1]
set path [lindex $argv 0]
spawn scp root@$ip:$path $path

set timeout 300
expect "root@$ip's password:"
set timeout 300
send "$password\r"
set timeout 300
send "exit\r"
expect eof
