#!/bin/bash
cd $2
git pull origin $1
php vendor/bin/phinx migrate