<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

date_default_timezone_set("UTC");

// Database parameters
$dbUser = "%dbuser%";
$dbPassword = "%dbpassword%";
$dbName = "%dbname%";
$dbHost = "%dbhost%";

// External Servers
$apiServer = "%apiserver%";
$imageServer = "%imageserver%";

// Base
$baseFile = __FILE__;
$baseDir = dirname($baseFile) . "/";
$baseUrl = "/";
$baseAddr = "%baseaddr%";
$fullAddr = "http://" . $baseAddr;
chdir($baseDir);

// Logfile
$logfile = "%logfile%";

// Memcache
$memcacheServer = "%memcache%";
$memcachePort = "%memcacheport%";

// Pheal
$phealCacheLocation = "%phealcachelocation%";

// Cookiiieeeee
$cookie_name = "zKB";
$cookie_time = (3600 * 24 * 30); // 30 days
$cookie_secret = "%cookiesecret%";

// API
$apiBinAttempts = 10; // 10 seconds of bin alive time
$apiTimeBetweenAccess = 10; // 6 seconds between each bin of requests
$apiWhiteList = array();

// Stomp
$stompServer = "";
$stompUser = "";
$stompPassword = "";

// CloudFlare
$cfUser = "";
$cfKey = "";

// Disqus
$disqusShortName = "";
$disqusSecretKey = "";
$disqusPublicKey = "";

// Email stuff
$emailsmtp = "";
$emailusername = "";
$emailpassword = "";
$sentfromemail = "";
$sentfromdomain = "";

// Twitter
$consumerKey = "";
$consumerSecret = "";
$accessToken = "";
$accessTokenSecret = "";

// Show Ads? Disabled by default
$showAds = false;

// Slim config
// to enable log, add "log.writer" => call after "log.enabled" => true, - you might have to load it in index after init has run and do $config["log.writer"] = call;
$config = array(
	"mode" => "production",
	"debug" => false,
	"log.enabled" => false,
	"cookies.secret_key" => $cookie_secret
	);
