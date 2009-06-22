#!/usr/bin/env php
<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, Control Yourself, Inc.
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

# Abort if called from a web server
if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
    print "This script must be run from the command line\n";
    exit();
}

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));
define('LACONICA', true);

require_once(INSTALLDIR . '/lib/common.php');

common_log(LOG_INFO, 'Fixing up conversations.');

$notice = new Notice();
$notice->whereAdd('conversation is null');
$notice->orderBy('id');

$cnt = $notice->find();

print "Found $cnt notices.\n";

while ($notice->fetch()) {

    print "$notice->id =>";

    $orig = clone($notice);

    if (empty($notice->reply_to)) {
        $notice->conversation = $notice->id;
    } else {
        $reply = Notice::staticGet('id', $notice->reply_to);

        if (empty($reply)) {
            common_log(LOG_WARNING, "Replied-to notice $notice->reply_to not found.");
            $notice->conversation = $notice->id;
        } else if (empty($reply->conversation)) {
            common_log(LOG_WARNING, "Replied-to notice $reply->id has no conversation ID.");
            $notice->conversation = $notice->id;
        } else {
            $notice->conversation = $reply->conversation;
        }
    }

    print "$notice->conversation";

    $result = $notice->update($orig);

    if (!$result) {
        common_log_db_error($notice, 'UPDATE', __FILE__);
        continue;
    }

    print ".\n";
}

ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);
mb_internal_encoding('UTF-8');
