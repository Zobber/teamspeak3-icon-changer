# TeamSpeak 3 Icon Changer
## Requirements
To use this application you need at least PHP 5.3+ installed.
You can install [Imagick extension][imagick].
## Usage
1. Put icons into `icons/` (you can use subdirectories) with name format `<group_id>.png`.
2. Setup connection details in `changer.php`
```php
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
```
3. Execute `php generator.php` (for Unix like systems) or `changer.bat` (for Windows)
## Additional info
If you have Imagick extension installed and enabled, application will strip metadata from your graphic files to get lower size.

[imagick]: http://php.net/manual/en/book.imagick.php
