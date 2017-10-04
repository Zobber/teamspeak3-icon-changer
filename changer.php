<?php

define('BASE_DIR', dirname(__FILE__) . '/');

$c = [
    
    'query' => [
        'hostname' => '',
        'username' => 'serveradmin',
        'password' => '',
        'udp_port' => 9987,
        'tcp_port' => 10011
    ],
    
    'delete_old' => true,
    
];

include_once 'lib/ts3admin.class.php';

$ts = new ts3admin($c['query']['hostname'], $c['query']['tcp_port']);

if (!$ts->getElement('success', $ts->connect()))
    die('Failed to connect TS3 Query.');

if (!$ts->getElement('success', $ts->login($c['query']['username'], $c['query']['password'])))
    die('Failed to login.');

if (!$ts->getElement('success', $ts->selectServer($c['query']['udp_port'])))
    die('Failed to select server.');

$icons = [];

function append_icon($filename, $dir)
{
    global $icons;
    
    if (preg_match('/(\d+)\..*/', $filename, $matches))
        $icons[$matches[1]] = $dir . $filename;
}

foreach (new DirectoryIterator(BASE_DIR . 'icons/') as $file)
{
    if ($file->isDot())
        continue;
    
    if ($file->isDir())
    {
        foreach (new DirectoryIterator(BASE_DIR . 'icons/' . $file->getFilename() . '/') as $subfile)
        {
            if ($subfile->isDot())
                continue;
            
            append_icon($subfile->getFilename(), BASE_DIR . 'icons/' . $file->getFilename() . '/');
        }
    }
    else
        append_icon($file->getFilename(), BASE_DIR . 'icons/');
}

$iconlist = $ts->ftGetFileList(0, '', '/icons/');
$available = [];

foreach ($iconlist['data'] as $icon)
    $available[] = $icon['name'];

$grouplist = $ts->serverGroupList();

if (!$grouplist['success'])
    die('Failed to get grouplist from server' . PHP_EOL);

$groups = [];

foreach ($grouplist['data'] as $info)
{
    $groups[$info['sgid']] = $info['name'];
}

$groupicon = [];

print('Fetching group icons from server ...' . PHP_EOL);

foreach ($icons as $gid => $_)
{
    if (isset($groupicon[$gid]))
        continue;
    
    if (!isset($groups[$gid]))
    {
        printf('Group with gid %d does not exists on server' . PHP_EOL, $gid);
        unset($icons[$gid]);
        continue;
    }
    
    $perms = $ts->serverGroupPermList($gid, true);
    
    if (!$perms['success']) {
        var_dump($perms);
        die('Failed to get permission list of group' . PHP_EOL);
    }
    
    $icon = 0;
    
    foreach ($perms['data'] as $perm)
    {
        if ($perm['permsid'] == 'i_icon_id')
        {
            $icon = $perm['permvalue'];
            break;
        }
    }
    
    $groupicon[$gid] = $icon;
}

foreach ($icons as $gid => $filename)
{
    $bytes = @file_get_contents($filename);
    
    if (!$bytes)
    {
        printf('Failed to get contents of file "%s"' . PHP_EOL, $filename);
        continue;
    }
    
    $crc = crc32($bytes);
    $size = strlen($bytes);
    
    $permvalue = ($crc > pow(2, 31)) ? ($crc - 4294967296) : $crc;
    
    if ($groupicon[$gid] == $permvalue)
    {
        printf('Skipping group %d icon not changed.' . PHP_EOL, $gid);
        continue;
    }
    
    $iconname = 'icon_' . $crc;
    $deleted = false;
    
    /*if ($c['delete_old'])
    {
        printf('Delete old icon "%s"' . PHP_EOL, $iconname);
        $ts->ftDeleteFile(0, '', [ '/' . $iconname ]);
        $deleted = true;
    }*/
    
    if (!in_array($iconname, $available) || $c['delete_old'])
    {
        printf('Uploading "%s" -> "%s"' . PHP_EOL, str_replace(BASE_DIR, '', $filename), $iconname);
        
        $init = $ts->ftInitUpload('/' . $iconname, 0, $size, '', true);
        
        if (!$init['success'])
        {
            printf('Failed to initalize upload session for icon.' . PHP_EOL);
            continue;
        }
        
        $output = $ts->ftUploadFile($init, $bytes);
        
        if (!$ts->getElement('success', $output))
        {
            printf('Failed to upload icon.' . PHP_EOL);
            var_dump($output, $init);
            exit();
        }
    }
    
    print('Updating group icon - ');
    
    $copy = $ts->serverGroupCopy($gid, 0, '___TEMPORARY___', 1);
    
    if (!$copy['success'])
        die('Failed to duplicate servergroup' . PHP_EOL);
    
    $temp_gid = $copy['data']['sgid'];
    
    $result = $ts->serverGroupAddPerm($temp_gid, [
        'i_icon_id' => [ $permvalue, 0, 0 ]
    ]);
    
    if (!$result['success'])
    {
        $ts->serverGroupDelete($temp_gid);
        die('Failed to set new icon in temporary group' . PHP_EOL);
    }
    
    $result = $ts->serverGroupCopy($temp_gid, $gid, $groups[$gid], 2);
    
    if (!$result['success'])
    {
        $ts->serverGroupDelete($temp_gid);
        die('Failed connect groups' . PHP_EOL);
    }
    
    $result = $ts->serverGroupDelete($temp_gid);
    
    if ($result['success'])
        print('success' . PHP_EOL);
    else
        print('failure' . PHP_EOL);
}




