# Twitter

* Version: 0.4
* Author: [Ben Passmore](http://www.passbe.com)
* Build Date: 2012-01-03
* Requirements: Symphony 2.3

## Description

A Symphony extension that allows you to retrieve tweets from a twitter account.

## Installation - git submodules

This extension uses an external library for twitter communication which resides in lib/twitter-async/. If this folder (or its contents) do not exist for you please ensure all git submodules have been updated recursively.

## Obtaining a Consumer Token and Secret

In order to use this extension you must register an application with twitter to allow OAuth communication.

* Visit [https://dev.twitter.com/user/login](https://dev.twitter.com/user/login) and sign in
* In the drop down under your screen name in the top right select "My applications"
* Create a new application and fill out the required fields. Be sure to set a callback URL (your website URL or preferences page are both fine), leaving it blank will result in Symphony errors when visiting your preferences page.
* Once created, you should be able to see a Consumer Key and Consumer Secret, save these to the twitter preferences on your Symphony website.

## Future Work

* I have a prototype field that will publish a tweet once the entry has been created. More work is needed before I can release this feature.
